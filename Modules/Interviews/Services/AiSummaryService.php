<?php 

namespace Modules\Interviews\Services;

use App\Models\MockInterview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiSummaryService
{
    public function generate(MockInterview $interview, string $lang): array
    {
        $qaPairs = [];

        foreach ($interview->questions as $q) {
            $answer = $q->answers->first();

            $qaPairs[] = [
                "question" => $q->question_text,
                "answer"   => $answer->answer_text ?? "No answer provided"
            ];
        }

        $prompt = "
        You are an AI mock interview evaluator.

        Analyze the ENTIRE interview (all questions and all answers).

        Return ONLY this JSON (valid, no comments):

        {
            \"overall_percentage\": number (0-100),
            \"strengths\": [\"bullet\", \"bullet\"],
            \"weaknesses\": [\"bullet\", \"bullet\"],
            \"work_on\": [\"bullet\", \"bullet\"]
        }

        Write summary in {$lang}.

        Dataset:
        " . json_encode($qaPairs, JSON_PRETTY_PRINT);

        Log::info('AI SUMMARY PROMPT', ['prompt' => $prompt]);

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        if (!$response->successful()) {
            Log::error('AI summary failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                "overall_percentage" => 0,
                "strengths" => [],
                "weaknesses" => [],
                "work_on" => [],
            ];
        }

        $raw = trim($response->json('choices.0.message.content'));
        $clean = str_replace(['```json','```'], '', $raw);

        $json = json_decode($clean, true);

        return [
            'overall_percentage' => $json['overall_percentage'] ?? 0,
            'strengths'          => $json['strengths'] ?? [],
            'weaknesses'         => $json['weaknesses'] ?? [],
            'work_on'            => $json['work_on'] ?? [],
        ];
    }
}
