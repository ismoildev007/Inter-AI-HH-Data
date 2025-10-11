<?php

namespace Modules\Vacancies\Services;

use App\Models\DemoResume;
use App\Models\Resume;
use App\Models\Vacancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Concurrency;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Modules\Vacancies\Interfaces\VacancyInterface;
use Spatie\Async\Pool;

class DemoVacancyMatchingService
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

        // 1️⃣ — Title yoki descriptiondagi query’ni vergul yoki nuqtali vergul bo‘yicha ajratamiz
        $keywords = collect(
            array_filter(
                array_map(fn($q) => trim($q), preg_split('/[,;]+/', $query))
            )
        );

        Log::info('Parsed keywords', ['keywords' => $keywords]);

        $allHhVacancies = collect();
        $localVacancies = collect();

        // 2️⃣ — Har bir kalit so‘z bo‘yicha parallel tarzda HH va lokal vacancylarni yig‘amiz
        foreach ($keywords as $keyword) {
            [$hhVacancies, $locals] = Concurrency::run([
                fn() => cache()->remember(
                    "hh:search:{$keyword}:area97",
                    now()->addMinutes(30),
                    fn() => $this->hhRepository->search($keyword, 0, 100, ['area' => 97])
                ),
                fn() => Vacancy::query()
                    ->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->get()
                    ->keyBy(
                        fn($v) => $v->source === 'hh' && $v->external_id
                            ? $v->external_id
                            : "local_{$v->id}"
                    ),
            ]);

            $hhItems = $hhVacancies['items'] ?? [];
            $allHhVacancies = $allHhVacancies->merge($hhItems);
            $localVacancies = $localVacancies->merge($locals);
        }

        // 3️⃣ — Dublikatlarni yo‘qotamiz
        $allHhVacancies = $allHhVacancies->unique('id')->values();
        $localVacancies = $localVacancies->unique('id')->values();

        Log::info('Total HH vacancies collected', ['count' => $allHhVacancies->count()]);
        Log::info('Total local vacancies collected', ['count' => $localVacancies->count()]);

        // 4️⃣ — Vacancy matnlarini tayyorlaymiz
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'text' => mb_substr(strip_tags($v->description ?? ''), 0, 2000),
            ];
        }

        // HH vakansiyalarini qo‘shamiz
        $toFetch = collect($allHhVacancies)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(70);

        foreach ($toFetch as $item) {
            $extId = $item['id'] ?? null;
            if (!$extId) continue;

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

        // 5️⃣ — Matcher servisiga yuboramiz
        $url = config('services.matcher.url', 'https://python.inter-ai.uz/bulk-match-fast');
        $response = Http::retry(3, 200)->timeout(30)->post($url, [
            'resumes'   => [mb_substr($resume->parsed_text, 0, 3000)],
            'vacancies' => array_map(fn($v) => [
                'id'   => $v['id'] ? (string) $v['id'] : null,
                'text' => $v['text'],
            ], $vacanciesPayload),
            'top_k'     => 100,
            'min_score' => 0,
        ]);

        Log::info('Matcher request finished', [
            'resume_id' => $resume->id,
            'time' => round(microtime(true) - $start, 2) . 's'
        ]);

        if ($response->failed()) {
            Log::error('Matcher API failed', ['resume_id' => $resume->id, 'body' => $response->body()]);
            return [];
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];

        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        // 6️⃣ — Moslik natijalarini saqlaymiz
        $savedData = [];
        foreach ($matches as $match) {
            if (($match['score'] ?? 0) < 50) continue;

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

        Log::info('Matching finished', [
            'resume_id' => $resume->id,
            'matched' => count($savedData),
            'duration' => round(microtime(true) - $start, 2) . 's'
        ]);

        return $savedData;
    }

}
