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
        } else {
            $rankExpr = "
        ts_rank_cd(
            to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')),
            websearch_to_tsquery('simple', ?)
        ) AS rank
    ";
            $params = [$tsQuery, $resume->id];
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

        if ($isTech) {
            $baseSql .= " AND v.category = ?";
            $params[] = $resumeCategory;

            $titleCondition = collect($tokens)
                ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                ->implode(' OR ');

            if ($titleCondition) {
                $baseSql .= " AND ({$titleCondition})";
            }

            $finalSql = "{$baseSql} ORDER BY rank DESC, id DESC LIMIT 50";

            Log::info('💻 [TECH MODE: EXACT CATEGORY SEARCH]', [
                'resume_id' => $resume->id,
                'category' => $resumeCategory,
                'title_condition' => $titleCondition ?: null,
                'is_tech' => true,
                'sql' => $finalSql,
                'params' => $params,
            ]);

        } else {
            // non-tech uchun ikkita qism: kategoriya + title orqali
            $unionSql = null;
            $categoryCount = 0;
            $titleCount = 0;

            if ($resumeCategory) {
                $baseSql .= " AND v.category = ?";
                $params[] = $resumeCategory;

                // 🔹 Kategoriya bo‘yicha topilganlar sonini log qilamiz
                try {
                    $categoryVacancies = DB::select($baseSql, $params);
                    $categoryCount = count($categoryVacancies);
                    Log::info('📊 [CATEGORY SEARCH RESULTS]', [
                        'resume_id' => $resume->id,
                        'category' => $resumeCategory,
                        'count' => $categoryCount,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('❌ [CATEGORY SEARCH ERROR]', [
                        'resume_id' => $resume->id,
                        'category' => $resumeCategory,
                        'error' => $e->getMessage(),
                    ]);
                }

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

                    // 🔹 Title orqali topilganlar sonini log qilamiz
                    try {
                        $titleVacancies = DB::select($unionSql, [$tsQuery, $resume->id]);
                        $titleCount = count($titleVacancies);
                        Log::info('🌍 [TITLE SEARCH RESULTS]', [
                            'resume_id' => $resume->id,
                            'title_condition' => $titleCondition,
                            'count' => $titleCount,
                        ]);
                    } catch (\Throwable $e) {
                        Log::error('❌ [TITLE SEARCH ERROR]', [
                            'resume_id' => $resume->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if ($unionSql) {
                $finalSql = "
            WITH combined AS (
                {$baseSql}
                UNION ALL
                {$unionSql}
            )
            SELECT * FROM combined
            ORDER BY rank DESC, id DESC
            LIMIT 50
        ";
                $params = array_merge($params, [$tsQuery, $resume->id]);
            } else {
                $finalSql = "{$baseSql} ORDER BY rank DESC, id DESC LIMIT 50";
            }

            Log::info('🧾 [NON-TECH FINAL SQL BUILT]', [
                'resume_id' => $resume->id,
                'category' => $resumeCategory,
                'is_tech' => false,
                'category_vacancies' => $categoryCount,
                'title_vacancies' => $titleCount,
                'total_found' => $categoryCount + $titleCount,
                'sql' => $finalSql,
                'params' => $params,
            ]);
        }

        Log::info('🧾 [FINAL SQL BUILT]', [
            'resume_id' => $resume->id,
            'sql' => $finalSql,
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
            'local' => \GuzzleHttp\Promise\Create::promiseFor(DB::select($finalSql, $params)),
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
            ->take(50)
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
            ->take(50);

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
