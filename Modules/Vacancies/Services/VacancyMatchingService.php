<?php

namespace Modules\Vacancies\Services;

use App\Models\Resume;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Concurrency;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Modules\Vacancies\Interfaces\VacancyInterface;
use App\Helpers\TranslitHelper;
use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Modules\TelegramChannel\Services\VacancyCategoryService;
use GuzzleHttp\Promise;

class VacancyMatchingService
{
    protected VacancyInterface $vacancyRepository;
    protected HHVacancyInterface $hhRepository;

    public function __construct(
        VacancyInterface $vacancyRepository,
        HHVacancyInterface $hhRepository
    ) {
        $this->vacancyRepository = $vacancyRepository;
        $this->hhRepository = $hhRepository;
    }

    public function matchResume(Resume $resume, $query): array
    {
        try {
            $start = microtime(true);

            $latinQuery = TranslitHelper::toLatin($query);
            $cyrilQuery = TranslitHelper::toCyrillic($query);
            $translator = new GoogleTranslate();
            $translator->setSource('auto');

            $translations = [
                'uz' => fn() => $translator->setTarget('uz')->translate("\"{$query}\""),
                'ru' => fn() => $translator->setTarget('ru')->translate("\"{$query}\""),
                'en' => fn() => $translator->setTarget('en')->translate("\"{$query}\""),
            ];

            $allVariants = collect([$query])
                ->merge(array_map(fn($f) => $f(), $translations))
                ->unique()
                ->filter()
                ->values();

            $splitByComma = fn($v) => preg_split('/\s*,\s*/u', (string) $v);
            $cleanText = fn($w) => mb_strtolower(trim(preg_replace('/[\"\'«»""]/u', '', $w)), 'UTF-8');

            $tokens = $allVariants
                ->flatMap($splitByComma)
                ->map($cleanText)
                ->filter(fn($w) => mb_strlen($w) >= 2)
                ->unique()
                ->take(8)
                ->values();

            $phrases = $allVariants
                ->flatMap($splitByComma)
                ->map($cleanText)
                ->filter(fn($s) => mb_strlen($s) >= 3 && str_contains($s, ' '))
                ->unique()
                ->take(4)
                ->values();

            $searchQuery = $latinQuery ?: $cyrilQuery;
            $tsTerms = [...$phrases, ...$tokens];
            $mustPair = count($tokens) >= 2 ? ['(' . $tokens[0] . ' ' . $tokens[1] . ')'] : [];
            $webParts = array_merge($mustPair, $tsTerms);
            $tsQuery = !empty($webParts)
                ? implode(' OR ', array_map(fn($t) => str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t, $webParts))
                : (string) $searchQuery;

            $guessedCategory = null;
            try {
                $guessedCategory = app(VacancyCategoryService::class)
                    ->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
                $guessedCategory = (is_string($guessedCategory) && !in_array(mb_strtolower($guessedCategory), ['other', ''], true))
                    ? $guessedCategory
                    : null;
            } catch (\Throwable $e) {
                Log::error('❌ Error categorizing resume', [
                    'resume_id' => $resume->id,
                    'error' => $e->getMessage()
                ]);
            }

            $resumeCategory = $resume->category ?? null;
            Log::info("🔍 Matching resume #{$resume->id} using query: {$query}, category: {$resumeCategory}, guessed category: {$guessedCategory}");
            $techCategories = [
                "IT and Software Development"
            ];
            $isTech = in_array($resumeCategory, $techCategories, true);

            $baseSql = "
                SELECT
                    v.id, v.title, v.description, v.source, v.external_id, v.category,
                    CASE
                        WHEN v.category IN ('IT and Software Development')
                        THEN ts_rank_cd(to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')), websearch_to_tsquery('simple', ?))
                        ELSE 0
                    END AS rank
                FROM vacancies v
                WHERE v.status = 'publish'
                  AND v.source = 'telegram'
                  AND v.id NOT IN (SELECT vacancy_id FROM match_results WHERE resume_id = ?)
            ";

            $params = [$tsQuery, $resume->id];

            if ($isTech) {
                $titleParts = collect(explode(',', (string) ($resume->title ?? '')))
                    ->map(fn($t) => trim($t))
                    ->filter()
                    ->values();

                $searchTokens = $tokens->merge($titleParts)->unique()->values();

                $titleCondition = $searchTokens
                    ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                    ->implode(' OR ');

                if ($titleCondition) {
                    $baseSql .= " AND (
                    (
                        v.category IN ('IT and Software Development')
                        AND (
                            $titleCondition
                            OR to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, ''))
                               @@ websearch_to_tsquery('simple', ?)
                        )
                    )
                )";
                    $params[] = $tsQuery;
                }
            } else {
                if ($resumeCategory) {
                    $baseSql .= " AND v.category = ?";
                    $params[] = $resumeCategory;
                } elseif ($guessedCategory) {
                    $baseSql .= " AND v.category = ?";
                    $params[] = $guessedCategory;
                }
            }

            $baseSql .= " ORDER BY rank DESC, id DESC LIMIT 50";

            $promises = [
                'hh' => \GuzzleHttp\Promise\Create::promiseFor(
                    cache()->remember(
                        "hh:search:{$query}:area97",
                        now()->addMinutes(30),
                        fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
                    )
                ),
                'local' => \GuzzleHttp\Promise\Create::promiseFor(DB::select($baseSql, $params)),
            ];

            $results = Promise\Utils::unwrap($promises);
            $hhVacancies = $results['hh'];
            $localRows = collect($results['local']);

