<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Throwable;

class RunMatchingSequential extends Command
{
    protected $signature = 'match:sequential {resumeId} {query} {tsQuery} {guessedCategory?}';
    protected $description = 'Run HH and Local vacancies search sequentially (no parallel)';

    public function handle()
    {
        $resumeId = $this->argument('resumeId');
        $query = $this->argument('query');
        $tsQuery = $this->argument('tsQuery');
        $guessedCategory = $this->argument('guessedCategory');

        // Log::info('🚀 [RunMatchingSequential] Started', [
        //     'resume_id' => $resumeId,
        //     'query' => $query,
        //     'tsQuery' => $tsQuery,
        //     'guessedCategory' => $guessedCategory,
        // ]);

        /** @var HHVacancyInterface $hhRepo */
        $hhRepo = app(HHVacancyInterface::class);

        $techCategories = [
            "IT and Software Development",
            "Data Science and Analytics",
            "QA and Testing",
            "DevOps and Cloud Engineering",
            "UI/UX and Product Design"
        ];

        $hhResult = [];
        $localResult = [];

        // 🧩 1. HH Vacancies
        try {
           // Log::info('[SEQ] Fetching HH vacancies...');
            $hhResult = Cache::remember("hh:search:{$query}:area97", now()->addHour(), function () use ($hhRepo, $query) {
                return $hhRepo->search($query, 0, 100, ['area' => 97]);
            });

            $hhCount = isset($hhResult['items']) ? count($hhResult['items']) : 0;
           // Log::info("[SEQ] HH vacancies found: {$hhCount}");
        } catch (Throwable $e) {
            Log::error('[SEQ] HH fetch error: ' . $e->getMessage());
            $hhResult = ['error' => $e->getMessage()];
        }

        // 🧩 2. Local Vacancies
        try {
            // Log::info('[SEQ] Fetching Local vacancies...');
            $resume = DB::table('resumes')->where('id', $resumeId)->first();
            if (!$resume) {
                Log::warning("[SEQ] Resume not found: {$resumeId}");
                $localResult = [];
            } else {
                $resumeCategory = $resume->category ?? null;

                $qb = DB::table('vacancies')
                    ->where('status', 'publish')
                    ->where('source', 'telegram')
                    ->whereNotIn('id', function ($q) use ($resumeId) {
                        $q->select('vacancy_id')
                            ->from('match_results')
                            ->where('resume_id', $resumeId);
                    });

                if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
                    $qb->whereRaw("to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)", [$tsQuery]);
                }

                if ($resumeCategory) {
                    $qb->where('category', $resumeCategory);
                } elseif ($guessedCategory) {
                    $qb->where('category', $guessedCategory);
                }

                $localResult = $qb->orderByDesc('id')->limit(50)->get();
               // Log::info('[SEQ] Local vacancies found: ' . count($localResult));
            }
        } catch (Throwable $e) {
            Log::error('[SEQ] Local fetch error: ' . $e->getMessage());
            $localResult = ['error' => $e->getMessage()];
        }

        // 🧾 3. Natija
        $response = [
            'hh' => $hhResult,
            'local' => $localResult,
        ];

        $hhCount = isset($hhResult['items']) ? count($hhResult['items']) : 0;
        $localCount = is_countable($localResult) ? count($localResult) : 0;

        // Log::info('✅ [RunMatchingSequential] Finished', [
        //     'resume_id' => $resumeId,
        //     'hh_result_count' => $hhCount,
        //     'local_result_count' => $localCount,
        //     'hh_error' => $hhResult['error'] ?? null,
        //     'local_error' => $localResult['error'] ?? null,
        // ]);

        $this->line(json_encode($response));

        return 0;
    }
}
