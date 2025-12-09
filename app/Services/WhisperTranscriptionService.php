<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhisperTranscriptionService
{
    public function transcribe(string $filePath, ?string $language = null): ?string
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            Log::warning('WhisperTranscriptionService: OPENAI_API_KEY is not configured');

            return null;
        }

        try {
            $params = [
                'file' => fopen($filePath, 'r'),
                'model' => 'whisper-1',
            ];

            if ($language !== null && $language !== '') {
                $params['language'] = $language;
            }

            $response = Http::asMultipart()
                ->withToken($apiKey)
                ->post('https://api.openai.com/v1/audio/transcriptions', $params);

            Log::info('WhisperTranscriptionService: raw response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            if ($response->failed()) {
                Log::error('WhisperTranscriptionService: transcription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return isset($data['text']) ? trim((string) $data['text']) : null;
        } catch (\Throwable $e) {
            Log::error('WhisperTranscriptionService: exception', ['exception' => $e]);

            return null;
        }
    }
}
