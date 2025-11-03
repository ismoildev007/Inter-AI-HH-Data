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

        // --- 2. Vergul bo‘yicha ajratamiz
        $splitByComma = fn($v) => preg_split('/\s*,\s*/u', (string) $v);
        $cleanText = fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w));
        $tokens = $allVariants
            ->flatMap($splitByComma)
            ->map($cleanText)
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->unique()
            ->take(8)
            ->values();

        Log::info('🧩 Tokens parsed', ['tokens' => $tokens->all()]);

        // --- 2. Vergul bo‘yicha ajratish (har bir bo‘lak alohida token bo‘ladi)
        $splitByComma = fn($v) => preg_split('/\s*,\s*/u', (string) $v);
        $cleanText = fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w));

        $tokens = $allVariants
            ->flatMap($splitByComma)
            ->map($cleanText)
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->unique()
            ->take(10)
            ->values();

        Log::info('🧩 Tokens parsed (comma-split)', ['tokens' => $tokens->all()]);

// --- 3. tsQuery OR bo‘yicha yasaymiz
        $tsQuery = $tokens->map(fn($t) => '"' . str_replace('"', '', $t) . '"')->implode(' | ');


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

        // --- 3. SQL tayyorlash
        $baseSql = "
    SELECT
        v.id, v.title, v.description, v.source, v.external_id, v.category,
        CASE
            WHEN v.category IN ('IT and Software Development', 'Data Science and Analytics', 'QA and Testing', 'DevOps and Cloud Engineering', 'UI/UX and Product Design')
            THEN ts_rank_cd(to_tsvector('simple', coalesce(v.description, '') || ' ' || coalesce(v.title, '')), websearch_to_tsquery('simple', ?))
            ELSE 0
        END AS rank
    FROM vacancies v
    WHERE v.status = 'publish'
      AND v.source = 'telegram'
      AND v.id NOT IN (SELECT vacancy_id FROM match_results WHERE resume_id = ?)
";

        $params = [$tsQuery, $resume->id];

// 🔎 Loglash: tsQuery qanday bo‘lganini ko‘rsatamiz
        Log::info('🔍 [SEARCH QUERY GENERATED]', [
            'tsQuery' => $tsQuery,
            'tokens' => $tokens->all(),
            'phrases' => $phrases->all(),
            'query_variants' => $allVariants->all(),
        ]);

        if ($isTech) {
            // 👇 Agar resume texnik kategoriya bo‘lsa, title orqali qidirish
            $titleCondition = collect($tokens)
                ->map(fn($t) => "LOWER(v.title) LIKE '%" . addslashes(mb_strtolower($t)) . "%'")
                ->implode(' OR ');

            if ($titleCondition) {
                $baseSql .= " AND ($titleCondition)";

                // 🧠 Loglash: title orqali qanday shart yuborilayotganini yozamiz
                Log::info('💻 [TECH MODE] Title orqali qidirish ishlatilmoqda', [
                    'category' => $resumeCategory,
                    'title_condition' => $titleCondition,
                    'tsQuery_used' => $tsQuery,
                ]);
            } else {
                Log::info('💻 [TECH MODE] Tokenlar bo‘sh, title condition yaratilmagan', [
                    'category' => $resumeCategory,
                ]);
            }
        } else {
            // 👇 Texnik bo‘lmasa — category orqali cheklash
            if ($resumeCategory) {
                $baseSql .= " AND v.category = ?";
                $params[] = $resumeCategory;
                Log::info("📊 [CATEGORY FILTER] Resume kategoriyasi ishlatildi", [
                    'category' => $resumeCategory,
                    'tsQuery_used' => $tsQuery,
                ]);
            } elseif ($guessedCategory) {
                $baseSql .= " AND v.category = ?";
                $params[] = $guessedCategory;
                Log::info("📊 [GUESSED CATEGORY USED] AI taxmin qilgan kategoriya ishlatildi", [
                    'guessedCategory' => $guessedCategory,
                    'tsQuery_used' => $tsQuery,
                ]);
            } else {
                Log::info("📊 [CATEGORY FILTER] Hech qanday category filter qo‘llanmagan", [
                    'tsQuery_used' => $tsQuery,
                ]);
            }
        }

        $baseSql .= " ORDER BY rank DESC, id DESC LIMIT 50";

// 🔧 Yakuniy SQL va parametrlarni ham logga yozamiz
        Log::info('🧾 [FINAL SQL BUILT]', [
            'sql' => $baseSql,
            'params' => $params,
        ]);


        // --- 4. ASINXRON so‘rovlar
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

        // --- 5. Local vacancy rank update
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
