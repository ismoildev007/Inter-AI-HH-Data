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

        $translations = [
            'uz' => $uzQuery,
            'ru' => $ruQuery,
            'en' => $enQuery,
        ];

        $allVariants = collect([$query, $uzQuery, $ruQuery, $enQuery])
            ->unique()
            ->filter()
            ->values()
            ->all();

        // $multiWords = array_unique(array_merge(
        //     ...array_map(fn($q) => array_map('trim', explode(',', $q)), $allVariants)
        // ));
        $multiWords = collect($allVariants)
            ->flatMap(fn($v) => preg_split('/[,;]+/u', $v)) // <-- bu vergul yoki nuqtali vergul bo‘yicha bo‘ladi
            ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
            ->filter(fn($w) => mb_strlen($w) >= 3)
            ->unique()
            ->values()
            ->all();

        Log::info('Searching vacancies for terms', ['terms' => $allVariants, 'multi_words' => $multiWords]);


        [$hhVacancies, $localVacancies] = Concurrency::run([
            fn() => cache()->remember(
                "hh:search:{$query}:area97",
                now()->addMinutes(30),
                fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
            ),
            fn() => DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                })
                ->where(function ($q) use ($multiWords, $latinQuery, $cyrilQuery) {
                    foreach ($multiWords as $word) {
                        $pattern = "%{$word}%";
                        $q->orWhere('title', 'ILIKE', $pattern)
                            ->orWhere('description', 'ILIKE', $pattern);
                    }

                    $q->orWhere('title', 'ILIKE', "%{$latinQuery}%")
                        ->orWhere('description', 'ILIKE', "%{$latinQuery}%")
                        ->orWhere('title', 'ILIKE', "%{$cyrilQuery}%")
                        ->orWhere('description', 'ILIKE', "%{$cyrilQuery}%");
                })
                // ->whereRaw("
                //         (
                //             title ILIKE ANY (ARRAY['%" . implode("%','%", $multiWords) . "%']) OR
                //             description ILIKE ANY (ARRAY['%" . implode("%','%", $multiWords) . "%'])
                //         )
                //         OR title ILIKE '%{$latinQuery}%'
                //         OR description ILIKE '%{$latinQuery}%'
                //         OR title ILIKE '%{$cyrilQuery}%'
                //         OR description ILIKE '%{$cyrilQuery}%'
                //     ")
                ->select('id', 'title', 'description', 'source', 'external_id')
                ->limit(100)
                ->orderByDesc('id')
                ->get()
                ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id
                    ? $v->external_id
                    : "local_{$v->id}")
        ]);
        // $multiWords = array_unique(array_filter(
        //     array_map('trim', preg_split('/[\s,«»"“”]+/u', implode(' ', $allVariants)))
        // ));

        // Log::info('Searching vacancies for terms', ['terms' => $allVariants, 'multi_words' => $multiWords]);

        // [$hhVacancies, $localVacancies] = Concurrency::run([
        //     fn() => cache()->remember(
        //         "hh:search:{$query}:area97",
        //         now()->addMinutes(30),
        //         fn() => $this->hhRepository->search($query, 0, 200, ['area' => 97])
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
        //                   ->orWhere('description', 'ILIKE', $pattern);
        //             }

        //             $q->orWhere('title', 'ILIKE', "%{$latinQuery}%")
        //               ->orWhere('description', 'ILIKE', "%{$latinQuery}%")
        //               ->orWhere('title', 'ILIKE', "%{$cyrilQuery}%")
        //               ->orWhere('description', 'ILIKE', "%{$cyrilQuery}%");
        //         })
        //         ->select('id', 'title', 'description', 'source', 'external_id')
        //         ->limit(200)
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
        //        $url = config('services.matcher.url', 'https://6hs64qyu5n547c-8000.proxy.runpod.net/bulk-match-fast');
        //        $response = Http::retry(3, 200)
        //            ->timeout(600)
        //            ->post($url, [
        //                'resumes' => [[
        //                    // 'title'       => mb_substr($resume->title ?? '', 0, 200),
        //                    'description' => mb_substr($resume->parsed_text ?? '', 0, 2000),
        //                ]],
        //                'vacancies'      => array_map(fn($v) => [
        //                    'id'    => $v['id'] ? (string)$v['id'] : null,
        //                    // 'title' => $v['title'] ?? '',
        //                    'text'  => $v['text'] ?? '',
        //                ], $vacanciesPayload),
        //                'top_k'          => count($vacanciesPayload),
        //                'min_score'      => 50,
        //                'weight_embed'   => 0.75,
        //                'weight_jaccard' => 0.15,
        //                'weight_cov'     => 0.1,
        //                // "title_threshold" => 0.5
        //            ]);
        //
        //        Log::info('Fetch HH details took: ' . (microtime(true) - $start) . 's');
        //        Log::info('hh response count:' . count($response->json()));
        //        if ($response->failed()) {
        //            Log::error('Matcher API failed', ['resume_id' => $resume->id, 'body' => $response->body()]);
        //            return [];
        //        }
        //
        //        $results = $response->json();
        //        $matches = $results['results'][0] ?? [];
        //        Log::info('Matches found: ' . count($matches));
        //        Log::info('example match', ['match' => $matches[0] ?? null]);
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        $savedData = [];
        //         foreach ($vacanciesPayload as $match) {
        // //            if ($match['score'] < 49) continue;

        //             $vacId = $match['vacancy_id'] ?? null;
        //             $vac   = $vacId ? Vacancy::find($vacId) : null;

        //             if (!$vac) {
        //                 $payload = $vacancyMap["new_{$match['vacancy_index']}"] ?? null;
        //                 if ($payload && isset($payload['external_id'])) {
        //                     $vac = Vacancy::where('source', 'hh')
        //                         ->where('external_id', $payload['external_id'])
        //                         ->first();

        //                     if (!$vac) {
        //                         $vac = $this->vacancyRepository->createFromHH($payload['raw']);
        //                     }
        //                 }
        //             }

        //             if ($vac) {
        //                 $savedData[] = [
        //                     'resume_id'     => $resume->id,
        //                     'vacancy_id'    => $vac->id,
        //                     'score_percent' => $match['score'],
        //                     'explanations'  => json_encode($match),
        //                     'updated_at'    => now(),
        //                     'created_at'    => now(),
        //                 ];
        //             }
        //         }

        // foreach ($vacanciesPayload as $match) {
        //     // Skip invalid or incomplete matches
        //     // if (!isset($match['score'])) {
        //     //     $match['score'] = 0; // default score if not provided
        //     // }

        //     $vacId = $match['vacancy_id'] ?? null;
        //     $vac   = $vacId ? Vacancy::find($vacId) : null;

        //     if (!$vac) {
        //         // Safely get the vacancy_index if it exists
        //         $vacIndex = $match['vacancy_index'] ?? null;
        //         $payload = $vacIndex !== null
        //             ? ($vacancyMap["new_{$vacIndex}"] ?? null)
        //             : null;

        //         if ($payload && isset($payload['external_id'])) {
        //             $vac = Vacancy::where('source', 'hh')
        //                 ->where('external_id', $payload['external_id'])
        //                 ->first();

        //             if (!$vac) {
        //                 $vac = $this->vacancyRepository->createFromHH($payload['raw']);
        //             }
        //         }
        //     }

        //     if ($vac) {
        //         $savedData[] = [
        //             'resume_id'     => $resume->id,
        //             'vacancy_id'    => $vac->id,
        //             'score_percent' => $match['score'] ?? 0,
        //             'explanations'  => json_encode($match),
        //             'updated_at'    => now(),
        //             'created_at'    => now(),
        //         ];
        //     } else {
        //         Log::warning('⚠️ Skipped match with missing vacancy_index or external_id', [
        //             'match' => $match,
        //         ]);
        //     }
        // }
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
