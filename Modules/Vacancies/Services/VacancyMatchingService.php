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
use Illuminate\Support\Facades\Cache;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Throwable;

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

    public function matchResume(Resume $resume, $query): array
    {
        Log::info('Job started for resume', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        $latinQuery = TranslitHelper::toLatin($query);
        $cyrilQuery = TranslitHelper::toCyrillic($query);

        $words = array_map('trim', explode(',', $query));

        $translator = new GoogleTranslate();
        $translator->setSource('uz');
        $translator->setTarget('uz');
        $uzQuery = $translator->translate("\"{$query}\"");

        $translator->setTarget('ru');
        $ruQuery = $translator->translate("\"{$query}\"");

        $translator->setTarget('en');
        $enQuery = $translator->translate("\"{$query}\"");

        $allVariants = collect([$query, $uzQuery, $ruQuery, $enQuery])
            ->unique()
            ->filter()
            ->values()
            ->all();
        $multiWords = collect($allVariants)
            ->flatMap(fn($v) => preg_split('/[,]+/u', $v)) // <-- bu vergul yoki nuqtali vergul bo‘yicha bo‘ladi
            ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
            ->filter(fn($w) => mb_strlen($w) >= 3)
            ->unique()
            ->values()
            ->all();
        $searchQuery = $latinQuery ?: $cyrilQuery;

        if (!empty($multiWords)) {
            $tsQuery = implode(' & ', array_map('trim', $multiWords));
        } else {
            $tsQuery = trim($searchQuery);
        }

        Log::info('Searching vacancies for terms', ['terms' => $allVariants, 'multi_words' => $multiWords]);
        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

        // 🔹 Qidiruv so‘rovini tayyorlash
        if (!empty($multiWords)) {
            $tsQuery = implode(' & ', array_map('trim', $multiWords));
        } else {
            $tsQuery = trim($latinQuery ?: $cyrilQuery);
        }

        // 🔹 Agar qidiruv bo‘sh bo‘lsa, natija bo‘sh bo‘lsin
        if (empty($tsQuery)) {
            $localVacancies = collect();
        } else {
            $localVacancies = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                })
                ->whereRaw("
        to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(description, ''))
        @@ websearch_to_tsquery('simple', ?)
    ", [$tsQuery])
                ->select(
                    'id',
                    'title',
                    'description',
                    'source',
                    'external_id',
                    DB::raw("
            ts_rank_cd(
                to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(description, '')),
                websearch_to_tsquery('simple', ?)
            ) as rank
        ")
                )
                ->addBinding($tsQuery, 'select')
                ->orderByDesc('rank')
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->keyBy(fn($v) => ($v->source === 'hh' && $v->external_id)
                    ? $v->external_id
                    : "local_{$v->id}");
        }

        // [$hhVacancies, $localVacancies] = Concurrency::run([
        //     fn() => cache()->remember(
        //         "hh:search:{$query}:area97",
        //         now()->addMinutes(30),
        //         fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        //     ),
        //     fn() => DB::table('vacancies')
        //         ->where('status', 'publish')
        //         ->where('source', 'telegram')
        //         ->whereNotIn('id', function ($q) use ($resume) {
        //             $q->select('vacancy_id')
        //                 ->from('match_results')
        //                 ->where('resume_id', $resume->id);
        //         })
        //         ->where(function ($q) use ($multiWords, $latinQuery, $cyrilQuery) {
        //             foreach ($multiWords as $word) {
        //                 $pattern = "%{$word}%";
        //                 $q->orWhere('title', 'ILIKE', $pattern)
        //                     ->orWhere('description', 'ILIKE', $pattern);
        //             }

        //             $q->orWhere('title', 'ILIKE', "%{$latinQuery}%")
        //                 ->orWhere('description', 'ILIKE', "%{$latinQuery}%")
        //                 ->orWhere('title', 'ILIKE', "%{$cyrilQuery}%")
        //                 ->orWhere('description', 'ILIKE', "%{$cyrilQuery}%");
        //         })
        //         //                ->select('id', 'title', 'description', 'source', 'external_id')
        //         ->limit(300)
        //         ->orderByDesc('id')
        //         ->get()
        //         ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id
        //             ? $v->external_id
        //             : "local_{$v->id}")
        // ]);


        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'vacancy_id'   => $v->id,
                // 'title' => $v->title,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        Log::info(['local vacancies' => $vacanciesPayload]);
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(200);
        foreach ($toFetch as $idx =>  $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) {
                continue;
            }
            // $title = $item['name'] ?? 'No title';
            $text = ($item['snippet']['requirement'] ?? '') . "\n" .
                ($item['snippet']['responsibility'] ?? '');

            if (!empty(trim($text))) {
                $vacanciesPayload[] = [
                    'id'          => null,
                    // 'title'       => mb_substr(strip_tags($title), 0, 200),
                    'text'        => mb_substr(strip_tags($text), 0, 1000),
                    'external_id' => $extId,
                    'raw'         => $item,
                    'vacancy_index' => $idx,
                ];
            }
        }
        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }
        Log::info('Prepared payload with ' . count($vacanciesPayload) . ' vacancies');
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        $savedData = [];
        foreach ($vacanciesPayload as $match) {
            try {
                $vac = null;
                $vacId = $match['vacancy_id'] ?? null;

                if ($vacId) {
                    // direct Eloquent lookup
                    $vac = Vacancy::withoutGlobalScopes()->find($vacId);
                }

                // If it's from HH, handle external_id
                if (!$vac && isset($match['external_id'])) {
                    $vac = Vacancy::where('source', 'hh')
                        ->where('external_id', $match['external_id'])
                        ->first();

                    if (!$vac && isset($match['raw'])) {
                        $vac = $this->vacancyRepository->createFromHH($match['raw']);
                    }
                }

                // 🟩 If it’s a local vacancy that exists in DB::table but not found by model,
                // still record it using the ID from the payload.
                if (!$vac && !empty($vacId)) {
                    Log::info('⚙️ Local vacancy not found via model, saving manually', [
                        'vacancy_id' => $vacId,
                    ]);

                    $savedData[] = [
                        'resume_id'     => $resume->id,
                        'vacancy_id'    => $vacId,
                        'score_percent' => $match['score'] ?? 0,
                        'explanations'  => json_encode($match),
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ];

                    continue;
                }

                // If everything’s normal
                if ($vac) {
                    $savedData[] = [
                        'resume_id'     => $resume->id,
                        'vacancy_id'    => $vac->id,
                        'score_percent' => $match['score'] ?? 0,
                        'explanations'  => json_encode($match),
                        'updated_at'    => now(),
                        'created_at'    => now(),
                    ];
                }
            } catch (\Throwable $e) {
                Log::error('💥 Error while matching vacancy', [
                    'match' => $match,
                    'error' => $e->getMessage(),
                ]);
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
