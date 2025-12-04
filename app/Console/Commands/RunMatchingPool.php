<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Async\Pool;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Throwable;

class RunMatchingPool extends Command
{
    protected $signature = 'match:pool {resumeId} {query} {tsQuery} {guessedCategory?}';
    protected $description = 'Run Spatie Async Pool for HH and Local vacancies in CLI (parallel) mode';

    public function handle()
    {
        $resumeId = $this->argument('resumeId');
        $query = $this->argument('query');
        $tsQuery = $this->argument('tsQuery');
        $guessedCategory = $this->argument('guessedCategory');

        // Log::info('🚀 [RunMatchingPool] Started', [
        //     'resume' => $resumeId,
        //     'query' => $query,
        //     'tsQuery' => $tsQuery,
        //     'guessedCategory' => $guessedCategory,
        // ]);

        /** @var HHVacancyInterface $hhRepo */
        $hhRepo = app(HHVacancyInterface::class);

        $pool = Pool::create();

        // HH pool
        $pool[] = async(fn() =>
            Cache::remember("hh:search:{$query}:area97", now()->addHour(),
                fn() => $hhRepo->search($query, 0, 100, ['area' => 97])
            )
        )->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);

        // LOCAL pool (buildLocal logic to‘liq)
        $pool[] = async(function () use ($resumeId, $tsQuery, $guessedCategory) {
            $resume = DB::table('resumes')->where('id', $resumeId)->first();
            if (!$resume) return [];

            $tokenArr = []; // agar tokenlar kerak bo‘lsa keyin uzatish mumkin

            $techCategories = [
                "IT and Software Development",
                "Data Science and Analytics",
                "QA and Testing",
                "DevOps and Cloud Engineering",
                "UI/UX and Product Design"
            ];

            $resumeCategory = $resume->category ?? null;

            // Log::info("🔍 [BUILD_LOCAL] Started building query for resume {$resume->id}", [
            //     'resume_category' => $resumeCategory,
            //     'guessed_category' => $guessedCategory,
            //     'tsQuery' => $tsQuery,
            // ]);

            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                });

            if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
               // Log::info("🧠 [TECH BRANCH ENTERED] Resume [{$resume->id}] '{$resumeCategory}' → TECH");
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
                Log::warning("🚫 [NON-TECH BRANCH ENTERED] Resume [{$resume->id}] '{$resumeCategory}' → NON-TECH");
                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw("0 as rank")
                );
            }

            if ($resumeCategory) {
                $countSameCategory = DB::table('vacancies')
                    ->where('status', 'publish')
                    ->where('source', 'telegram')
                    ->where('category', $resumeCategory)
                    ->count();
               // Log::info("📊 [CATEGORY FILTER] '{$resumeCategory}' → {$countSameCategory} vacancies");
                $qb->where('category', $resumeCategory);
            } elseif ($guessedCategory) {
              //  Log::info("📊 [GUESSED CATEGORY USED] '{$guessedCategory}' used for filtering.");
                $qb->where('category', $guessedCategory);
            } else {
               // Log::warning("⚠️ [NO CATEGORY FOUND] No category filter applied!");
            }

            $qb->orderByDesc('rank')->orderByDesc('id');
            $result = $qb->limit(50)->get();

           // Log::info("✅ [BUILD_LOCAL] Finished → Found " . count($result));
            return $result;
        })->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);

        $results = await($pool);

        $hhResult = $results[0] ?? [];
        $localResult = $results[1] ?? [];

        // Log::info('✅ [RunMatchingPool] Finished', [
        //     'resume_id' => $resumeId,
        //     'hh_result_count' => isset($hhResult['items']) ? count($hhResult['items']) : 0,
        //     'local_result_count' => is_countable($localResult) ? count($localResult) : 0,
        //     'hh_error' => $hhResult['error'] ?? null,
        //     'local_error' => $localResult['error'] ?? null,
        // ]);

        $response = [
            'hh' => $hhResult,
            'local' => $localResult,
        ];

        $this->line(json_encode($response));

        return 0;
    }
}
