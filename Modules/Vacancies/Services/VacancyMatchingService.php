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
        Log::info('🚀 Job started', ['resume_id' => $resume->id, 'query' => $query]);
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

// 🔹 Tokenlar va frazalarni aniqlash
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

// 🔹 Kategoriya taxmin qilish
        try {
            $guessedCategory = app(VacancyCategoryService::class)
                ->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            $guessedCategory = (is_string($guessedCategory) && !in_array(mb_strtolower($guessedCategory), ['other', ''], true))
                ? $guessedCategory
                : null;
        } catch (\Throwable) {
            $guessedCategory = null;
        }

// 🔹 HH dan natijalar
        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

// 🔹 Lokal vacancy builder
        $buildLocal = function (bool $withCategory) use ($resume, $tsQuery, $tokens, $guessedCategory) {
            $techCategories = [
                "IT and Software Development",
                "Data Science and Analytics",
                "QA and Testing",
                "DevOps and Cloud Engineering",
                "UI/UX and Product Design"
            ];

            $resumeCategory = $resume->category ?? null;

            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                });

            $isTech = in_array($resumeCategory, $techCategories, true);

            if ($isTech) {
                $qb->where(function ($q) use ($tsQuery, $tokens) {
                    $q->whereRaw("
                to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)
            ", [$tsQuery]);

                    if ($tokens->isNotEmpty()) {
                        $likeTokens = $tokens->take(10)->map(fn($t) => "%{$t}%")->all();
                        $q->orWhere(function ($sub) use ($likeTokens) {
                            foreach ($likeTokens as $pattern) {
                                $sub->orWhere('description', 'ILIKE', $pattern)
                                    ->orWhere('title', 'ILIKE', $pattern);
                            }
                        });
                    }
                });

                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw("ts_rank_cd(to_tsvector('simple', coalesce(description, '')), websearch_to_tsquery('simple', ?)) as rank")
                )->addBinding($tsQuery, 'select');
            } else {
                $qb->select('id', 'title', 'description', 'source', 'external_id', 'category', DB::raw('0 as rank'));
            }

            if ($withCategory) {
                if ($resumeCategory) {
                    $count = DB::table('vacancies')
                        ->where('status', 'publish')
                        ->where('source', 'telegram')
                        ->where('category', $resumeCategory)
                        ->count();
                    Log::info("📊 [CATEGORY] {$resumeCategory} → {$count} vacancies.");
                    $qb->where('category', $resumeCategory);
                } elseif ($guessedCategory) {
                    Log::info("📊 [GUESSED] {$guessedCategory} used.");
                    $qb->where('category', $guessedCategory);
                } else {
                    Log::warning("⚠️ [NO CATEGORY] No category filter applied!");
                }
            }

            Log::info("✅ [BUILD_LOCAL] Resume {$resume->id} (TECH=" . ($isTech ? 'YES' : 'NO') . ")");
            return $qb->orderByDesc('rank')->orderByDesc('id');
        };

        $localVacancies = collect($buildLocal(true)->limit(50)->get())
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

        Log::info('All details took finished: ' . (microtime(true) - $start) . 's');

        Log::info('Matching finished', ['resume_id' => $resume->id]);

        return $savedData;
    }
}
