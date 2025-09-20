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
You are an assistant that cleans and standardizes job vacancy posts into a fixed JSON schema.

Rules:
- Output ONLY valid JSON. No extra text, no markdown, no comments.
- Do NOT translate. Keep the same language as in the input.
- Do NOT drop any job-related information.
- Remove stickers, ads, hashtags, channel signatures, unrelated links.

Schema:
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

Field rules:
- language: detect from the text.
- title: must be only the role/position. No generic words like "Vakansiya" or "Xodim kerak". If not explicit, infer from stack/skills.
- company: use company/brand name if written, else "".
- contact:
  - phones: extract all phone numbers, normalize by removing spaces/dashes/() only. Keep leading "+" if present. Deduplicate.
  - telegram_usernames: extract all "@user" and "t.me/user" → "@user". Lowercase. Deduplicate.
- description:
  - Put ALL remaining vacancy information (responsibilities, requirements, skills, salary, currency, bonuses, schedule, shift, format, contract, trial period, experience, location, deadlines, how to apply, preferred contact method/time, languages, start date, etc.).
  - Preserve numbers and currency exactly as in text.
  - Write neatly with proper punctuation, commas, spaces, and line breaks.

Input text:
"""
{$rawText}
"""

Context (do not include in output): source_username={$sourceUsername} message_id={$messageId}
PROMPT;

        $response = Http::withToken($apiKey)
            ->timeout(20)
            ->retry(2, 200)
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
