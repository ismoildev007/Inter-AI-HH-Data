<?php

namespace Modules\Vacancies\Services;

use App\Models\Resume;
use App\Models\MatchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Modules\Vacancies\Interfaces\VacancyInterface;

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

    public function matchResume(Resume $resume, string $query): array
    {
        Log::info(['resume info' => $query]);
        Log::info('Job started for resume', ['resume_id' => $resume->id]);

        // --- Fetch from HH ---
        $cacheKey = "hh:search:{$query}:area97";
        $hhVacancies = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($query) {
            return $this->hhRepository->search($query, 0, 100, ['area' => 97]);
        });

        $hhItems = $hhVacancies['items'] ?? [];
        if (empty($hhItems)) {
            Log::info('No HH vacancies found', ['query' => $query]);
        }

        // --- Fetch from local DB (own vacancies) ---
        $localVacancies = \App\Models\Vacancy::where('title', 'like', "%{$query}%")
            ->get()
            ->keyBy(function ($v) {
                return $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}";
            });

        // --- Prepare merged vacancies ---
        $vacanciesPayload = [];

        // Add local vacancies first
        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'text' => strip_tags($v->description),
            ];
        }

        // Add HH vacancies if not already in local
        foreach ($hhItems as $item) {
            $extId = $item['id'] ?? null;
            if (!$extId) {
                continue;
            }

            if ($localVacancies->has($extId)) {
                continue; // skip duplicate
            }

            $full = cache()->remember("hh:vacancy:{$extId}", now()->addHours(6), function () use ($item) {
                return $this->hhRepository->getById($item['id']);
            });

            if (!empty($full['description'])) {
                $vacanciesPayload[] = [
                    'id'   => null,
                    'text' => strip_tags($full['description']),
                    'external_id' => $extId,
                    'raw'  => $full,
                ];
            }
        }

        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }

        // --- Call Python matcher ---
        $url = config('services.matcher.url', 'https://python.inter-ai.uz/bulk-match-fast');
        $response = Http::timeout(60)->post($url, [
            'resumes'   => [$resume->parsed_text ?? $resume->description],
            'vacancies' => array_map(fn($v) => ['id' => $v['id'], 'text' => $v['text']], $vacanciesPayload),
            'top_k'     => 50,
            'min_score' => 0,
        ]);

        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $resume->id, 'body' => $response->body()]);
            return [];
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];

        // --- Map extId to payload for later save ---
        $vacancyMap = collect($vacanciesPayload)->keyBy(function ($v, $k) {
            return $v['id'] ?? "new_{$k}";
        });

        // --- Save results ---
        $savedData = [];
        foreach ($matches as $match) {
            if ($match['score'] >= 70) {
                $vacId = $match['vacancy_id'] ?? null;
                $vac = null;

                if ($vacId) {
                    $vac = \App\Models\Vacancy::find($vacId);
                } else {
                    $payload = $vacancyMap["new_{$match['vacancy_index']}"] ?? null;
                    if ($payload && isset($payload['external_id'])) {
                        $vac = $this->vacancyRepository->createFromHH($payload['raw']);
                    }
                }

                if (!$vac) {
                    continue;
                }

                $savedData[] = [
                    'resume_id'     => $resume->id,
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

        Log::info('Matching finished', ['resume_id' => $resume->id]);

        return $savedData;
    }
}
