<?php

namespace Modules\Users\Repositories;

use App\Models\HhAccount;
use App\Models\UserSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class HhAccountRepository implements HhAccountRepositoryInterface
{
    private function cfg(string $key, ?string $default = null): ?string
    {
        // Prefer config('services.hh.*') if present, fallback to env('HH_*')
        $fromConfig = config('services.hh.' . $key);
        if (!is_null($fromConfig)) {
            return (string) $fromConfig;
        }
        $envKey = 'HH_' . strtoupper($key);
        return env($envKey, $default);
    }

    public function createAuthorizeUrl(?int $userId, ?string $redirectUri = null, array $scopes = []): array
    {
        $clientId = $this->cfg('client_id');
        $baseUrl = rtrim($this->cfg('base_url', 'https://hh.ru') ?? 'https://hh.ru', '/');
        $authorizePath = ltrim($this->cfg('authorize_path', '/oauth/authorize') ?? '/oauth/authorize', '/');
        $redirect = $redirectUri ?: $this->cfg('redirect_uri');
        $scope = implode(' ', $scopes ?: preg_split('/\s+/', (string) ($this->cfg('default_scopes', 'applicant') ?? 'applicant')));

        if (!$clientId || !$redirect) {
            throw new RuntimeException('HH OAuth not configured: client_id/redirect_uri missing');
        }

        // PKCE parameters
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $state = Str::uuid()->toString();

        // Persist state for callback verification (cache only, no DB)
        Cache::put(
            'hh:oauth:state:' . $state,
            [
                'code_verifier' => $codeVerifier,
                'code_challenge' => $codeChallenge,
                'code_challenge_method' => 'S256',
                'redirect_uri' => $redirect,
                'scope' => $scope,
                'user_id' => $userId,
            ],
            now()->addMinutes(15)
        );

        // Build authorization URL
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirect,
            'scope' => $scope,
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return [
            'url' => $baseUrl . '/' . trim($authorizePath, '/') . '?' . $query,
            'state' => $state,
            'redirect_uri' => $redirect,
        ];
    }

    public function handleCallback(string $code, string $state): HhAccount
    {
        $oauth = Cache::get('hh:oauth:state:' . $state);
        if (!$oauth) {
            throw new RuntimeException('Invalid or expired OAuth state');
        }

        $clientId = $this->cfg('client_id');
        $clientSecret = $this->cfg('client_secret');
        $baseUrl = rtrim($this->cfg('base_url', 'https://hh.ru') ?? 'https://hh.ru', '/');
        $tokenPath = ltrim($this->cfg('token_path', '/oauth/token') ?? '/oauth/token', '/');

        if (!$clientId) {
            throw new RuntimeException('HH OAuth not configured: client_id missing');
        }

        $payload = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'redirect_uri' => $oauth['redirect_uri'] ?? null,
        ];

        if ($clientSecret) {
            $payload['client_secret'] = $clientSecret;
        } else {
            if (empty($oauth['code_verifier'])) {
                throw new RuntimeException('Missing code_verifier for PKCE flow');
            }
            $payload['code_verifier'] = $oauth['code_verifier'];
        }

        // Exchange code for token
        $resp = Http::asForm()->post($baseUrl . '/' . trim($tokenPath, '/'), $payload);
        if (!$resp->ok()) {
            throw new RuntimeException('Failed to exchange code for token: ' . $resp->status());
        }

        $data = $resp->json();
        $accessToken = Arr::get($data, 'access_token');
        $refreshToken = Arr::get($data, 'refresh_token');
        $expiresIn = Arr::get($data, 'expires_in');
        $scope = Arr::get($data, 'scope', $oauth['scope'] ?? null);

        if (!$accessToken) {
            throw new RuntimeException('Token response missing access_token');
        }

        $expiresAt = $expiresIn ? now()->addSeconds((int) $expiresIn) : null;

        $account = HhAccount::updateOrCreate(
            ['user_id' => $oauth['user_id'] ?? null], 
            [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at'    => $expiresAt,
                'scope'         => is_array($scope) ? implode(' ', $scope) : (string) $scope,
                'raw_json'      => $data,
            ]
        );

        $url = 'https://api.hh.ru/resumes/mine';

        $resumesResp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'User-Agent' => 'InterAi/1.0 (+support@inter-ai.uz',
        ])->get($url);
        Log::info(['resume' => $resumesResp->json()]);

        if ($resumesResp->ok()) {
            $resumes = $resumesResp->json()['items'] ?? [];

            // find active resume
            $activeResume = collect($resumes)->firstWhere('status.id', 'published');
            Log::info(['active resume' => $activeResume]);
            if ($activeResume) {
                UserSetting::updateOrCreate(
                    ['user_id' => $oauth['user_id']],
                    ['resume_id' => $activeResume['id']]
                );
            }
        }

        Cache::forget('hh:oauth:state:' . $state);
        return $account;
    }

    public function attachToUser(int $accountId, int $userId): HhAccount
    {
        $account = HhAccount::findOrFail($accountId);
        $account->user_id = $userId;
        $account->save();
        return $account;
    }

    public function findForUser(int $userId): ?HhAccount
    {
        return HhAccount::where('user_id', $userId)->first();
    }

    public function refreshToken(HhAccount $account): HhAccount
    {
        $clientId = $this->cfg('client_id');
        $clientSecret = $this->cfg('client_secret');
        $baseUrl = rtrim($this->cfg('base_url', 'https://hh.ru') ?? 'https://hh.ru', '/');
        $tokenPath = ltrim($this->cfg('token_path', '/oauth/token') ?? '/oauth/token', '/');
        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id' => $clientId,
        ];

        if ($clientSecret) {
            $payload['client_secret'] = $clientSecret;
        }
        $resp = Http::asForm()->post($baseUrl . '/' . trim($tokenPath, '/'), $payload);

        if (!$resp->ok()) {
            throw new RuntimeException('Failed to refresh token: ' . $resp->status());
        }

        $data = $resp->json();
        $accessToken = Arr::get($data, 'access_token');
        $refreshToken = Arr::get($data, 'refresh_token');
        $expiresIn = Arr::get($data, 'expires_in');

        if (!$accessToken) {
            throw new RuntimeException('Token response missing access_token');
        }

        $account->access_token = $accessToken;
        $account->refresh_token = $refreshToken;
        $account->expires_at = $expiresIn ? now()->addSeconds((int) $expiresIn) : null;
        $account->raw_json = $data;
        $account->save();

        return $account;
    }
}
