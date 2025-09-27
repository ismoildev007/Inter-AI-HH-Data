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
        Log::info('Job started for resume', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        // --- Fetch HH and local vacancies in parallel ---
        [$hhVacancies, $localVacancies] = Concurrency::run([
            fn() => cache()->remember(
                "hh:search:{$query}:area97",
                now()->addMinutes(30),
                fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
            ),
            fn() => \App\Models\Vacancy::where('title', 'like', "%{$query}%")
                ->get()
                ->keyBy(
                    fn($v) => $v->source === 'hh' && $v->external_id
                        ? $v->external_id
                        : "local_{$v->id}"
                ),
        ]);
        

        $hhItems = $hhVacancies['items'] ?? [];
        // --- Prepare merged vacancies payload ---
        $vacanciesPayload = [];
        // Local vacancies
        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        // --- Fetch HH vacancy details concurrently ---
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(70); 
        Log::info('Fetch HH details took: ' . (microtime(true) - $start) . 's');

        foreach ($toFetch as $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) {
                continue;
            }
        
            $text = ($item['snippet']['requirement'] ?? '') . "\n" .
                    ($item['snippet']['responsibility'] ?? '');
        
            if (!empty(trim($text))) {
                $vacanciesPayload[] = [
                    'id'          => null,
                    'text'        => mb_substr(strip_tags($text), 0, 1000), // shorter for speed
                    'external_id' => $extId,
                    'raw'         => $item, // raw item, not full vacancy
                ];
            }
        }
        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }
        // --- Call Python matcher ---
        $url = config('services.matcher.url', 'https://python.inter-ai.uz/bulk-match-fast');
        $response = Http::timeout(30)->post($url, [
            'resumes'   => [mb_substr($resume->description, 0, 3000)],
            'vacancies' => array_map(fn($v) => [
                'id'   => $v['id'] ? (string) $v['id'] : null, // force string
                'text' => $v['text'],
            ], $vacanciesPayload),
            'top_k'     => 100,
            'min_score' => 0,
        ]);
        
        
        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $resume->id, 'body' => $response->body()]);
            return [];
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];
        // --- Map extId to payload ---
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        // --- Save results ---
        $savedData = [];
        foreach ($matches as $match) {
            if ($match['score'] < 70) continue;

            $vacId = $match['vacancy_id'] ?? null;
            $vac   = $vacId ? Vacancy::find($vacId) : null;

            if (!$vac) {
                $payload = $vacancyMap["new_{$match['vacancy_index']}"] ?? null;
                if ($payload && isset($payload['external_id'])) {
                    $vac = Vacancy::where('source', 'hh')
                        ->where('external_id', $payload['external_id'])
                        ->first();

                    if (!$vac) {
                        $vac = $this->vacancyRepository->createFromHH($payload['raw']);
                    }
                }
            }

            if ($vac) {
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