            // HeadHunter natijalarini log qilish
            Log::info("🔍 HeadHunter search natijalari", [
                'resume_id' => $resume->id,
                'query' => $query,
                'total_found' => $hhVacancies['found'] ?? 0,
                'items_count' => count($hhVacancies['items'] ?? []),
                'pages' => $hhVacancies['pages'] ?? 0,
                'per_page' => $hhVacancies['per_page'] ?? 0,
                'raw_response_keys' => array_keys($hhVacancies),
            ]);

            // Agar vacansiyalar bo'lsa, birinchi 3tasini ko'rsatish
            if (!empty($hhVacancies['items'])) {
                Log::info("📋 HeadHunter dan topilgan vacansiyalar (birinchi 3ta)", [
                    'resume_id' => $resume->id,
                    'sample_vacancies' => array_map(function($item) {
                        return [
                            'id' => $item['id'] ?? null,
                            'name' => $item['name'] ?? null,
                            'employer' => $item['employer']['name'] ?? null,
                            'area' => $item['area']['name'] ?? null,
                        ];
                    }, array_slice($hhVacancies['items'], 0, 3))
                ]);
            } else {
                Log::warning("⚠️ HeadHunter dan vacansiya topilmadi", [
                    'resume_id' => $resume->id,
                    'query' => $query,
                ]);
            }

            $localVacancies = $localRows
                ->map(function ($v) use ($isTech, $tokens) {
                    if ($isTech && !empty($tokens)) {
                        foreach (array_slice($tokens->all(), 0, 10) as $t) {
                            $pattern = mb_strtolower($t);
                            if (str_contains(mb_strtolower($v->title), $pattern) || str_contains(mb_strtolower($v->description), $pattern)) {
                                $v->rank += 0.1;
                            }
                        }
                    }
                    return $v;
                })
                ->sortByDesc('rank')
                ->take(50)
                ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");
            // Local vacansiyalar sonini ham log qilish
            Log::info("💾 Local database dan topilgan vacansiyalar", [
                'resume_id' => $resume->id,
                'count' => $localVacancies->count(),
            ]);

            $vacanciesPayload = [];

            foreach ($localVacancies as $v) {
                $vacanciesPayload[] = [
                    'id'   => $v->id,
                    'vacancy_id'   => $v->id,
                    'text' => mb_substr(strip_tags($v->description), 0, 2000),
                ];
            }

            $hhItems = $hhVacancies['items'] ?? [];
            $toFetch = collect($hhItems)
                ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
                ->take(50);
            // Local vacansiyalar sonini ham log qilish
            Log::info("💾 HeadHunter dan topilgan vacansiyalar", [
                'resume_id' => $resume->id,
                'count' => $toFetch->count(),
            ]);

            foreach ($toFetch as $idx => $item) {
                $extId = $item['id'] ?? null;
                if (!$extId || $localVacancies->has($extId)) continue;

                $text = ($item['snippet']['requirement'] ?? '') . "\n" .
                    ($item['snippet']['responsibility'] ?? '');
                if (!empty(trim($text))) {
                    $vacanciesPayload[] = [
                        'id'          => null,
                        'text'        => mb_substr(strip_tags($text), 0, 1000),
                        'external_id' => $extId,
                        'raw'         => $item,
                        'vacancy_index' => $idx,
                    ];
                }
            }

            if (empty($vacanciesPayload)) {
                return [];
            }

            $savedData = [];
            foreach ($vacanciesPayload as $match) {
                try {
                    $vac = null;
                    $vacId = $match['vacancy_id'] ?? null;

                    if ($vacId) {
                        $vac = Vacancy::withoutGlobalScopes()->find($vacId);
                    }
                    if (!$vac && isset($match['external_id'])) {
                        $vac = Vacancy::where('source', 'hh')
                            ->where('external_id', $match['external_id'])
                            ->first();
                        Log::info("resume category: " . $resumeCategory);
                        if (!$vac && isset($match['raw'])) {
                            // Use HH bulk categorization (rule-based) instead of forcing resume category
                            $vac = $this->vacancyRepository->firstOrCreateFromHH($match['raw']);
                        }
                    }

                    if (!$vac && !empty($vacId)) {
                        $savedData[] = [
                            'resume_id'     => $resume->id,
                            'vacancy_id'    => $vacId,
                            'score_percent' => $match['score'] ?? 0,
                            'explanations'  => json_encode($match),
                            'updated_at'    => now(),
                            'created_at'    => now(),
                        ];
                        continue;
                    }

                    if ($vac) {
                        $savedData[] = [
                            'resume_id'     => $resume->id,
                            'vacancy_id'    => $vac->id,
                            'score_percent' => $match['score'] ?? 0,
                            'explanations'  => json_encode($match),
                            'updated_at'    => now(),
                            'created_at'    => now(),
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error('💥 Error while matching vacancy', [
                        'resume_id' => $resume->id,
                        'match' => $match,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            if (!empty($savedData)) {
                $chunks = array_chunk($savedData, 200);
                DB::transaction(function () use ($chunks) {
                    foreach ($chunks as $chunk) {
                        DB::table('match_results')->upsert(
                            $chunk,
                            ['resume_id', 'vacancy_id'],
                            ['score_percent', 'explanations', 'updated_at']
                        );
                    }
                });
            }

            return $savedData;

        } catch (\Throwable $e) {
            Log::error('🚨 Fatal error in matchResume', [
                'resume_id' => $resume->id,
                'query' => $query,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }
}
