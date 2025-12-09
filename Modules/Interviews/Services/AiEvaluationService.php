<?php 

namespace Modules\Interviews\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiEvaluationService
{
    public function evaluate(string $question, string $userAnswer, string $lang): string
    {
        Log::info('AI Evaluation', ['question' => $question, 'answer' => $userAnswer]);

        $prompt = "
        You are an AI mock interview evaluator.

        Question:
        {$question}

        Candidate answer:
        {$userAnswer}

        Return ONLY JSON:

        {
          \"recommendation\": \"1–2 sentence professional feedback in {$lang}\"
        }
        ";

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        if (!$response->successful()) {
            Log::error('AI evaluation failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return "AI evaluation failed.";
        }

        $raw = trim($response->json('choices.0.message.content'));
        $clean = str_replace(['```json', '```'], '', $raw);
        $json = json_decode($clean, true);

        return $json['recommendation'] ?? "No recommendation.";
    }
}