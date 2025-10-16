<?php

namespace Modules\Vacancies\Repositories;

use App\Models\HhAccount;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Vacancies\Interfaces\HHVacancyInterface;
use Modules\Users\Repositories\HhAccountRepositoryInterface;
use Stichoza\GoogleTranslate\GoogleTranslate;

class HHVacancyRepository implements HHVacancyInterface
{
    protected string $baseUrl = 'https://api.hh.ru';

    protected function http()
    {
        return Http::acceptJson()->withHeaders([
            'User-Agent' => 'InterAI/1.0 (+support@inter-ai.uz)',
        ]);
    }

    // public function search(string $query, int $page = 0, int $perPage = 100, array $options = ['area' => 97]): array
    // {
    //     $dateFrom = now()->subMonth()->startOfDay()->toIso8601String();
    //     $dateTo   = now()->endOfDay()->toIso8601String();

    //     $params = array_merge([
    //         'text'      => $query,
    //         'page'      => $page,
    //         'per_page'  => $perPage,
    //         'archived'  => false,
    //         'date_from' => $dateFrom,
    //         'date_to'   => $dateTo,
    //     ], $options);

    //     $response = $this->http()->get("{$this->baseUrl}/vacancies", $params);

    //     if ($response->failed()) {
    //         throw new \RuntimeException("HH API search failed: " . $response->body());
    //     }

    //     return $response->json();
    // }

    // public function search(string $query, int $page = 0, int $perPage = 100, array $options = ['area' => 97]): array
    // {
    //     $query = trim($query);
    //     if ($query === '') {
    //         Log::warning('HH search called with empty query');
    //         return ['items' => []];
    //     }

    //     $terms = array_filter(array_map('trim', explode(',', $query)));

    //     $perPage = min($perPage, 100);

    //     $dateFrom = now()->subDays(30)->startOfDay()->toIso8601String();
    //     $dateTo   = now()->endOfDay()->toIso8601String();

    //     $baseParams = [
    //         'page'       => $page,
    //         'per_page'   => $perPage,
    //         'archived'   => false,
    //         'date_from'  => $dateFrom,
    //         'date_to'    => $dateTo,
    //     ] + $options;

    //     $mergedItems = [];

    //     foreach ($terms as $term) {
    //         $cacheKey = "hh:search:" . md5("{$term}:{$page}:{$perPage}:" . json_encode($options));

    //         $data = cache()->remember($cacheKey, now()->addMinutes(30), function () use ($term, $baseParams) {
    //             $params = ['text' => $term] + $baseParams;

    //             try {
    //                 $response = $this->http()->get("{$this->baseUrl}/vacancies", $params);

    //                 if ($response->failed()) {
    //                     Log::error('HH API request failed', [
    //                         'term' => $term,
    //                         'params' => $params,
    //                         'body' => $response->body(),
    //                     ]);
    //                     return ['items' => []];
    //                 }

    //                 $json = $response->json();
    //                 return is_array($json) ? $json : ['items' => []];
    //             } catch (\Throwable $e) {
    //                 Log::error('HH API exception', [
    //                     'term' => $term,
    //                     'message' => $e->getMessage(),
    //                 ]);
    //                 return ['items' => []];
    //             }
    //         });

    //         if (isset($data['items']) && is_array($data['items'])) {
    //             $mergedItems = array_merge($mergedItems, $data['items']);
    //         }
    //     }

    //     // 🔹 Deduplicate by vacancy ID
    //     $mergedItems = collect($mergedItems)
    //         ->filter(fn($v) => isset($v['id']))
    //         ->unique('id')
    //         ->values()
    //         ->all();

    //     Log::info('HH search completed', [
    //         'query' => $query,
    //         'terms' => $terms,
    //         'total_items' => count($mergedItems),
    //     ]);

    //     return ['items' => $mergedItems];
    // }

