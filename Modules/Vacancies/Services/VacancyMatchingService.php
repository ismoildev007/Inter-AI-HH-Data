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
        $translator->setSource('auto');
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

        $tokens = $allVariants
            ->flatMap(fn($v) => preg_split('/[\s,;\/\|]+/u', (string) $v))
            ->map(fn($w) => trim(preg_replace('/[\"\'«»“”]/u', '', $w)))
            ->filter(fn($w) => mb_strlen($w) >= 3)
            ->map(fn($w) => mb_strtolower($w, 'UTF-8'))
            ->unique()
            ->take(8)
            ->values();
        $tokenArr = $tokens->all();

        $phrases = $allVariants
            ->flatMap(fn($v) => preg_split('/\s*,\s*/u', (string) $v))
            ->map(fn($s) => trim($s))
            ->filter(fn($s) => mb_strlen($s) >= 3 && preg_match('/\s/u', $s))
            ->map(fn($s) => mb_strtolower($s, 'UTF-8'))
            ->unique()
            ->take(4)
            ->values();
        $phraseArr = $phrases->all();

        $tokensByLen = $tokens->sortByDesc(fn($t) => mb_strlen($t, 'UTF-8'))->values();
        $mustAnd = array_slice($tokensByLen->all(), 0, 2);

        Log::info('Searching vacancies for terms', ['terms' => $allVariants->all(), 'tokens' => $tokenArr]);

        $searchQuery = $latinQuery ?: $cyrilQuery;

        $tsTerms = array_merge(
            array_map('trim', array_map('strval', $phraseArr)),
            array_map('trim', array_map('strval', $tokenArr))
        );
        $mustPair = [];
        if (count($tokenArr) >= 2) {
            $mustPair = ['(' . $tokenArr[0] . ' ' . $tokenArr[1] . ')']; // websearch: space = AND
        }
        $webParts = array_merge($mustPair, $tsTerms);

        $tsQuery = !empty($webParts)
            ? implode(' OR ', array_map(function ($t) {
                return str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t;
            }, $webParts))
            : trim((string) $searchQuery);

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

        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

        $buildLocal = function (bool $withCategory) use ($resume, $tsQuery, $tokenArr, $guessedCategory) {
            // IT sohalar ro‘yxati
            $techCategories = [
                "IT and Software Development",
                "Data Science and Analytics",
                "QA and Testing",
                "DevOps and Cloud Engineering",
                "UI/UX and Product Design"
            ];

            $resumeCategory = $resume->category ?? null;

            $qb = DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                });

            /**
             * 🔍 1. Agar kategoriya IT/Tech sohalardan biri bo‘lsa → to‘liq search ishlaydi (title, description)
             */
            if ($resumeCategory && in_array($resumeCategory, $techCategories, true)) {
                $qb->where(function ($query) use ($tsQuery, $tokenArr) {
                    // PostgreSQL to_tsvector orqali qidiruv
                    $query->whereRaw("
                to_tsvector('simple', coalesce(description, ''))
                @@ websearch_to_tsquery('simple', ?)
            ", [$tsQuery]);

                    // Tokenlar bo‘yicha kengroq qidiruv (title + description)
                    if (!empty($tokenArr)) {
                        $top = array_slice($tokenArr, 0, min(10, count($tokenArr)));
                        $query->orWhere(function ($q) use ($top) {
                            foreach ($top as $t) {
                                $pattern = "%{$t}%";
                                $q->orWhere('description', 'ILIKE', $pattern)
                                    ->orWhere('title', 'ILIKE', $pattern);
                            }
                        });
                    }
                });

                Log::info("Resume [ID: {$resume->id}] is in TECH category '{$resumeCategory}' → using full text + title search.");
            }
            /**
             * ⚙️ 2. Agar boshqa kategoriya bo‘lsa → title orqali qidiruv yo‘q, faqat category bo‘yicha chiqsin
             */
            else {
                Log::info("Resume [ID: {$resume->id}] is in NON-TECH category '{$resumeCategory}' → returning all vacancies from this category.");
            }

            // Umumiy select
            $qb->select(
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

            // Category cheklovi
            if ($withCategory) {
                if ($resumeCategory) {
                    $countSameCategory = DB::table('vacancies')
                        ->where('status', 'publish')
                        ->where('source', 'telegram')
                        ->where('category', $resumeCategory)
                        ->count();

                    Log::info("Resume [ID: {$resume->id}] category '{$resumeCategory}' → {$countSameCategory} matching vacancies found.");

                    $qb->where('category', $resumeCategory);
                } elseif ($guessedCategory) {
                    $qb->where('category', $guessedCategory);
                }
            }

            return $qb->orderByDesc('rank')->orderByDesc('id');
        };

// 🔹 Asosiy qidiruv
        $localVacancies = $buildLocal(true)->limit(1000)->get();

        $localVacancies = collect($localVacancies)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");
        $techCategories = [
            "IT and Software Development",
            "Data Science and Analytics",
            "QA and Testing",
            "DevOps and Cloud Engineering",
            "UI/UX and Product Design"
        ];
//
//        // Agar juda kam chiqsa (masalan < 100) → fallback: shu categorydagi hamma vacancy
//        if ($localVacancies->count() < 100 && !empty($resume->category)) {
//            $fallback = DB::table('vacancies')
//                ->where('status', 'publish')
//                ->where('source', 'telegram')
//                ->where('category', $resume->category)
//                ->limit(200)
//                ->get();
//
//            Log::info("⚠️ Low match ({$localVacancies->count()} found). Added fallback {$fallback->count()} from category '{$resume->category}'.");
//
//            $localVacancies = $localVacancies->concat($fallback)->unique('id');
//        }


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
            ->take(100);
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
                    $vac = Vacancy::withoutGlobalScopes()->find($vacId);
                }

                if (!$vac && isset($match['external_id'])) {
                    $vac = Vacancy::where('source', 'hh')
                        ->where('external_id', $match['external_id'])
                        ->first();

                    if (!$vac && isset($match['raw'])) {
                        $vac = $this->vacancyRepository->createFromHH($match['raw']);
                    }
                }

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
