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
        Log::info(['resume info' => $this->query]);
        Log::info('Job started for resume', ['resume_id' => $this->resume->id]);

        // --- Fetch from HH ---
        $cacheKey = "hh:search:{$this->query}:area97";
        $hhVacancies = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($hhRepository) {
            return $hhRepository->search($this->query, 0, 100, ['area' => 97]);
        });

        $hhItems = $hhVacancies['items'] ?? [];
        if (empty($hhItems)) {
            Log::info('No HH vacancies found', ['query' => $this->query]);
        }

        // --- Fetch from local DB (own vacancies) ---
        $localVacancies = \App\Models\Vacancy::where('title', 'like', "%{$this->query}%")
            ->get()
            ->keyBy(function ($v) {
                return $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}";
            });

        // --- Prepare merged vacancies ---
        $vacanciesPayload = [];

        // Add local vacancies first
        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,  // local DB id
                'text' => strip_tags($v->description),
            ];
        }

        // Add HH vacancies only if not already in local
        foreach ($hhItems as $item) {
            $extId = $item['id'] ?? null;
            if (!$extId) {
                continue;
            }

            if ($localVacancies->has($extId)) {
                // Skip duplicate, already in DB
                continue;
            }

            $full = cache()->remember("hh:vacancy:{$extId}", now()->addHours(6), function () use ($hhRepository, $item) {
                return $hhRepository->getById($item['id']);
            });

            if (!empty($full['description'])) {
                $vacanciesPayload[] = [
                    'id'   => null, // not in DB yet
                    'text' => strip_tags($full['description']),
                    'external_id' => $extId,
                    'raw'  => $full, // keep raw in case we need to save later
                ];
            }
        }

        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $this->resume->id]);
            return;
        }

        // --- Send to Python matcher ---
        $url = config('services.matcher.url', 'http://0.0.0.0:8080/bulk-match-fast');
        $response = Http::timeout(60)->post($url, [
            'resumes'   => [$this->resume->parsed_text ?? $this->resume->description],
            'vacancies' => array_map(fn($v) => ['id' => $v['id'], 'text' => $v['text']], $vacanciesPayload),
            'top_k'     => 50,
            'min_score' => 0,
        ]);

        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $this->resume->id, 'body' => $response->body()]);
            return;
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];

        // --- Map extId to full vacancy payload (for later save if needed) ---
        $vacancyMap = collect($vacanciesPayload)->keyBy(function ($v, $k) {
            return $v['id'] ?? "new_{$k}";
        });

        // --- Save match results ---
        $savedData = [];
        foreach ($matches as $match) {
            if ($match['score'] >= 70) {
                $vacId = $match['vacancy_id'] ?? null;
                $vac = null;

                if ($vacId) {
                    // Already in DB
                    $vac = \App\Models\Vacancy::find($vacId);
                } else {
                    // This came from HH, not saved before
                    $payload = $vacancyMap["new_{$match['vacancy_index']}"] ?? null;
                    if ($payload && isset($payload['external_id'])) {
                        $vac = $vacancyRepository->createFromHH($payload['raw']); 
                    }
                }

                if (!$vac) {
                    continue;
                }

                $savedData[] = [
                    'resume_id'     => $this->resume->id,
                    'vacancy_id'    => $vac->id,
                    'score_percent' => $match['score'],
                    'explanations'  => json_encode($match),
                    'updated_at'    => now(),
                    'created_at'    => now(),
                ];
            }
        }

        if (!empty($savedData)) {
            DB::table('match_results')->upsert(
                $savedData,
                ['resume_id', 'vacancy_id'],
                ['score_percent', 'explanations', 'updated_at']
            );
        }

        Log::info('Job finished for resume', ['resume_id' => $this->resume->id]);
    }
}