    public function search(string $query, int $page = 0, int $perPage = 100, array $options = ['area' => 97]): array
    {
        $query = trim($query);
        if ($query === '') {
            Log::warning('HH search called with empty query');
            return ['items' => []];
        }

        // 🔹 Split multiple comma-separated queries
        $terms = array_filter(array_map('trim', explode(',', $query)));

        // 🔹 Translate each term into English
        $translator = new GoogleTranslate();
        $translator->setSource('uz'); // auto-detect source
        $translator->setTarget('en');
        $translatedTerms = [];
        foreach ($terms as $term) {
            try {
                $translated = $translator->translate(trim($term));
                $translatedTerms[] = ucfirst($translated);
            } catch (\Throwable $e) {
                Log::warning("Translation failed for term '{$term}': " . $e->getMessage());
                $translatedTerms[] = $term;
            }
        }
        $perPage = min($perPage, 100);

        $dateFrom = now()->subDays(30)->startOfDay()->toIso8601String();
        $dateTo   = now()->endOfDay()->toIso8601String();

        $baseParams = [
            'page'       => $page,
            'per_page'   => $perPage,
            'archived'   => false,
            'date_from'  => $dateFrom,
            'date_to'    => $dateTo,
        ] + $options;

        $mergedItems = [];

        foreach ($translatedTerms as $term) {
            $cacheKey = "hh:search:" . md5("{$term}:{$page}:{$perPage}:" . json_encode($options));

            $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($term, $baseParams) {
                $params = ['text' => $term] + $baseParams;

                try {
                    $response = $this->http()->get("{$this->baseUrl}/vacancies", $params);

                    if ($response->failed()) {
                        Log::error('HH API request failed', [
                            'term' => $term,
                            'params' => $params,
                            'body' => $response->body(),
                        ]);
                        return ['items' => []];
                    }

                    $json = $response->json();
                    return is_array($json) ? $json : ['items' => []];
                } catch (\Throwable $e) {
                    Log::error('HH API exception', [
                        'term' => $term,
                        'message' => $e->getMessage(),
                    ]);
                    return ['items' => []];
                }
            });

            if (isset($data['items']) && is_array($data['items'])) {
                $mergedItems = array_merge($mergedItems, $data['items']);
            }
        }

        // 🔹 Deduplicate by vacancy ID
        $mergedItems = collect($mergedItems)
            ->filter(fn($v) => isset($v['id']))
            ->unique('id')
            ->values()
            ->all();

        Log::info('HH search completed', [
            'original_query'   => $query,
            'translated_terms' => $translatedTerms,
            'total_items'      => count($mergedItems),
        ]);

        return ['items' => $mergedItems];
    }


    public function getById(string $id): array
    {
        $response = $this->http()->get("{$this->baseUrl}/vacancies/{$id}");
        if ($response->failed()) {
            return [
                'status' => false,
                'message' => 'vacancy not found'
            ];
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

    public function listNegotiations(int $page = 0, int $perPage = 100, ?HhAccount $account = null): array
    {
        $account = $account ?: optional(Auth::user())->hhAccount;
        if (!$account || !$account->access_token) {
            return [
                'success' => false,
                'message' => 'No HH account linked for negotiations',
                'status'  => 401,
            ];
        }

        $makeRequest = function (HhAccount $acc) use ($page, $perPage) {
            return Http::withHeaders([
                'Authorization' => 'Bearer ' . $acc->access_token,
                'HH-User-Agent' => 'InterAI/1.0 (support@inter-ai.uz)',
                'User-Agent'    => 'InterAI/1.0 (+support@inter-ai.uz)',
            ])->get("{$this->baseUrl}/negotiations", [
                'page' => $page,
                'per_page' => $perPage,
            ]);
        };

        $response = $makeRequest($account);

        if ($response->status() === 401) {
            try {
                /** @var HhAccountRepositoryInterface $repo */
                $repo = app(HhAccountRepositoryInterface::class);
                $account = $repo->refreshToken($account);
                $response = $makeRequest($account);
            } catch (\Throwable $e) {
                Log::warning('HH negotiations token refresh failed', ['error' => $e->getMessage()]);
            }
        }

        if ($response->failed()) {
            Log::info('HH API negotiations fetch failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return [
                'success' => false,
                'message' => 'HH API negotiations failed: ' . $response->status(),
                'status'  => $response->status(),
            ];
        }

        return [
            'success' => true,
            'data'    => $response->json(),
        ];
    }
}
