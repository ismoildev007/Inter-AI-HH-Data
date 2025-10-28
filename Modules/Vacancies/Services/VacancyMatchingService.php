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

        $multiWords = array_unique(array_merge(
            ...array_map(fn($q) => array_map('trim', explode(',', $q)), $allVariants)
        ));
        Log::info('Searching vacancies for terms', ['terms' => $allVariants, 'multi_words' => $multiWords]);


        [$hhVacancies, $localVacancies] = Concurrency::run([
            fn() => cache()->remember(
                "hh:search:{$query}:area97",
                now()->addMinutes(30),
                fn() => $this->hhRepository->search($query, 0, 200, ['area' => 97])
            ),
            fn() => DB::table('vacancies')
                ->where('status', 'publish')
                ->where('source', 'telegram')
                ->whereNotIn('id', function ($q) use ($resume) {
                    $q->select('vacancy_id')
                        ->from('match_results')
                        ->where('resume_id', $resume->id);
                })
                ->whereRaw("
                        (
                            title ILIKE ANY (ARRAY['%" . implode("%','%", $multiWords) . "%']) OR
                            description ILIKE ANY (ARRAY['%" . implode("%','%", $multiWords) . "%'])
                        )
                        OR title ILIKE '%{$latinQuery}%'
                        OR description ILIKE '%{$latinQuery}%'
                        OR title ILIKE '%{$cyrilQuery}%'
                        OR description ILIKE '%{$cyrilQuery}%'
                    ")
                ->select('id', 'title', 'description', 'source', 'external_id')
                ->limit(200)
                ->orderByDesc('id')
                ->get()
                ->keyBy(fn($v) => $v->source === 'hh' && $v->external_id
                    ? $v->external_id
                    : "local_{$v->id}")

        ]);

        Log::info('Data fetch took:' . (microtime(true) - $start) . 's');
        Log::info('Local vacancies: ' . $localVacancies->count());
        Log::info('hh vacancies count: ' . count($hhVacancies['items'] ?? []));

        $hhItems = $hhVacancies['items'] ?? [];
        $vacanciesPayload = [];

        foreach ($localVacancies as $v) {
            $vacanciesPayload[] = [
                'id'   => $v->id,
                // 'title' => $v->title,
                'text' => mb_substr(strip_tags($v->description), 0, 2000),
            ];
        }
        $toFetch = collect($hhItems)
            ->filter(fn($item) => isset($item['id']) && !$localVacancies->has($item['id']))
            ->take(1000);
        foreach ($toFetch as $item) {
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
                ];
            }
        }
        if (empty($vacanciesPayload)) {
            Log::info('No vacancies to match for resume', ['resume_id' => $resume->id]);
            return [];
        }
        Log::info('Prepared payload with ' . count($vacanciesPayload) . ' vacancies');
        // Python matcher disabled: save zero-score matches directly
        $vacancyMap = collect($vacanciesPayload)->keyBy(fn($v, $k) => $v['id'] ?? "new_{$k}");

        $savedData = [];
        foreach ($vacanciesPayload as $idx => $payload) {
            $vac = null;

            if (!empty($payload['id'])) {
                $vac = Vacancy::find($payload['id']);
            }

            if (!$vac && isset($payload['external_id'])) {
                $vac = Vacancy::where('source', 'hh')
                    ->where('external_id', $payload['external_id'])
                    ->first();

                if (!$vac && isset($payload['raw'])) {
                    try {
                        $vac = $this->vacancyRepository->createFromHH($payload['raw']);
                    } catch (\Throwable $e) {
                        Log::warning('CreateFromHH failed', [
                            'external_id' => $payload['external_id'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            if (!$vac) {
                continue;
            }

            $savedData[] = [
                'resume_id'     => $resume->id,
                'vacancy_id'    => $vac->id,
                'score_percent' => 0,
                'explanations'  => json_encode([
                    'source' => $vac->source,
                    'via' => 'zero-score-direct',
                    'vacancy_index' => $idx,
                ]),
                'updated_at'    => now(),
                'created_at'    => now(),
            ];
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
