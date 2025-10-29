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
        Log::info([
            'resume' => $resumeId,
            'query'  => $query,
            'tsQuesry' => $tsQuery,
            'guest category' => $guessedCategory
        ]);
        /** @var HHVacancyInterface $hhRepo */
        $hhRepo = app(HHVacancyInterface::class);

        $techCategories = [
            "IT and Software Development",
            "Data Science and Analytics",
            "QA and Testing",
            "DevOps and Cloud Engineering",
            "UI/UX and Product Design"
        ];

        $pool = Pool::create();

        $pool[] = async(function () use ($query, $hhRepo) {
            Log::info('[Pool] Running HH search...');
            $result = $hhRepo->search($query, 0, 100, ['area' => 97]);
            Log::info('[Pool] HH search done', ['count' => count($result['items'] ?? [])]);
            Cache::put("hh:search:{$query}:area97", $result, now()->addHour());
            return $result;
        })->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);
        

        $pool[] = async(function () use ($resumeId, $tsQuery, $guessedCategory, $techCategories) {
            Log::info('[Pool] Running local vacancy query...');

            $resume = DB::table('resumes')->where('id', $resumeId)->first();
            if (!$resume) return [];

            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resumeId) {
                    $q->select('vacancy_id')->from('match_results')->where('resume_id', $resumeId);
                });

            $resumeCategory = $resume->category ?? null;

            if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
                $qb->whereRaw("to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)", [$tsQuery]);
            }

            if ($resumeCategory) {
                $qb->where('category', $resumeCategory);
            } elseif ($guessedCategory) {
                $qb->where('category', $guessedCategory);
            }
            $result = $qb->orderByDesc('id')->limit(50)->get();
            Log::info('[Pool] Local query done', ['count' => count($result)]);
            return $result;
        })->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);

        $results = await($pool);
        $hhResult = $results[0] ?? [];
        $localResult = $results[1] ?? [];

        $hhCount = is_array($hhResult) && isset($hhResult['items'])
            ? count($hhResult['items'])
            : (is_countable($hhResult) ? count($hhResult) : 0);

        $localCount = is_countable($localResult) ? count($localResult) : 0;

        Log::info('✅ [RunMatchingPool] Finished', [
            'resume_id' => $resumeId,
            'hh_result_count' => $hhCount,
            'local_result_count' => $localCount,
            'hh_error' => $hhResult['error'] ?? null,
            'local_error' => $localResult['error'] ?? null,
        ]);

        $response = [
            'hh' => $hhResult,
            'local' => $localResult,
        ];
        // $response = [
        //     'hh' => $results[0] ?? [],
        //     'local' => $results[1] ?? [],
        // ];

        $this->line(json_encode($response));

        return 0;
    }
}
