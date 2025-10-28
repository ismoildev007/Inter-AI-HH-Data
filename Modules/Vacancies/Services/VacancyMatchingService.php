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
use Modules\TelegramChannel\Services\VacancyCategoryService;

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
            ->values();

        // Kengaytirilgan tokenizatsiya: bo'sh joy, vergul, nuqtali vergul, chiziqcha va h.k.
        $tokens = $allVariants
            ->flatMap(fn($v) => preg_split('/[\s,;\/\|]+/u', (string) $v))
            ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
            ->filter(fn($w) => mb_strlen($w) >= 3)
            ->map(fn($w) => mb_strtolower($w, 'UTF-8'))
            ->unique()
            ->take(8)
            ->values();
        $tokenArr = $tokens->all();

        // Ko'p so'zli iboralar (vergul bo'yicha segmentlar) — aniqroq moslik uchun phrase matching
        $phrases = $allVariants
            ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
            ->map(fn($s) => trim($s))
            ->filter(fn($s) => mb_strlen($s) >= 3 && preg_match('/\s/u', $s))
            ->map(fn($s) => mb_strtolower($s, 'UTF-8'))
            ->unique()
            ->take(4)
            ->values();
        $phraseArr = $phrases->all();

        Log::info('Searching vacancies for terms', ['terms' => $allVariants->all(), 'tokens' => $tokenArr]);

        $searchQuery = $latinQuery ?: $cyrilQuery;

        // websearch_to_tsquery uchun OR mantiqi: websearch sintaksisida 'OR' so'zi ishlatiladi
        // Ko'p so'zli frazalar "..." bilan o'raladi
        $tsTerms = array_merge(
            array_map('trim', array_map('strval', $phraseArr)),
            array_map('trim', array_map('strval', $tokenArr))
        );
        $tsQuery = !empty($tsTerms)
            ? implode(' OR ', array_map(function ($t) {
                return str_contains($t, ' ') ? '"'.str_replace('"','', $t).'"' : $t;
            }, $tsTerms))
            : trim((string) $searchQuery);

        // Rezume bo'yicha taxminiy kategoriya — mos natijalarni ustun qo'yish uchun
        $guessedCategory = null;
        try {
            /** @var VacancyCategoryService $categorizer */
            $categorizer = app(\Modules\TelegramChannel\Services\VacancyCategoryService::class);
            $guessedCategory = $categorizer->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            if (!is_string($guessedCategory) || mb_strtolower($guessedCategory, 'UTF-8') === 'other' || $guessedCategory === '') {
                $guessedCategory = null;
            }
        } catch (\Throwable $e) {
            $guessedCategory = null;
        }

        // HH qidiruvini cache bilan olamiz
        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

        // Lokal (telegram) qidiruvini ikki fazada: (1) strict kategoriya, (2) umumiy fallback
        $buildLocal = function (bool $withCategory) use ($resume, $tsQuery, $tokenArr, $guessedCategory) {
            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                })
                ->where(function ($query) use ($tsQuery, $tokenArr) {
                    // Rezume title/keywords -> faqat description bo'yicha FT qidiruv (titlega emas)
                    $query->whereRaw("
                        to_tsvector('simple', coalesce(description, ''))
                        @@ websearch_to_tsquery('simple', ?)
                    ", [$tsQuery]);

                    // Fallback: OR-ILIKE (recallni oshirish uchun)
                    if (!empty($tokenArr)) {
                        $top = array_slice($tokenArr, 0, min(5, count($tokenArr)));
                        $query->orWhere(function ($q) use ($top) {
                            foreach ($top as $idx => $t) {
                                $pattern = "%{$t}%";
                                // Faqat description ILIKE — titlega mos kelgan dev lavozimlar aralashmasin
                                if ($idx === 0) {
                                    $q->where('description', 'ILIKE', $pattern);
                                } else {
                                    $q->orWhere('description', 'ILIKE', $pattern);
                                }
                            }
                        });
                    }
                })
                ->select(
                    'id',
                    'title',
                    'description',
                    'source',
                    'external_id',
                    'category',
                    DB::raw("
                        ts_rank_cd(
                            to_tsvector('simple', coalesce(description, '')),
                            websearch_to_tsquery('simple', ?)
                        ) as rank
                    ")
                )
                ->addBinding($tsQuery, 'select');

            if ($withCategory && $guessedCategory) {
                $qb->where('category', $guessedCategory);
            }

            return $qb->orderByDesc('rank')->orderByDesc('id');
        };

        $catLimit = 100;
        $fallbackThreshold = 40; // kategoriya bo'yicha natijalar kam bo'lsa — umumiy qidiruv bilan to'ldiramiz
        $localCat = $buildLocal(true)->limit($catLimit)->get();

        if ($guessedCategory && $localCat->count() < $fallbackThreshold) {
            $need = max(0, $catLimit - $localCat->count());
            $localGen = $buildLocal(false)->limit($need)->get();
            $localVacancies = collect($localCat)->concat($localGen)->values();
        } else {
            $localVacancies = $localCat;
        }

        // Key by external key to avoid duplicates vs HH payload join further
        $localVacancies = collect($localVacancies)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");


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
