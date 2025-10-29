<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

        $pool[] = async(fn() =>
            Cache::remember("hh:search:{$query}:area97", now()->addHour(), 
                fn() => $hhRepo->search($query, 0, 100, ['area' => 97])
            )
        )->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);

        $pool[] = async(function () use ($resumeId, $tsQuery, $guessedCategory, $techCategories) {
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

            return $qb->orderByDesc('id')->limit(50)->get();
        })->catch(fn(Throwable $e) => ['error' => $e->getMessage()]);

        $results = await($pool);

        $response = [
            'hh' => $results[0] ?? [],
            'local' => $results[1] ?? [],
        ];

        $this->line(json_encode($response));

        return 0;
    }
}
