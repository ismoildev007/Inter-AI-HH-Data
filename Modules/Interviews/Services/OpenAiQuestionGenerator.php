<?php

namespace Modules\Interviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAiQuestionGenerator implements AiQuestionGeneratorInterface
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;
    protected int $retries;

    public function __construct()
    {
        $this->apiKey = (string) (config('services.openai.key') ?? env('OPENAI_API_KEY'));
        $this->model = (string) config('interviews.ai.model', 'gpt-4.1-nano');
        $this->timeout = (int) config('interviews.ai.timeout', 20);
        $this->retries = (int) config('interviews.ai.retries', 2);
    }

    public function generate(string $title, ?string $company, ?string $description, ?string $language, int $count = 20): array
    {
        // Default to English if language is not specified or set to 'auto'
        $language = $language && $language !== 'auto' ? $language : 'en';
        $count = max(1, min(50, $count));

        $prompt = $this->buildPrompt($title, $company, $description, $language, $count);

        $attempts = 0;
        $lastException = null;
        do {
            try {
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeout)
                    ->acceptJson()
                    ->asJson()
                    ->post('https://api.openai.com/v1/responses', [
                        'model' => $this->model,
                        'input' => $prompt,
                        'max_output_tokens' => 800,
                    ]);

                if ($response->failed()) {
                    throw new \RuntimeException('OpenAI error: ' . $response->body());
                }

                $text = (string) data_get($response->json(), 'output_text', '');
                if (!$text) {
                    // Fallback for alternative response structure
                    $text = (string) data_get($response->json(), 'choices.0.message.content', '');
                }

                $questions = $this->extractQuestions($text, $count);
                if (count($questions) >= min(5, $count)) {
                    return array_slice($questions, 0, $count);
                }

                // If parsing failed, try a light retry
                throw new \RuntimeException('Not enough questions parsed');
            } catch (\Throwable $e) {
                $lastException = $e;
                usleep(200_000 * ($attempts + 1));
            }
        } while (++$attempts <= $this->retries);

        // On failure, return a minimal safe fallback
        if ($lastException) {
            // Optionally log here, but service should be side-effect free
        }
        return $this->fallback($title, $company, $language, $count);
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
