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
use GuzzleHttp\Promise;

class NotificationMatchingService
{
    protected VacancyInterface $vacancyRepository;
    protected HHVacancyInterface $hhRepository;

    public function __construct(
        VacancyInterface   $vacancyRepository,
        HHVacancyInterface $hhRepository
    ) {
        $this->vacancyRepository = $vacancyRepository;
        $this->hhRepository = $hhRepository;
    }

    public function matchResume(Resume $resume, $query): array
    {
        Log::info('🚀 Job started', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        // $linkedinTitle = explode(',', $resume->title ?? '')[0] ?? '';
        // $linkedinQuery = !empty($linkedinTitle) ? $linkedinTitle : $query;
        $resumeCategory = $resume->category ?? null;

        // $linkedinVacanciesForUser = collect();

        // try {
        //     $linkedinService = app(\Modules\JobSources\Services\LinkedinService::class);
        //     $linkedinResponse = $linkedinService->fetchLinkedinJobs($linkedinQuery, '91000000');
        //     $linkedinJobs = collect($linkedinResponse['data'] ?? []);

        //     $saveStats = $linkedinService->saveToDatabase($linkedinJobs->all());

        //     $allExternalIds = $linkedinJobs
        //         ->map(fn($job) => $linkedinService->extractExternalId($job['link'] ?? ''))
        //         ->filter()
        //         ->values();

        //     $allVacancies = Vacancy::where('source', 'linkedin')
        //         ->whereIn('external_id', $allExternalIds)
        //         ->get()
        //         ->keyBy('external_id');

        //     $alreadyGiven = DB::table('match_results as mr')
        //         ->join('vacancies as v', 'v.id', '=', 'mr.vacancy_id')
        //         ->where('mr.resume_id', $resume->id)
        //         ->where('v.source', 'linkedin')
        //         ->pluck('v.external_id')
        //         ->toArray();

        //     $onlyNew = $allExternalIds
        //         ->reject(fn($id) => in_array($id, $alreadyGiven))
        //         ->map(fn($id) => $allVacancies->get($id))
        //         ->filter()
        //         ->values();

        //     $linkedinVacanciesForUser = $onlyNew->take(10);

        //     if ($linkedinVacanciesForUser->count() < 10) {
        //         $needed = 10 - $linkedinVacanciesForUser->count();

        //         $fallback = $allExternalIds
        //             ->map(fn($id) => $allVacancies->get($id))
        //             ->filter()
        //             ->reject(fn($vac) => in_array($vac->external_id, $alreadyGiven))
        //             ->skip(10) 
        //             ->take($needed);

        //         $linkedinVacanciesForUser = $linkedinVacanciesForUser
        //             ->merge($fallback)
        //             ->take(10);
        //     }

        //     Log::info("LinkedIn final count for user: " . $linkedinVacanciesForUser->count());
        // } catch (\Throwable $e) {
        //     Log::error("LinkedIn error: " . $e->getMessage());
        // }


        // Translation va token generation
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

        $cleanAndSplit = function ($variants) {
            return $variants
                ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
                ->map(fn($w) => mb_strtolower(trim(preg_replace('/[\"\'«»""]/u', '', $w)), 'UTF-8'));
        };

        $cleaned = $cleanAndSplit($allVariants);

        $tokens = $cleaned
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->unique()
            ->take(8)
            ->values();

        Log::info('🧩 Tokens parsed', ['tokens' => $tokens->all()]);

        $phrases = $cleaned
            ->filter(fn($s) => mb_strlen($s) >= 3 && str_contains($s, ' '))
            ->unique()
            ->take(4)
            ->values();

        // TS Query generation
        $searchQuery = $latinQuery ?: $cyrilQuery;
        $tsTerms = [...$phrases, ...$tokens];
        $mustPair = count($tokens) >= 2 ? ['(' . $tokens[0] . ' ' . $tokens[1] . ')'] : [];
        $webParts = array_merge($mustPair, $tsTerms);

        $tsQuery = !empty($webParts)
            ? implode(' OR ', array_map(fn($t) => str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t, $webParts))
            : (string) $searchQuery;

        // Category guessing
        try {
            $guessedCategory = app(VacancyCategoryService::class)
                ->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            $guessedCategory = (is_string($guessedCategory) && !in_array(mb_strtolower($guessedCategory), ['other', ''], true))
                ? $guessedCategory
                : null;
        } catch (\Throwable) {
            $guessedCategory = null;
        }

        // HH vacancies caching (synchronous)
        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );
        $resumeCategory = $resume->category ?? null;
        Log::info("Guessed category notification: " . ($guessedCategory ?? 'none'));

        // Local vacancies builder
        $buildLocal = function () use ($resume, $tsQuery, $tokens, $guessedCategory) {
            $techCategories = [
                "IT and Software Development",
                "Data Science and Analytics",
                "QA and Testing",
                "DevOps and Cloud Engineering",
                "UI/UX and Product Design"
            ];

            $resumeCategory = $resume->category ?? null;
            $isTech = in_array($resumeCategory, $techCategories, true);

            // Base query
            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                });

            $tsVectorSql = "
        setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
        setweight(to_tsvector('simple', coalesce(description, '')), 'B')
    ";

