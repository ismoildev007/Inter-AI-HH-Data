<?php

namespace Modules\Vacancies\Services;

use App\Models\Resume;
use App\Models\MatchResult;
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
        Log::info('Starting vacancy match', ['resume_id' => $resume->id, 'query' => $query]);
        $hhVacacnies = $this->hhRepository->search($query);
        $vacancies = $hhVacacnies['items'] ?? [];

        if (empty($vacancies)) {
            return [];
        }

        $resumes = [$resume->parsed_text ?? $resume->description];
        $vacancyTexts = [];
        foreach ($vacancies as $v) {
            if (!empty($v['id'])) {
                $full = $this->hhRepository->getById($v['id']); // fetch full vacancy
                if (!empty($full['description'])) {
                    $vacancyTexts[] = strip_tags($full['description']); // clean HTML
                }
            }
        }
        $url = 'https://python.inter-ai.uz/bulk-match';
        Log::info('Calling matcher API', ['url' => $url]);
        Log::info('Number of vacancies to match', ['count' => count($vacancyTexts)]);
        Log::info('Number of resumes to match', ['count' => count($resumes)]);


        $response = Http::timeout(30)->post($url, [
            'resumes'   => $resumes,
            'vacancies' => $vacancyTexts,
            'top_k'     => 20,
            'min_score' => 0,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Matcher API failed: " . $response->body());
        }

        $results = $response->json();
        $matches = $results['results'][0] ?? [];
        $saved = [];
        foreach ($matches as $match) {
            if ($match['score'] >= 70) {
                $vacancyData = $vacancies[$match['vacancy_index']] ?? null;
                if (!$vacancyData) {
                    continue;
                }

                $vacancy = $this->vacancyRepository->firstOrCreateFromHH($vacancyData);

                $saved[] = MatchResult::updateOrCreate(
                    [
                        'resume_id'  => $resume->id,
                        'vacancy_id' => $vacancy->id,
                    ],
                    [
                        'score_percent' => $match['score'],
                        'explanations'  => json_encode($match),
                    ]
                );
            }
        }

        return $saved;
    }
}
