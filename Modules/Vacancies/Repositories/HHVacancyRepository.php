<?php

namespace Modules\Vacancies\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;

class HHVacancyRepository implements HHVacancyInterface
{
    protected string $baseUrl = 'https://api.hh.ru';

    protected function http()
    {
        return Http::acceptJson()->withHeaders([
            'User-Agent' => 'InterAI/1.0 (+support@inter-ai.uz)',
        ]);
    }

    public function search(string $query, int $page = 0, int $perPage = 40, array $options = []): array
    {
        $params = array_merge([
            'text'     => $query,
            'page'     => $page,
            'per_page' => $perPage,
        ], $options);
        $response = $this->http()->get("{$this->baseUrl}/vacancies", $params);


        if ($response->failed()) {
            throw new \RuntimeException("HH API search failed: " . $response->body());
        }

        return $response->json();
    }


    public function getById(string $id): array
    {
        $response = $this->http()->get("{$this->baseUrl}/vacancies/{$id}");

        if ($response->failed()) {
            throw new \RuntimeException("HH API getById failed: " . $response->body());
        }

        return $response->json();
    }

    public function applyToVacancy(string $vacancyId, string $resumeId, ?string $coverLetter = null): array
    {
        $user = Auth::user();
        $token = optional($user->hhAccount)->access_token;

        if (!$token) {
            return [
                'success' => false,
                'message' => 'No HH account linked',
            ];
        }

        $multipart = [
            [
                'name'     => 'vacancy_id',
                'contents' => $vacancyId,
            ],
            [
                'name'     => 'resume_id',
                'contents' => $resumeId,
            ],
        ];

        if ($coverLetter) {
            $multipart[] = [
                'name'     => 'message',
                'contents' => $coverLetter,
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'HH-User-Agent' => 'InterAI/1.0 (support@inter-ai.uz)',
        ])->asMultipart()->post("{$this->baseUrl}/negotiations", $multipart);

        if ($response->failed()) {
            Log::info('HH API apply failed', ['response' => $response->body()]);
            return [
                'success' => false,
                'message' => 'HH API apply failed: ' . $response->body(),
            ];
        }

        Log::info('HH API apply succeeded', ['response' => $response->body()]);

        return [
            'success' => true,
            'data'    => $response->json(),
        ];
    }
}
