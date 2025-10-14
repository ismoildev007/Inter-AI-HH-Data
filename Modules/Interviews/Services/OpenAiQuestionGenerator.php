<?php

namespace Modules\Interviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAiQuestionGenerator implements AiQuestionGeneratorInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected int $retries;

    public function __construct()
    {
        $this->apiKey =  env('OPENAI_API_KEY');
        $this->model = 'gpt-4.1-nano';
        $this->timeout =  20;
        $this->retries =  2;
    }

    public function generate(string $title, ?string $company, ?string $description, ?string $language, int $count = 20): array
    {
        $language = $language && $language !== 'auto' ? $language : 'en';
        $count = max(1, min(50, $count));

        $prompt = $this->buildPrompt($title, $company, $description, $language, $count);
        Log::info('OpenAI prompt', ['title' => $title, 'company' => $company, 'language' => $language, 'count' => $count]);
        $attempts = 0;
        do {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->acceptJson()
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are a seasoned technical and HR interviewer who generates professional interview questions.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ],
                        ],
                        'temperature' => 0.2,
                        'max_tokens' => 800,
                    ]);
                Log::info('OpenAI response', ['status' => $response->status()]);

                if ($response->failed()) {
                    Log::error('OpenAI API error', ['status' => $response->status(), 'body' => $response->body()]);
                    throw new \RuntimeException('OpenAI error: ' . $response->body());
                }

                $text = (string) data_get($response->json(), 'choices.0.message.content', '');
                if (!$text) {
                    $text = (string) data_get($response->json(), 'choices.0.message.content', '');
                }

                $questions = $this->extractQuestions($text, $count);
                if (count($questions) >= min(5, $count)) {
                    Log::info('Generated questions', ['count' => count($questions), 'sample' => array_slice($questions, 0, 10)]);
                    return array_slice($questions, 0, $count);
                }

                throw new \RuntimeException('Not enough questions parsed');
            } catch (\Throwable $e) {
                $lastException = $e;
                usleep(200_000 * ($attempts + 1));
            }
        } while (++$attempts <= $this->retries);
        throw new \RuntimeException('OpenAI generation failed after ' . $this->retries . ' retries', 0, $lastException ?? null);

    }

    protected function buildPrompt(string $title, ?string $company, ?string $description, string $language, int $count): string
    {
        $ctx = trim((string) $description);
        $ctx = Str::of($ctx)->replace(["\r", "\n\n"], ["\n", "\n"])->limit(4000)->toString();
        $companyText = $company ? $company : 'Unknown company';

        return <<<PROMPT
You are a seasoned technical/HR interviewer. Based on the job below, craft exactly {$count} distinct, role-specific interview questions.

Language:
- Write all questions in "{$language}".
- If the requested language seems inappropriate for the context, default to English.

Coverage and quality:
- Target the described role and its seniority. Use concrete tools, frameworks, and domain terms from the job.
- Balance breadth and depth across: responsibilities, core skills, practical implementation, troubleshooting, architecture/design, trade-offs, product/business context, collaboration, and behavioral aspects.
- Prefer scenario- and problem-based questions over trivia. Avoid yes/no questions and redundancy.

Style constraints:
- Each question must be a single concise sentence (max 20 words), clear and unambiguous.
- Do NOT include explanations, examples, or answers.
- Do NOT include quotes or extra symbols.
- Do NOT end lines with any punctuation (no ?, ., !). The consumer will add punctuation.
- Output format: a numbered list, one question per line, strictly:
  1. Question text
  2. Question text
  ...
  {$count}. Question text

Context:
- Role title: {$title}
- Company: {$companyText}
- Job description (responsibilities, requirements, stack, tools, domain):
{$ctx}

Now produce exactly {$count} lines as specified above, with no preamble or postscript.
PROMPT;
    }

    protected function extractQuestions(string $text, int $count): array
    {
        $lines = preg_split('/\r?\n/', trim($text));
        $qs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+\.|^-\s+|^\*\s+/', '', $line) ?: $line;
            if ($line !== '' && mb_strlen($line) > 2) {
                $qs[] = Str::of($line)->trim()->trim('.') . '?';
            }
        }
        $qs = array_values(array_unique($qs));
        return array_slice($qs, 0, $count);
    }

    protected function fallback(string $title, ?string $company, string $language, int $count): array
    {
        $base = [
            'Bu lavozimda asosiy masʼuliyatlaringiz nimalardan iborat bo‘ladi?',
            'Oldingi tajribangizdan bu rolga mos keladigan misollar keltiring.',
            'Texnik muammolarni hal qilishga qanday yondashasiz?',
            'Jamoada hamkorlik va muloqotni qanday tashkil qilasiz?',
            'Qisqa muddatli deadlines bilan ishlash tajribangiz haqida gapirib bering.',
        ];
        while (count($base) < $count) {
            $base[] = 'Kasbiy maqsadlaringiz va shu rolga motivatsiyangiz qanday?';
        }
        return array_slice($base, 0, $count);
    }
}