            // Extract skills helper
            $extractSkills = fn($text) => collect(preg_split('/\s*,\s*/u', (string) $text))
                ->map(fn($s) => trim($s))
                ->filter(fn($s) => mb_strlen($s) > 2)
                ->unique()
                ->values();

            // Resume title skills
            $extraSkills = $extractSkills($resume->title ?? '');

            // LIKE conditions helper
            $addLikeConditions = function ($query, $items) {
                foreach ($items as $item) {
                    $pattern = "%{$item}%";
                    $query->orWhere('title', 'ILIKE', $pattern)
                        ->orWhere('description', 'ILIKE', $pattern);
                }
            };

            if ($isTech) {
                // Tech: Category-focused search
                $categoriesForSearch = collect([$resumeCategory, $guessedCategory])
                    ->filter()
                    ->unique()
                    ->whenEmpty(fn($c) => collect($techCategories))
                    ->all();

                $qb->whereIn('category', $categoriesForSearch)
                    ->where(function ($q) use ($tsQuery, $tokens, $extraSkills, $tsVectorSql, $addLikeConditions) {
                        // 1) Full-text search
                        $q->whereRaw("$tsVectorSql @@ websearch_to_tsquery('simple', ?)", [$tsQuery]);

                        // 2) Token LIKE search
                        if ($tokens->isNotEmpty()) {
                            $q->orWhere(function ($sub) use ($tokens, $addLikeConditions) {
                                $addLikeConditions($sub, $tokens->take(10));
                            });
                        }

                        // 3) Skill-based search (from resume title)
                        if ($extraSkills->isNotEmpty()) {
                            $q->orWhere(function ($sub) use ($extraSkills, $addLikeConditions) {
                                $addLikeConditions($sub, $extraSkills);
                            });
                            Log::info('🧠 [TECH SKILL SEARCH]', ['skills' => $extraSkills->all()]);
                        }
                    })
                    ->select(
                        'id',
                        'title',
                        'description',
                        'source',
                        'external_id',
                        'category',
                        DB::raw("ts_rank_cd($tsVectorSql, websearch_to_tsquery('simple', ?)) as rank")
                    )
                    ->addBinding($tsQuery, 'select');

                Log::info("💡 [TECH SEARCH]", [
                    'categories' => $categoriesForSearch,
                    'tokens' => $tokens->all(),
                ]);
            } else {
                // Non-Tech: Broader search with category preference
                $qb->select('id', 'title', 'description', 'source', 'external_id', 'category', DB::raw('0 as rank'))
                    ->where(function ($main) use ($tokens, $tsQuery, $resumeCategory, $extraSkills, $addLikeConditions) {

                        // 1) Category match (if exists)
                        if (!empty($resumeCategory)) {
                            $main->orWhere('category', $resumeCategory);
                            Log::info("📂 [NON-TECH CATEGORY] {$resumeCategory}");
                        }

                        // 2) Full-text search on description
                        $main->orWhereRaw("
                   to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)
               ", [$tsQuery]);

                        // 3) Token LIKE search
                        if ($tokens->isNotEmpty()) {
                            $main->orWhere(function ($q) use ($tokens, $addLikeConditions) {
                                $addLikeConditions($q, $tokens);
                            });
                            Log::info('🔎 [NON-TECH TOKEN SEARCH]', ['tokens' => $tokens->all()]);
                        }

                        // 4) Skill-based search (from resume title)
                        if ($extraSkills->isNotEmpty()) {
                            $main->orWhere(function ($q) use ($extraSkills, $addLikeConditions) {
                                $addLikeConditions($q, $extraSkills);
                            });
                            Log::info('🧠 [NON-TECH SKILL SEARCH]', ['skills' => $extraSkills->all()]);
                        }
                    });

                Log::info("✅ [NON-TECH SEARCH] Resume {$resume->id}");
            }

            return $qb->orderByDesc('rank')->orderByDesc('id');
        };

        // Execute query (synchronous, no promises)
        $localVacancies = collect($buildLocal()->limit(10)->get())
            ->take(10)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");

        Log::info('✅ [SEARCH COMPLETED]', [
            'hh_count' => count($hhVacancies),
            'local_count' => $localVacancies->count(),
            'resume_id' => $resume->id,
        ]);


        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];

        $existingHhExternalIds = DB::table('match_results as mr')
            ->join('vacancies as v', 'v.id', '=', 'mr.vacancy_id')
            ->where('mr.resume_id', $resume->id)
            ->where('v.source', 'hh')
            ->pluck('v.external_id')
            ->filter()
            ->all();
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
                // 'title' => $v->title,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }

        // foreach ($linkedinVacanciesForUser as $v) {
        //     $vacanciesPayload[] = [
        //         'id'          => $v->id,
        //         'vacancy_id'  => $v->id,
        //         'text'        => mb_substr(strip_tags($v->description), 0, 2000),
        //         'source'      => 'linkedin'
        //     ];
        // }

        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id'])
                && !$localVacancies->has($item['id'])
                && !in_array($item['id'], $existingHhExternalIds, true))
            ->take(10);
        foreach ($toFetch as $idx =>  $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) {
                continue;
            }
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

                    Log::info("resume category notification: " . $resumeCategory);

                    if (!$vac && isset($match['raw'])) {
                        // Use bulk HH categorization logic (rule-based) instead of forcing resume category
                        $vac = $this->vacancyRepository->firstOrCreateFromHH($match['raw']);
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
