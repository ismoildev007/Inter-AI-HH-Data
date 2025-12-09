<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiTranscriptionService
{
    public function transcribe(string $filePath): ?string
    {
        $apiKey = (string) config('services.gemini.api_key');

        if ($apiKey === '') {
            Log::warning('GeminiTranscriptionService: GEMINI_API_KEY is not configured');

            return null;
        }

        $mimeType = mime_content_type($filePath) ?: 'audio/wav';
        $audioBytes = base64_encode((string) file_get_contents($filePath));

        $payload = [
            'contents' => [[
                'parts' => [
                    [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $audioBytes,
                        ],
                    ],
                    [
                        'text' => 'This is Uzbek audio, please transcribe the speech in Uzbek using Latin script only. Do not translate to another language and do not add any explanations, timestamps, or labels. Return only the clean transcript text.',
                    ],
                ],
            ]],
            'generation_config' => [
                'temperature' => 0,
            ],
        ];

        try {
            $response = Http::retry(3, 2000)
                ->timeout(120)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key='.$apiKey,
                    $payload
                );

            Log::info('GeminiTranscriptionService: raw response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'headers' => $response->headers(),
            ]);

            if ($response->failed()) {
                Log::error('GeminiTranscriptionService: transcription failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            $text = data_get($data, 'candidates.0.content.parts.0.text');

            return $text !== null ? trim((string) $text) : null;
        } catch (\Throwable $e) {
            Log::error('GeminiTranscriptionService: exception', ['exception' => $e]);

            return null;
        }
    }
}
