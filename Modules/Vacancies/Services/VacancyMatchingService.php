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

        Log::info('🚀 Job started', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        // --- 1. Query normalization
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
        $cleanText = fn($w) => mb_strtolower(trim(preg_replace('/[\"\'«»“”]/u', '', $w)), 'UTF-8');

        $tokens = $allVariants
            ->flatMap($splitByComma)
            ->map($cleanText)
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->unique()
            ->take(8)
            ->values();

        Log::info('🧩 Tokens parsed', ['tokens' => $tokens->all()]);

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

        // --- 2. Guess category
        try {
            $guessedCategory = app(VacancyCategoryService::class)
                ->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            $guessedCategory = (is_string($guessedCategory) && !in_array(mb_strtolower($guessedCategory), ['other', ''], true))
                ? $guessedCategory
                : null;
        } catch (\Throwable) {
            $guessedCategory = null;
        }

        $resumeCategory = $resume->category ?? null;

        $techCategories = [
            "IT and Software Development",
            "Data Science and Analytics",
            "QA and Testing",
            "DevOps and Cloud Engineering",
            "UI/UX and Product Design"
        ];

        $isTech = in_array($resumeCategory, $techCategories, true);

        if ($isTech && $resumeCategory) {
            $rankExpr = "
                CASE
                    WHEN v.category = ?
                    THEN ts_rank_cd(
                        to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')),
                        websearch_to_tsquery('simple', ?)
                    )
                    ELSE 0
                END AS rank
            ";

            $params = [$resumeCategory, $tsQuery, $resume->id];

            Log::info('🏷️ [RANK SCOPE SET TO EXACT CATEGORY]', [
                'resume_id' => $resume->id,
                'rank_applied_category' => $resumeCategory,
                'is_tech' => true,
            ]);
        } else {
            $rankExpr = "
                CASE
                    WHEN v.category IN ('IT and Software Development','Data Science and Analytics','QA and Testing','DevOps and Cloud Engineering','UI/UX and Product Design')
                    THEN ts_rank_cd(
                        to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')),
                        websearch_to_tsquery('simple', ?)
                    )
                    ELSE 0
                END AS rank
            ";

            $params = [$tsQuery, $resume->id];

            Log::info('🏷️ [RANK SCOPE SET TO 5-TECH CATEGORIES]', [
                'resume_id' => $resume->id,
                'is_tech' => false,
            ]);
        }

        $baseSql = "
            SELECT
                v.id, v.title, v.description, v.source, v.external_id, v.category,
                {$rankExpr}
            FROM vacancies v
            WHERE v.status = 'publish'
              AND v.source = 'telegram'
              AND v.id NOT IN (SELECT vacancy_id FROM match_results WHERE resume_id = ?)
        ";

        Log::info('🔍 [SEARCH QUERY GENERATED]', [
            'tsQuery' => $tsQuery,
            'tokens' => $tokens->all(),
            'phrases' => $phrases->all(),
            'query_variants' => $allVariants->all(),
        ]);

        if ($isTech) {
            // ✅ Aynan shu rezume kategoriyasi bo‘yicha cheklaymiz
            $baseSql .= " AND v.category = ?";
            $params[] = $resumeCategory;

            // Title LIKE qismi
            $titleCondition = collect($tokens)
                ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                ->implode(' OR ');

            if ($titleCondition) {
                $baseSql .= " AND ({$titleCondition})";
            }

            Log::info('💻 [TECH MODE: EXACT CATEGORY SEARCH]', [
                'resume_id' => $resume->id,
                'category' => $resumeCategory,
                'title_condition' => $titleCondition ?: null,
                'params_order' => $params,
            ]);
        } else {
            // 👇 Texnik bo‘lmagan kategoriya
            if ($resumeCategory) {
                // 🟢 1. Shu kategoriyaga tegishli barcha vakansiyalarni chiqaramiz
                $baseSql .= " AND v.category = ?";
                $params[] = $resumeCategory;

                Log::info('📊 [Categoriyaga tegishli barcha vacansiyalar]', [
                    'resume_id' => $resume->id,
                    'category' => $resumeCategory,
                    'tsQuery_used' => $tsQuery,
                ]);

                // 🟢 2. Shu bilan birga title orqali umumiy search (barcha vacancies ichidan)
                $titleCondition = collect($tokens)
                    ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                    ->implode(' OR ');

                if ($titleCondition) {
                    $unionSql = "
                SELECT
                    v.id, v.title, v.description, v.source, v.external_id, v.category,
                    ts_rank_cd(to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')),
                        websearch_to_tsquery('simple', ?)
                    ) AS rank
                FROM vacancies v
                WHERE v.status = 'publish'
                  AND v.source = 'telegram'
                  AND ($titleCondition)
                  AND v.id NOT IN (SELECT vacancy_id FROM match_results WHERE resume_id = ?)
            ";

                    Log::info('🌍 [NON-TECH: GLOBAL TITLE SEARCH ADDED]', [
                        'resume_id' => $resume->id,
                        'title_condition' => $titleCondition,
                    ]);

                    // 🧩 3. Ikkisini birlashtiramiz (kategoriya + global title qidiruv)
                    $baseSql = "($baseSql) UNION ($unionSql)";
                    $params = array_merge($params, [$tsQuery, $resume->id]);
                }
            } elseif ($guessedCategory) {
                $baseSql .= " AND v.category = ?";
                $params[] = $guessedCategory;

                Log::info('🧠 [NON-TECH: GUESSED CATEGORY FILTER USED]', [
                    'resume_id' => $resume->id,
                    'guessed_category' => $guessedCategory,
                    'tsQuery_used' => $tsQuery,
                ]);
            } else {
                // 🟡 Category umuman yo‘q — umumiy search
                $titleCondition = collect($tokens)
                    ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                    ->implode(' OR ');

                if ($titleCondition) {
                    $baseSql .= " AND ($titleCondition)";
                }

                Log::info('🌐 [NON-TECH: NO CATEGORY, FULL TABLE SEARCH]', [
                    'resume_id' => $resume->id,
                    'title_condition' => $titleCondition ?: null,
                ]);
            }
        }

        $baseSql .= " ORDER BY rank DESC, id DESC LIMIT 100";

        Log::info('🧾 [FINAL SQL BUILT]', [
            'resume_id' => $resume->id,
            'sql' => $baseSql,
            'params' => $params,
            'is_tech' => $isTech,
        ]);


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
            ->take(100)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");

        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        // --- 6. Vacancies prepare
        $hhItems = $hhVacancies['items'] ?? [];
        foreach ($hhItems as $idx => $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) continue;
            $text = ($item['snippet']['requirement'] ?? '') . "\n" . ($item['snippet']['responsibility'] ?? '');
            if (!empty(trim($text))) {
                $vacanciesPayload[] = [
                    'id'          => null,
                    'text'        => mb_substr(strip_tags($text), 0, 1000),
                    'external_id' => $extId,
                    'raw'         => $item,
                    'source'      => 'hh',
                ];
            }
        }
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'vacancy_id'   => $v->id,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }

        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(100);

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
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }

        Log::info('Prepared payload with ' . count($vacanciesPayload) . ' vacancies');

        // --- 7. Save results
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

                    if (!$vac && isset($match['raw'])) {
                        $vac = $this->vacancyRepository->createFromHH($match['raw']);
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
                    'match' => $match,
                    'error' => $e->getMessage(),
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

        Log::info('All details finished: ' . (microtime(true) - $start) . 's');
        return $savedData;
    }
}
