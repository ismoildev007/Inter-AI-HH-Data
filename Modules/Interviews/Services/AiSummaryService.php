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

        foreach ($interview->interviewQuestions as $q) {
            $answer = $q->interviewAnswers->first();

            $qaPairs[] = [
                "question" => $q->question,
                "answer"   => $answer->answer_text ?? "No answer provided"
            ];
        }

        $prompt = "
        You are an AI mock interview evaluator.

        Analyze all Q&A and return ONLY this JSON:

        {
            \"overall_percentage\": number (0-100),
            \"strengths\": [\"bullet\", \"bullet\"],
            \"weaknesses\": [\"bullet\", \"bullet\"],
            \"work_on\": [\"bullet\", \"bullet\"]
        }

        Write analysis in {$lang}.

        Dataset:
        " . json_encode($qaPairs, JSON_PRETTY_PRINT);

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
        $clean = str_replace(['```json', '```'], '', $raw);
        $summary = json_decode($clean, true) ?: [];

        return [
            "overall_percentage" => $summary['overall_percentage'] ?? 0,
            "strengths"          => $summary['strengths'] ?? [],
            "weaknesses"         => $summary['weaknesses'] ?? [],
            "work_on"            => $summary['work_on'] ?? [],
        ];
    }
}