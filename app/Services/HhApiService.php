<?php

namespace App\Services;

use App\Models\Application;
use App\Models\HhAccount;
use App\Models\MatchResult;
use App\Models\Resume;
use App\Models\Vacancy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HhApiService
{
    protected string $baseUrl = 'https://api.hh.ru';

    /**
     * Send application to HH
     */
    // public function apply(Application $application)
    // {
    //     $hhAccount   = $application->user->hhAccount;
    //     $coverLetter = optional($application->user->preference)->cover_letter;

    //     if (!$hhAccount) {
    //         throw new \Exception("User does not have HH account connected.");
    //     }

    //     // Ensure valid token
    //     if ($hhAccount->expires_at->isPast()) {
    //         $this->refreshToken($hhAccount);
    //     }

    //     $vacancy = Vacancy::find($application->vacancy_id);
    //     if (!$vacancy || !$vacancy->external_id) {
    //         throw new \Exception("Vacancy external_id missing for application {$application->id}");
    //     }

    //     $multipart = [
    //         [
    //             'name'     => 'vacancy_id',
    //             'contents' => $vacancy->external_id,
    //         ],
    //         [
    //             'name'     => 'resume_id',
    //             'contents' => $application->hh_resume_id,
    //         ],
    //     ];

    //     if ($coverLetter) {
    //         $multipart[] = [
    //             'name'     => 'message',
    //             'contents' => $coverLetter,
    //         ];
    //     }

    //     Log::info('HH apply payload', [
    //         'vacancy_id'   => $vacancy->external_id,
    //         'resume_id'    => $application->hh_resume_id,
    //         'cover_letter' => $coverLetter,
    //     ]);

    //     // Send to HH API
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Bearer ' . $hhAccount->access_token,
    //         'HH-User-Agent' => 'InterAI/1.0 (support@inter-ai.uz)',
    //     ])
    //         ->asMultipart()
    //         ->post("{$this->baseUrl}/negotiations", $multipart);

    //     if ($response->failed()) {
    //         Log::error('HH Apply failed', [
    //             'application_id' => $application->id,
    //             'response'       => $response->json(),
    //         ]);
    //         throw new \Exception("HH API apply failed");
    //     }

    //     $data = $response->json();

    //     $application->update([
    //         'external_id' => $data['id'] ?? null,
    //         'hh_status'   => $data['state'] ?? 'response',
    //     ]);

    //     return $application;
    // }
    public function apply($id)
    {
        $user = auth()->user();

        $resumeId = $user->settings->resume_id;
        if (!$resumeId) {
            return response()->json([
                'success' => false,
                'message' => 'No primary resume set. Please set a primary resume in your settings.',
            ], 200);
        }

        $vacancy = Vacancy::where('external_id', $id)->firstOrFail();

        $userResume = Resume::where('user_id', $user->id)
            ->where('is_primary', true)
            ->firstOrFail();

        $matchResult = MatchResult::where('vacancy_id', $vacancy->id)
            ->where('resume_id', $userResume->id)
            ->first();

        $coverLetter = $user->preference?->cover_letter ?? null;

        return DB::transaction(function () use ($user, $vacancy, $resumeId, $coverLetter, $matchResult, $userResume) {
            $existing = Application::where('user_id', $user->id)
                ->where('vacancy_id', $vacancy->id)
                ->first();

            if ($existing && $existing->status === 'response') {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already responded to this vacancy.',
                ], 409);
            }

            $application = Application::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'vacancy_id' => $vacancy->id,
                    'resume_id'  => $userResume->id,
                ],
                [
                    'hh_resume_id' => $resumeId,
                    'status'       => 'response',
                    'submitted_at' => now(),
                    'match_score'  => $matchResult?->score_percent,
                    'external_id'  => $vacancy->external_id,
                ]
            );

            // Only call HH API if it's a new application or hh_status is null/failed
            if ($vacancy->external_id && $resumeId && (!$existing || $existing->hh_status !== 'response')) {
                try {
                    app(\Modules\Vacancies\Interfaces\HHVacancyInterface::class)
                        ->applyToVacancy($vacancy->external_id, $resumeId, $coverLetter);

                    $application->update(['hh_status' => 'response']);
                } catch (\Throwable $e) {
                    Log::error("HH apply failed", ['error' => $e->getMessage()]);
                    $application->update(['hh_status' => 'failed']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Application submitted successfully.',
                'data'    => $application,
            ]);
        });
    }

    /**
     * Refresh expired access token
     */
    protected function refreshToken(HhAccount $account)
    {
        $response = Http::asForm()->post('https://hh.ru/oauth/token', [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id'     => config('services.hh.client_id'),
            'client_secret' => config('services.hh.client_secret'),
        ]);

        if ($response->failed()) {
            Log::error('Failed to refresh HH token', ['response' => $response->json()]);
            throw new \Exception("Failed to refresh HH token");
        }

        $data = $response->json();

        $account->update([
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $account->refresh_token,
            'expires_at'    => Carbon::now()->addSeconds($data['expires_in']),
        ]);
    }
}
