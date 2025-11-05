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

class NotificationMatchingService
{
    protected VacancyInterface $vacancyRepository;
    protected HHVacancyInterface $hhRepository;

    public function __construct(
        VacancyInterface   $vacancyRepository,
        HHVacancyInterface $hhRepository
    )
    {
        $this->vacancyRepository = $vacancyRepository;
        $this->hhRepository = $hhRepository;
    }

    public function matchResume(Resume $resume, $query): array
    {
        Log::info('🚀 Job started', ['resume_id' => $resume->id, 'query' => $query]);
        $start = microtime(true);

        $latinQuery = TranslitHelper::toLatin($query);
        $cyrilQuery = TranslitHelper::toCyrillic($query);

        $translator = new GoogleTranslate();
        $translator->setSource('auto');

        $translations = [
            'uz' => fn() => $translator->setTarget('uz')->translate("\"{$query}\""),
            'ru' => fn() => $translator->setTarget('ru')->translate("\"{$query}\""),
            'en' => fn() => $translator->setTarget('en')->translate("\"{$query}\""),
        ];

        $allVariants = collect([$query])
            ->merge(array_map(fn($f) => $f(), $translations))
            ->unique()
            ->filter()
            ->values();

        $splitByComma = fn($v) => preg_split('/\s*,\s*/u', (string) $v);
        $cleanText = fn($w) => mb_strtolower(trim(preg_replace('/[\"\'«»“”]/u', '', $w)), 'UTF-8');

        $tokens = $allVariants
            ->flatMap($splitByComma)
            ->map($cleanText)
            ->filter(fn($w) => mb_strlen($w) >= 2)
            ->unique()
            ->take(8)
            ->values();

        Log::info('🧩 Tokens parsed', ['tokens' => $tokens->all()]);

        $phrases = $allVariants
            ->flatMap($splitByComma)
            ->map($cleanText)
            ->filter(fn($s) => mb_strlen($s) >= 3 && str_contains($s, ' '))
            ->unique()
            ->take(4)
            ->values();

        $searchQuery = $latinQuery ?: $cyrilQuery;
        $tsTerms = [...$phrases, ...$tokens];
        $mustPair = count($tokens) >= 2 ? ['(' . $tokens[0] . ' ' . $tokens[1] . ')'] : [];
        $webParts = array_merge($mustPair, $tsTerms);

        $tsQuery = !empty($webParts)
            ? implode(' OR ', array_map(fn($t) => str_contains($t, ' ') ? '"' . str_replace('"', '', $t) . '"' : $t, $webParts))
            : (string) $searchQuery;

        try {
            $guessedCategory = app(VacancyCategoryService::class)
                ->categorize('', (string) ($resume->title ?? ''), (string) ($resume->description ?? ''), '');
            $guessedCategory = (is_string($guessedCategory) && !in_array(mb_strtolower($guessedCategory), ['other', ''], true))
                ? $guessedCategory
                : null;
        } catch (\Throwable) {
            $guessedCategory = null;
        }

        $hhVacancies = cache()->remember(
            "hh:search:{$query}:area97",
            now()->addMinutes(30),
            fn() => $this->hhRepository->search($query, 0, 100, ['area' => 97])
        );

        $buildLocal = function (bool $withCategory) use ($resume, $tsQuery, $tokens, $guessedCategory) {
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

            $isTech = in_array($resumeCategory, $techCategories, true);

            // umumiy tsvector (title+description)
            $tsVectorSql = "
                setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('simple', coalesce(description, '')), 'B')
            ";

            if ($isTech) {
                // 🧠 Tech kategoriyalarda — faqat shu kategoriyalardagi vacancies ichidan qidiradi
                $categoriesForSearch = collect([$resumeCategory, $guessedCategory])
                    ->filter()
                    ->unique()
                    ->all();

                if (empty($categoriesForSearch)) {
                    // agar kategoriya yo‘q bo‘lsa, fallback sifatida umumiy texnik kategoriyalarni olamiz
                    $categoriesForSearch = $techCategories;
                }

                $qb->whereIn('category', $categoriesForSearch);

                // 🔍 Keng qidiruv: FT + tokens + extraSkills (lekin shu kategoriya ichida)
                $extraSkills = collect(preg_split('/\s*,\s*/u', (string) ($resume->title ?? '')))
                    ->map(fn($s) => trim($s))
                    ->filter(fn($s) => mb_strlen($s) > 2)
                    ->unique()
                    ->values();

                $qb->where(function ($q) use ($tsQuery, $tokens, $extraSkills, $tsVectorSql) {
                    // 1) Full-text search
                    $q->whereRaw("$tsVectorSql @@ websearch_to_tsquery('simple', ?)", [$tsQuery]);

                    // 2) Token LIKE qidiruv
                    if ($tokens->isNotEmpty()) {
                        $likeTokens = $tokens->take(10)->map(fn($t) => "%{$t}%")->all();
                        $q->orWhere(function ($sub) use ($likeTokens) {
                            foreach ($likeTokens as $pattern) {
                                $sub->orWhere('title', 'ILIKE', $pattern)
                                    ->orWhere('description', 'ILIKE', $pattern);
                            }
                        });
                    }

                    // 3) Extra skills
                    if ($extraSkills->isNotEmpty()) {
                        $q->orWhere(function ($sub) use ($extraSkills) {
                            foreach ($extraSkills as $skill) {
                                $pattern = "%{$skill}%";
                                $sub->orWhere('title', 'ILIKE', $pattern)
                                    ->orWhere('description', 'ILIKE', $pattern);
                            }
                        });
                        Log::info('🧠 [TECH TITLE-BASED SKILL SEARCH]', [
                            'skills' => $extraSkills->all(),
                        ]);
                    }
                });

                // Rank (title+desc asosida)
                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw("ts_rank_cd($tsVectorSql, websearch_to_tsquery('simple', ?)) as rank")
                )->addBinding($tsQuery, 'select');

                Log::info("💡 [TECH SEARCH LIMITED TO CATEGORIES]", ['categories' => $categoriesForSearch]);

            } else {
                // 🔹 TEXNIK EMAS — category bo‘yicha ham, umumiy title search ham
                $qb->select(
                    'id', 'title', 'description', 'source', 'external_id', 'category',
                    DB::raw('0 as rank')
                );

                $qb->where(function ($main) use ($tokens, $tsQuery, $resume, $resumeCategory) {

                    // 🟢 1. Agar kategoriya mavjud bo‘lsa — shu kategoriyadagi barcha vacancies
                    if (!empty($resumeCategory)) {
                        $main->orWhere('category', $resumeCategory);
                        Log::info("📂 [NON-TECH CATEGORY] {$resumeCategory} vacancies included.");
                    }

                    // 🟢 2. Full-text search (butun vacancies bazasidan)
                    $main->orWhereRaw("
                to_tsvector('simple', coalesce(description, '')) @@ websearch_to_tsquery('simple', ?)
            ", [$tsQuery]);

                    // 🟢 3. Tokenlar orqali title/description qidiruv (butun bazadan)
                    if ($tokens->isNotEmpty()) {
                        $main->orWhere(function ($q) use ($tokens) {
                            foreach ($tokens as $t) {
                                $pattern = "%{$t}%";
                                $q->orWhere('title', 'ILIKE', $pattern)
                                    ->orWhere('description', 'ILIKE', $pattern);
                            }
                        });
                        Log::info('🔎 [TITLE/DESC SEARCH ADDED FOR NON-TECH]', ['tokens' => $tokens->all()]);
                    }

                    // 🟢 4. Resume->title da vergul bilan ajratilgan frazalar bo‘lsa — skill qidiruvi
                    $extraSkills = collect(preg_split('/\s*,\s*/u', (string) ($resume->title ?? '')))
                        ->map(fn($s) => trim($s))
                        ->filter(fn($s) => mb_strlen($s) > 2)
                        ->unique()
                        ->values();

                    if ($extraSkills->isNotEmpty()) {
                        $main->orWhere(function ($q) use ($extraSkills) {
                            foreach ($extraSkills as $skill) {
                                $pattern = "%{$skill}%";
                                $q->orWhere('title', 'ILIKE', $pattern)
                                    ->orWhere('description', 'ILIKE', $pattern);
                            }
                        });

                        Log::info('🧠 [TITLE-BASED SKILL SEARCH]', [
                            'resume_id' => $resume->id,
                            'skills' => $extraSkills->all(),
                        ]);
                    }
                });
            }

            Log::info("✅ [BUILD_LOCAL] Resume {$resume->id} (TECH=" . ($isTech ? 'YES' : 'NO') . ")");
            return $qb->orderByDesc('rank')->orderByDesc('id');
        };



