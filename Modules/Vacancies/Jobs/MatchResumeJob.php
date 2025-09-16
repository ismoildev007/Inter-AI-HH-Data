<?php

namespace Modules\Vacancies\Jobs;

use App\Models\Resume;
use App\Models\MatchResult;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Vacancies\Interfaces\VacancyInterface;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class MatchResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Resume $resume;
    public string $query;

    public function __construct(Resume $resume, string $query)
    {
        $this->resume = $resume;
        $this->query = $query;
    }

    public function handle(VacancyInterface $vacancyRepository, HHVacancyInterface $hhRepository): void
    {
        Log::info('Job started for resume', ['resume_id' => $this->resume->id]);

        $cacheKey = "hh:search:{$this->query}:area97";
        $hhVacancies = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($hhRepository) {
            return $hhRepository->search($this->query, 0, 40, ['area' => 97]);
        });

        $vacancies = $hhVacancies['items'] ?? [];
        if (empty($vacancies)) {
            Log::info('No HH vacancies found', ['query' => $this->query]);
            return;
        }

        $vacancyTexts = [];
        foreach ($vacancies as $v) {
            if (empty($v['id'])) {
                continue;
            }

            $full = cache()->remember("hh:vacancy:{$v['id']}", now()->addHours(6), function () use ($hhRepository, $v) {
                return $hhRepository->getById($v['id']);
            });

            if (!empty($full['description'])) {
                $vacancyTexts[] = strip_tags($full['description']);
            }
        }

        if (empty($vacancyTexts)) {
            return;
        }

        $url = config('services.matcher.url', 'https://python.inter-ai.uz/bulk-match');
        $response = Http::timeout(30)->post($url, [
            'resumes'   => [$this->resume->parsed_text ?? $this->resume->description],
            'vacancies' => $vacancyTexts,
            'top_k'     => 20,
            'min_score' => 0,
        ]);

        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $this->resume->id, 'body' => $response->body()]);
            return;
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];

        $savedData = [];
        $matchedVacancies = [];
        foreach ($matches as $match) {
            if ($match['score'] >= 70 && isset($vacancies[$match['vacancy_index']])) {
                $matchedVacancies[] = $vacancies[$match['vacancy_index']];
            }
        }

        if (!empty($matchedVacancies)) {
            $vacancyMap = $vacancyRepository->bulkUpsertFromHH($matchedVacancies);

            $savedData = [];
            foreach ($matches as $match) {
                if ($match['score'] >= 70) {
                    $vData = $vacancies[$match['vacancy_index']] ?? null;
                    if (!$vData) continue;

                    $vacancy = $vacancyMap[$vData['id']] ?? null;
                    if (!$vacancy) continue;

                    $savedData[] = [
                        'resume_id'     => $this->resume->id,
                        'vacancy_id'    => $vacancy->id,
                        'score_percent' => $match['score'],
                        'explanations'  => json_encode($match),
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ];
                }
            }

            DB::table('match_results')->upsert(
                $savedData,
                ['resume_id', 'vacancy_id'],
                ['score_percent', 'explanations', 'updated_at']
            );
        }


        Log::info('Job finished for resume', ['resume_id' => $this->resume->id]);
    }
}
