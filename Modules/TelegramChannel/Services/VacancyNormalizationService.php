<?php

namespace Modules\TelegramChannel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VacancyNormalizationService
{
    /**
     * Normalize a raw vacancy text into a structured array using OpenAI.
     * - Keeps original language (no translation)
     * - Extracts title, company, contacts and description
     */
    public function normalize(string $rawText, string $sourceUsername, int $messageId): array
    {
        $model = config('telegramchannel.openai_model', env('OPENAI_MODEL', 'gpt-4.1-nano'));
        $apiKey = config('telegramchannel.openai_key', env('OPENAI_API_KEY'));

        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $prompt = <<<PROMPT
You are an expert assistant that standardizes employer job vacancy posts into a fixed JSON schema.
Rules:
- DO NOT translate: keep the language exactly as in the input.
- Output ONLY valid JSON, no extra text.
- Extract and normalize fields: title, company, contact, description, language.
- title MUST be the job role/position (e.g., "Frontend React Developer") and MUST NOT be generic slogans/tags like "Xodim kerak", "Ish joyi kerak", "Vakansiya", "Resume", "CV". If necessary, infer a concise role from the technologies mentioned.
- contact: phones (array) and telegram_usernames (array). Do not mask. Convert any "t.me/username" to "@username". Remove duplicates.
- Remove channel signatures, ads, and unrelated links from description; keep only job-relevant info (requirements, conditions, salary if present).
- If a field is missing, return empty string or empty array for it.

Required JSON shape:
{
  "language": "uz|ru|en|...",
  "title": "string",
  "company": "string",
  "contact": {
    "phones": ["+998...", "..."],
    "telegram_usernames": ["@user", "@user2"]
  },
  "description": "string"
}

Input text:
"""
{$rawText}
"""

Context (do not include in output): source_username={$sourceUsername} message_id={$messageId}
PROMPT;

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You format vacancies into a fixed JSON schema.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
            ]);

        if ($response->failed()) {
            Log::error('Vacancy normalization failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OpenAI request failed: '.$response->status());
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $content = trim($content);
        $content = preg_replace('/^```(json)?/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON from OpenAI: '.$content);
        }

        // Ensure shape
        $normalized = [
            'language' => (string) ($data['language'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'company' => (string) ($data['company'] ?? ''),
            'contact' => [
                'phones' => array_values(array_filter(array_map('strval', (array) ($data['contact']['phones'] ?? [])))),
                'telegram_usernames' => array_values(array_filter(array_map('strval', (array) ($data['contact']['telegram_usernames'] ?? [])))),
            ],
            'description' => (string) ($data['description'] ?? ''),
        ];

        return $normalized;
    }
}
