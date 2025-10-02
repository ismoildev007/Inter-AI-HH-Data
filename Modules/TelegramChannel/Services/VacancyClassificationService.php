<?php

namespace Modules\TelegramChannel\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VacancyClassificationService
{
    /**
     * Classify a raw vacancy-like text.
     * Returns [label => employer_vacancy|job_seeker|mentoring|other, confidence => 0..1, language => code]
     */
    public function classify(string $rawText): array
    {
        $apiKey = config('telegramchannel.openai_key', env('OPENAI_API_KEY'));
        $model  = config('telegramchannel.openai_model', env('OPENAI_MODEL', 'gpt-4.1-nano'));

        if (!$apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $system = 'You are a precise classifier. Output JSON only.';
        $prompt = <<<PROMPT
Classify the following text into one of four categories:
- employer_vacancy: An employer/company posts a job vacancy (role/position is offered by a company; third-person or company tone; hiring language like "Xodim kerak", "Vakansiya", "Hiring").
- job_seeker: A person seeking a job (first-person tone or banners like "Ish joyi kerak", "Ish qidiryapman", "Ищу работу", "Резюме", "Resume", "CV").
- mentoring: Mentorship/apprenticeship (ustoz/shogird/mentor/nastavnik), not a standard employer vacancy.
- other: Anything else.

Strict rules:
- If the text starts with or strongly contains phrases such as "Ish joyi kerak", "Ish qidiryapman", "Ищу работу", "Резюме", "Resume", "CV" then label = job_seeker, even if company-like fields appear.
- Only use employer_vacancy if it clearly states the company is hiring (third-person/company voice) and provides vacancy details.
- DO NOT translate; detect the main language (e.g., "uz", "ru", "en").
- Return only JSON with fields: label, confidence (0..1), language.

Text:
"""
{$rawText}
"""

Return JSON like: {"label":"employer_vacancy","confidence":0.88,"language":"uz"}
PROMPT;

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->retry(3, fn ($attempt) => [500, 2000, 5000][$attempt - 1] ?? 5000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.1,
            ]);

        if ($response->failed()) {
            Log::error('Vacancy classification failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('OpenAI classification request failed: '.$response->status());
        }

        $content = (string) $response->json('choices.0.message.content', '');
        $content = trim($content);
        $content = preg_replace('/^```(json)?/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid JSON from OpenAI (classification): '.$content);
        }

        return [
            'label'     => (string) ($data['label'] ?? ''),
            'confidence'=> (float)  ($data['confidence'] ?? 0.0),
            'language'  => (string) ($data['language'] ?? ''),
        ];
    }
}
