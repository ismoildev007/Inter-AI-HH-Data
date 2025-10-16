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

        $latinQuery = TranslitHelper::toLatin($query);
        $cyrilQuery = TranslitHelper::toCyrillic($query);

        $words = array_map('trim', explode(',', $query));

        [$hhVacancies, $localVacancies] = Concurrency::run([
            fn() => cache()->remember(
                "hh:search:{$query}:area97",
                now()->addMinutes(30),
                fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
            ),
            fn() => Vacancy::query()
                ->where('status', 'publish')
                ->where(function ($q) use ($words, $latinQuery, $cyrilQuery) {
                    foreach ($words as $word) {
                        $latin = TranslitHelper::toLatin($word);
                        $cyril = TranslitHelper::toCyrillic($word);
                        $q->orWhere(function ($sub) use ($word, $latin, $cyril) {
                            $sub->where('title', 'ilike', "%{$word}%")
                                ->orWhere('title', 'ilike', "%{$latin}%")
                                ->orWhere('title', 'ilike', "%{$cyril}%")
                                ->orWhere('description', 'ilike', "%{$word}%")
                                ->orWhere('description', 'ilike', "%{$latin}%")
                                ->orWhere('description', 'ilike', "%{$cyril}%");
                        });
                    }
                    $q->orWhere('title', 'ilike', "%{$latinQuery}%")
                        ->orWhere('title', 'ilike', "%{$cyrilQuery}%")
                        ->orWhere('description', 'ilike', "%{$latinQuery}%")
                        ->orWhere('description', 'ilike', "%{$cyrilQuery}%");
                })
                ->get()
                ->keyBy(
                    fn($v) => $v->source === 'hh' && $v->external_id
                        ? $v->external_id
                        : "local_{$v->id}"
                ),
        ]);

        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(70);
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
                    'text'        => mb_substr(strip_tags($text), 0, 1000),
                    'external_id' => $extId,
                    'raw'         => $item,
                ];
            }
        }
        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }
        Log::info('Prepared payload with ' . count($vacanciesPayload) . ' vacancies');
        $url = config('services.matcher.url', 'https://python.inter-ai.uz/bulk-match-fast');
        $response = Http::retry(3, 200)->timeout(30)->post($url, [
            'resumes'   => [mb_substr($resume->parsed_text, 0, 3000)],
            'vacancies' => array_map(fn($v) => [
                'id'   => $v['id'] ? (string) $v['id'] : null,
                'text' => $v['text'],
            ], $vacanciesPayload),
            'top_k'     => count($vacanciesPayload),
            'min_score' => 60,
        ]);

        Log::info('Fetch HH details took: ' . (microtime(true) - $start) . 's');
        Log::info('hh response count:'. count($response->json()));
        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $resume->id, 'body' => $response->body()]);
            return [];
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];
        Log::info('example match', ['match' => $matches[0] ?? null]);
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        $savedData = [];
        foreach ($matches as $match) {
            if ($match['score'] < 50) continue;

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
        Log::info('All details took finished: ' . (microtime(true) - $start) . 's');

        Log::info('Matching finished', ['resume_id' => $resume->id]);

        return $savedData;
    }
}