        $localVacancies = collect($buildLocal(true)->limit(10)->get())
            ->take(10)
            ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id ? $v->external_id : "local_{$v->id}");


        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];

        // Exclude HH vacancies that were already matched for this resume earlier
        $existingHhExternalIds = DB::table('match_results as mr')
            ->join('vacancies as v', 'v.id', '=', 'mr.vacancy_id')
            ->where('mr.resume_id', $resume->id)
            ->where('v.source', 'hh')
            ->pluck('v.external_id')
            ->filter()
            ->all();
        foreach ($hhItems as $idx => $item) {
            $extId = $item['id'] ?? null;
            if (!$extId || $localVacancies->has($extId)) continue;
            $text = ($item['snippet']['requirement'] ?? '') . "\n" . ($item['snippet']['responsibility'] ?? '');
            if (!empty(trim($text))) {
                $vacanciesPayload[] = [
                    'id'          => null,
                    'text'        => mb_substr(strip_tags($text), 0, 1000),
                    'external_id' => $extId,
                    'raw'         => $item,
                    'source'      => 'hh',
                ];
            }
        }
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                'vacancy_id'   => $v->id,
                // 'title' => $v->title,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id'])
                && !$localVacancies->has($item['id'])
                && !in_array($item['id'], $existingHhExternalIds, true))
            ->take(10);
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
            $chunks = array_chunk($savedData, 200);

            DB::transaction(function () use ($chunks) {
                foreach ($chunks as $chunk) {
                    DB::table('match_results')->upsert(
                        $chunk,
                        ['resume_id', 'vacancy_id'],
                        ['score_percent', 'explanations', 'updated_at']
                    );
                }
            });
        }

        Log::info('All details took finished: ' . (microtime(true) - $start) . 's');

        Log::info('Matching finished', ['resume_id' => $resume->id]);

        return $savedData;
    }
}
