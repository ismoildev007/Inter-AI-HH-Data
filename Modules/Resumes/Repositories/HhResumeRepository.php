<?php

namespace Modules\Resumes\Repositories;

use App\Models\HhAccount;
use Illuminate\Support\Facades\Http;
use Modules\Resumes\Interfaces\HhResumeInterface;

class HhResumeRepository implements HhResumeInterface
{
    public function fetchMyResumes(HhAccount $account): array
    {
        if (!$account->access_token) {
            return [
                'success' => false,
                'message' => 'No HH account linked',
            ];
        }

        $url = 'https://api.hh.ru/resumes/mine';

        $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $account->access_token,
            'User-Agent' => 'InterAi/1.0 (+support@inter-ai.uz', 
        ])->get($url);

        if (!$resp->ok()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch resumes: ' . $resp->status(),
            ];
        }

        $data = $resp->json();

        return [
            'success' => true,
            'data' => $data,
        ];
    }
}