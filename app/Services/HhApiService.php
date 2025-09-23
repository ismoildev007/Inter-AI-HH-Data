<?php

namespace App\Services;

use App\Models\Application;
use App\Models\HhAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HhApiService
{
    protected string $baseUrl = 'https://api.hh.ru';

    /**
     * Send application to HH
     */
    public function apply(Application $application)
    {
        $hhAccount = $application->user->hhAccount;

        if (!$hhAccount) {
            throw new \Exception("User does not have HH account connected.");
        }

        // Ensure valid token
        if ($hhAccount->expires_at->isPast()) {
            $this->refreshToken($hhAccount);
        }

        // Send to HH API
        $response = Http::withToken($hhAccount->access_token)
            ->post($this->baseUrl . '/negotiations', [
                'vacancy_id' => $application->vacancy_id,
                'resume_id'  => $application->hh_resume_id,
            ]);

        if ($response->failed()) {
            Log::error('HH Apply failed', [
                'application_id' => $application->id,
                'response' => $response->json(),
            ]);
            throw new \Exception("HH API apply failed");
        }

        $data = $response->json();

        $application->update([
            'external_id' => $data['id'] ?? null,
            'hh_status'   => $data['state'] ?? 'response',
        ]);

        return $application;
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
