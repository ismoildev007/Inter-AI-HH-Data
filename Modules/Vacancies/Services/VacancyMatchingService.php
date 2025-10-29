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
use Spatie\Async\Pool;
use App\Helpers\TranslitHelper;
use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;
use Modules\TelegramChannel\Services\VacancyCategoryService;

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
    // public function matchResume(Resume $resume, $query): array
    // {
    //     Log::info('🚀 Optimized matching started', ['resume_id' => $resume->id, 'query' => $query]);
    //     $start = microtime(true);

    //     $latinQuery = TranslitHelper::toLatin($query);
    //     $cyrilQuery = TranslitHelper::toCyrillic($query);

    //     $translations = Cache::remember("translations:{$query}", now()->addHours(2), function () use ($query) {
    //         $t = new GoogleTranslate();
    //         $t->setSource('auto');
    //         return [
    //             'uz' => $t->setTarget('uz')->translate($query),
    //             'ru' => $t->setTarget('ru')->translate($query),
    //             'en' => $t->setTarget('en')->translate($query),
    //         ];
    //     });

    //     $allVariants = collect([$query, ...array_values($translations)])
    //         ->unique()
    //         ->filter()
    //         ->values();

    //     $tokens = $allVariants
    //         ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
    //         ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
    //         ->filter(fn($w) => mb_strlen($w) >= 2)
    //         ->map(fn($w) => mb_strtolower($w, 'UTF-8'))
    //         ->unique()
    //         ->take(8)
    //         ->values();

    //     $tokenArr = $tokens->all();

    //     $phrases = $allVariants
    //         ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
    //         ->filter(fn($s) => mb_strlen($s) >= 3 && preg_match('/\s/u', $s))
    //         ->map(fn($s) => mb_strtolower($s, 'UTF-8'))
    //         ->unique()
    //         ->take(4)
    //         ->values();

    //     $tsTerms = array_merge($phrases->all(), $tokens->all());
    //     $mustPair = count($tokens) >= 2 ? ['(' . $tokens[0] . ' ' . $tokens[1] . ')'] : [];
    //     $webParts = array_merge($mustPair, $tsTerms);

    //     $tsQuery = !empty($webParts)
    //         ? implode(' OR ', array_map(fn($t) => str_contains($t, ' ') ? '"' . $t . '"' : $t, $webParts))
    //         : trim((string) ($latinQuery ?: $cyrilQuery));

    //     try {
    //         $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
    //         $guessedCategory = $categorizer->categorize('', $resume->title ?? '', $resume->description ?? '', '');
    //     } catch (Throwable) {
    //         $guessedCategory = null;
    //     }

    //     // $techCategories = [
    //     //     "IT and Software Development",
    //     //     "Data Science and Analytics",
    //     //     "QA and Testing",
    //     //     "DevOps and Cloud Engineering",
    //     //     "UI/UX and Product Design"
    //     // ];

    //     // $pool = \Spatie\Async\Pool::create();

    //     // $pool[] = async(
    //     //     fn() =>
    //     //     Cache::remember(
    //     //         "hh:search:{$query}:area97",
    //     //         now()->addHour(),
    //     //         fn() =>
    //     //         $this->hhRepository->search($query, 0, 100, ['area' => 97])
    //     //     )
    //     // );

    //     // 🧠 Endi poolni CLI’da ishga tushiramiz
    //     $cmd = sprintf(
    //         '/usr/bin/php %s match:sequential %d %s %s %s',
    //         base_path('artisan'),
    //         $resume->id,
    //         escapeshellarg($query),
    //         escapeshellarg($tsQuery),
    //         escapeshellarg($guessedCategory ?? '')
    //     );

    //     Log::info('⚙️ Running async sequential command', ['cmd' => $cmd]);

    //     exec($cmd . ' 2>&1', $output, $exitCode);

    //     if ($exitCode !== 0) {
    //         Log::error('❌ Pool command failed', [
    //             'code' => $exitCode,
    //             'output' => implode("\n", $output),
    //         ]);
    //         return [];
    //     }

    //     $json = implode("\n", $output);
    //     $data = json_decode($json, true);

    //     $hhVacancies = $data['hh'] ?? [];
    //     $localVacancies = $data['local'] ?? [];
    //     Log::info(['count hh ' => count($hhVacancies), 'local vacan' => count($localVacancies)]);


    //     // $pool[] = async(
    //     //     fn() =>
    //     //     Cache::remember("local:vacancies:{$resume->category}:" . md5($tsQuery), now()->addMinutes(15), function () use ($resume, $tsQuery, $tokenArr, $guessedCategory, $techCategories) {
    //     //         $qb = DB::table('vacancies')
    //     //             ->where('status', 'publish')
    //     //             ->where('source', 'telegram')
    //     //             ->whereNotIn('id', function ($q) use ($resume) {
    //     //                 $q->select('vacancy_id')->from('match_results')->where('resume_id', $resume->id);
    //     //             });

    //     //         $resumeCategory = $resume->category ?? null;

    //     //         if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
    //     //             $qb->whereRaw("to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)", [$tsQuery]);
    //     //         }

    //     //         if ($resumeCategory) {
    //     //             $qb->where('category', $resumeCategory);
    //     //         } elseif ($guessedCategory) {
    //     //             $qb->where('category', $guessedCategory);
    //     //         }

    //     //         return $qb->orderByDesc('id')->limit(50)->get();
    //     //     })
    //     // );

    //     // [$hhVacancies, $localVacancies] = await($pool);

    //     $hhItems = $hhVacancies['items'] ?? [];
    //     $vacanciesPayload = collect($localVacancies)
    //         ->take(50)
    //         ->map(fn($v) => [
    //             'vacancy_id' => $v->id,
    //             'text' => mb_substr(strip_tags($v->description), 0, 2000),
    //         ])->values();

    //     foreach ($hhItems as $idx => $item) {
    //         $extId = $item['id'] ?? null;
    //         $text = trim(($item['snippet']['requirement'] ?? '') . "\n" . ($item['snippet']['responsibility'] ?? ''));
    //         if ($extId && $text) {
    //             $vacanciesPayload->push([
    //                 'external_id' => $extId,
    //                 'text' => mb_substr(strip_tags($text), 0, 1000),
    //                 'vacancy_index' => $idx,
    //             ]);
    //         }
    //     }

    //     $savedData = $vacanciesPayload->map(fn($m) => [
    //         'resume_id' => $resume->id,
    //         'vacancy_id' => $m['vacancy_id'] ?? null,
    //         'score_percent' => $m['score'] ?? 0,
    //         'explanations' => json_encode($m),
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ])->chunk(50);
    //     Log::info(['count' => count($savedData)]);

    //     foreach ($savedData as $chunk) {
    //         DB::table('match_results')->upsert($chunk->toArray(), ['resume_id', 'vacancy_id'], ['score_percent', 'explanations', 'updated_at']);
    //     }

    //     Log::info('✅ Optimized matching finished in ' . round(microtime(true) - $start, 2) . 's');

    //     return $savedData->flatten(1)->toArray();
    // }


    public function matchResume(Resume $resume, $query): array
    {
        Log::info('Job started for resume', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        $latinQuery = TranslitHelper::toLatin($query);
        $cyrilQuery = TranslitHelper::toCyrillic($query);

        $words = array_map('trim', explode(',', $query));

        $translator = new GoogleTranslate();
        $translator->setSource('auto');
        $translator->setTarget('uz');
        $uzQuery = $translator->translate("\"{$query}\"");

        $translator->setTarget('ru');
        $ruQuery = $translator->translate("\"{$query}\"");

        $translator->setTarget('en');
        $enQuery = $translator->translate("\"{$query}\"");

        $allVariants = collect([$query, $uzQuery, $ruQuery, $enQuery])
            ->unique()
            ->filter()
            ->values();

        $tokens = $allVariants
            // faqat vergul orqali bo‘lamiz
            ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
            ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->map(fn($w) => mb_strtolower($w, 'UTF-8'))
            ->unique()
            ->take(8)
            ->values();

        $tokenArr = $tokens->all();

        Log::info('🧩 Tokens parsed by comma only', ['tokens' => $tokenArr]);

        $phrases = $allVariants
            ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
            ->map(fn($s) => trim($s))
            ->filter(fn($s) => mb_strlen($s) >= 3 && preg_match('/\s/u', $s))
            ->map(fn($s) => mb_strtolower($s, 'UTF-8'))
            ->unique()
            ->take(4)
            ->values();
        $phraseArr = $phrases->all();

        $tokensByLen = $tokens->sortByDesc(fn($t) => mb_strlen($t, 'UTF-8'))->values();
        $mustAnd = array_slice($tokensByLen->all(), 0, 2);

        Log::info('Searching vacancies for terms', ['terms' => $allVariants->all(), 'tokens' => $tokenArr]);

        $searchQuery = $latinQuery ?: $cyrilQuery;

        $tsTerms = array_merge(
            array_map('trim', array_map('strval', $phraseArr)),
            array_map('trim', array_map('strval', $tokenArr))
        );
        $mustPair = [];
        if (count($tokenArr) >= 2) {
            $mustPair = ['(' . $tokenArr[0] . ' ' . $tokenArr[1] . ')']; // websearch: space = AND
        }
        $webParts = array_merge($mustPair, $tsTerms);

        $tsQuery = !empty($webParts)
            ? implode(' OR ', array_map(function ($t) {
                return str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t;
            }, $webParts))
            : trim((string) $searchQuery);

        $guessedCategory = null;
        try {
            /** @var VacancyCategoryService $categorizer */
            $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
            $guessedCategory = $categorizer->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            if (!is_string($guessedCategory) || mb_strtolower($guessedCategory, 'UTF-8') === 'other' || $guessedCategory === '') {
                $guessedCategory = null;
            }
        } catch (\Throwable $e) {
            $guessedCategory = null;
        }

        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

        $buildLocal = function (bool $withCategory) use ($resume, $tsQuery, $tokenArr, $guessedCategory) {
            // IT sohalar ro‘yxati
            $techCategories = [
                "IT and Software Development",
                "Data Science and Analytics",
                "QA and Testing",
                "DevOps and Cloud Engineering",
                "UI/UX and Product Design"
            ];

            $resumeCategory = $resume->category ?? null;

            Log::info("🔍 [BUILD_LOCAL] Started building query for resume {$resume->id}", [
                'resume_category' => $resumeCategory,
                'guessed_category' => $guessedCategory,
                'tsQuery' => $tsQuery,
                'tokens' => $tokenArr,
            ]);

            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                });

            // 🔸 Log da aniqlaymiz: resumeCategory TECH ro‘yxatida bormi?
            if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
                Log::info("🧠 [TECH BRANCH ENTERED] Resume [{$resume->id}] category '{$resumeCategory}' is TECH. Running full text + token search.");

                $qb->where(function ($query) use ($tsQuery, $tokenArr) {
                    $query->whereRaw("
                        to_tsvector('simple', coalesce(description, ''))
                        @@ websearch_to_tsquery('simple', ?)
                    ", [$tsQuery]);

                    if (!empty($tokenArr)) {
                        $top = array_slice($tokenArr, 0, min(10, count($tokenArr)));
                        $query->orWhere(function ($q) use ($top) {
                            foreach ($top as $t) {
                                $pattern = "%{$t}%";
                                $q->orWhere('description', 'ILIKE', $pattern)
                                    ->orWhere('title', 'ILIKE', $pattern);
                            }
                        });
                    }
                });

                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw("
                ts_rank_cd(
                    to_tsvector('simple', coalesce(description, '')),
                    websearch_to_tsquery('simple', ?)
                ) as rank
            ")
                )->addBinding($tsQuery, 'select');
            } else {
                Log::warning("🚫 [NON-TECH BRANCH ENTERED] Resume [{$resume->id}] category '{$resumeCategory}' is NON-TECH or unknown → no search applied.");

                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw("0 as rank")
                );
            }

            // 🔸 Category filter log
            if ($withCategory) {
                if ($resumeCategory) {
                    $countSameCategory = DB::table('vacancies')
                        ->where('status', 'publish')
                        ->where('source', 'telegram')
                        ->where('category', $resumeCategory)
                        ->count();

                    Log::info("📊 [CATEGORY FILTER] Resume [{$resume->id}] '{$resumeCategory}' → {$countSameCategory} total vacancies in DB.");
                    $qb->where('category', $resumeCategory);
                } elseif ($guessedCategory) {
                    Log::info("📊 [GUESSED CATEGORY USED] '{$guessedCategory}' used for filtering.");
                    $qb->where('category', $guessedCategory);
                } else {
                    Log::warning("⚠️ [NO CATEGORY FOUND] No category filter applied!");
                }
            }

            Log::info("✅ [BUILD_LOCAL] Finished building query for resume {$resume->id} (TECH=" . (in_array($resumeCategory, $techCategories, true) ? 'YES' : 'NO') . ")");

            return $qb->orderByDesc('rank')->orderByDesc('id');
        };



        $techCategories = [
            "IT and Software Development",
            "Data Science and Analytics",
            "QA and Testing",
            "DevOps and Cloud Engineering",
            "UI/UX and Product Design"
        ];

        $localVacancies = $buildLocal(true)->limit(50)->get();

        $resumeCategory = $resume->category ?? null;

        if ($resumeCategory && !in_array($resumeCategory, $techCategories, true)) {

            $currentCount = $localVacancies->count();
            $limit = 50; // umumiy kerakli son
            $need = max(0, $limit - $currentCount); // nechta yetmayapti

            if ($need > 0) {
                $fallback = DB::table('vacancies')
                    ->where('status', 'publish')
                    ->where('source', 'telegram')
                    ->where('category', $resumeCategory)
                    ->whereNotIn('id', $localVacancies->pluck('id')->toArray()) // dublikatni oldini oladi
                    ->limit($need)
                    ->get();

                Log::info("⚠️ Low match ($currentCount found). Added {$fallback->count()} fallback vacancies (total target = {$limit}) from category '{$resumeCategory}'.");

                $localVacancies = $localVacancies->concat($fallback)->unique('id');
            } else {
                Log::info("✅ Enough results ($currentCount) found for '{$resumeCategory}', fallback not needed.");
            }
        } else {
            Log::info("✅ Resume category '{$resumeCategory}' is TECH → fallback disabled.");
        }

        $localVacancies = collect($localVacancies)
            ->take(50)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");



        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'vacancy_id'   => $v->id,
                // 'title' => $v->title,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        Log::info(['local vacancies' => $vacanciesPayload]);
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(50);
        foreach ($toFetch as $idx =>  $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) {
                continue;
            }
            // $title = $item['name'] ?? 'No title';
            $text = ($item['snippet']['requirement'] ?? '') . "\n" .
                ($item['snippet']['responsibility'] ?? '');

            if (!empty(trim($text))) {
                $vacanciesPayload[] = [
                    'id'          => null,
                    // 'title'       => mb_substr(strip_tags($title), 0, 200),
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
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

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
                    Log::info('⚙️ Local vacancy not found via model, saving manually', [
                        'vacancy_id' => $vacId,
                    ]);

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
            DB::table('match_results')->upsert(
                $savedData,
                ['resume_id', 'vacancy_id'],
                ['score_percent', 'explanations', 'updated_at']
            );
        }
        Log::info('All details took finished: ' . (microtime(true) - $start) . 's');

        Log::info('Matching finished', ['resume_id' => $resume->id]);

        return $savedData;
    }
}
