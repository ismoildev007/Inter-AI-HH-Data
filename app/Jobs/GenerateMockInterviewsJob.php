<?php

namespace App\Jobs;

use App\Models\MockInterview;
use App\Models\MockInterviewQuestion;
use App\Models\User;
use App\Services\TextToSpeechService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMockInterviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public array $analysis;
    public string $parsedText;

    public function __construct(User $user, array $analysis, string $parsedText)
    {
        $this->user = $user;
        $this->analysis = $analysis;
        $this->parsedText = $parsedText;
    }

    public function handle()
    {
        try {
            $this->generateMockInterviews();
        } catch (\Throwable $e) {
            Log::error("Mock Interview Job Failed: " . $e->getMessage());
        }
    }

    private function generateMockInterviews()
    {
        $lang = $this->user->language ?? 'en';

        $prompt = $this->buildPrompt();

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post("https://api.openai.com/v1/chat/completions", [
                "model" => "gpt-4o-mini",
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ]
            ]);

        if (!$response->successful()) {
            Log::error("AI generation failed: " . $response->body());
            return;
        }

        $content = $response->json("choices.0.message.content");

        $data = $this->cleanJson($content);

        if (!$data) {
            Log::error("Invalid JSON from AI");
            return;
        }

        foreach (['general', 'technical'] as $type) {
            if (isset($data[$type])) {
                $this->saveCategory($type, $data[$type]);
            }
        }
    }

    private function buildPrompt(): string
    {
        $a = $this->analysis;
        $resumeText = $this->parsedText;

        $position = $a['position'] ?? 'Developer';
        $level = $a['level'] ?? 'Junior';
        $strengths = implode(', ', $a['strengths'] ?? []);
        $weaknesses = implode(', ', $a['weaknesses'] ?? []);
        $growth = implode(', ', $a['growth_areas'] ?? []);
        $language = $this->user->language ?? 'en';

        return <<<PROMPT
    You are an advanced AI system specialized in generating high-quality mock interview questions for software engineers.
    
    Your task is to create two interview sets:
    
    1. General Interview (10 questions)
    2. Technical Interview (5 questions)
    
    Questions must be **personalized** based on:
    - The user's position: {$position}
    - Experience level: {$level}
    - Strengths: {$strengths}
    - Weaknesses: {$weaknesses}
    - Growth areas: {$growth}
    - Language for the questions: {$language}
    
    Resume details (experience, skills, education, achievements, professional summary, etc.):
    --------------------
    {$resumeText}
    --------------------
    
    STRICT OUTPUT RULES
    
    1. Output ONLY valid JSON.  
    2. Use double quotes everywhere.  
    3. Do NOT include explanations, markdown, comments, or text outside the JSON.  
    4. Do NOT use placeholders like "q1", "example question", "…", or incomplete sentences.  
    5. No trailing commas.
    
    JSON FORMAT (must match exactly)
    
    {
      "general": {
        "title": "General Interview",
        "questions": ["...", "...", "... (10 total)"]
      },
      "technical": {
        "title": "Technical Interview",
        "questions": ["...", "...", "... (5 total)"]
      }
    }
    
    GENERAL INTERVIEW – Requirements
    - Write 10 personalized questions that evaluate:
      - communication
      - behavioral traits
      - leadership
      - decision-making
      - teamwork & collaboration
      - conflict resolution
      - ownership & responsibility
      - problem-solving approach
    - Use the user’s strengths to ask deeper questions.
    - Use the weaknesses & growth areas to construct challenging but fair questions.
    - Avoid generic clichés like “Tell me about yourself” unless fully customized to the resume.
    - Refer to experience and projects from the resume, but do not copy text—paraphrase naturally.
    
    TECHNICAL INTERVIEW – Requirements
    - Write 5 tailored technical questions suitable for a {$level} {$position}.
    - Include a mix of:
      - debugging or troubleshooting scenarios  
      - small-scale system / architecture reasoning  
      - performance or scalability considerations  
      - real-world practical problems the candidate may face  
      - questions that target weaknesses or growth areas
    - Do not ask trivial textbook questions.
    - Make each question clear, specific, and meaningful.
    
    LANGUAGE REQUIREMENT
    - All questions must be written in {$language}.
    - No mixing of languages.
    
    Return ONLY the JSON object.  
    PROMPT;
    }


    private function cleanJson(string $text): ?array
    {
        $text = trim(preg_replace('/```(json)?|```/i', '', $text));
        $decoded = json_decode($text, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function saveCategory(string $type, array $block)
    {
        $interview = MockInterview::create([
            "user_id" => $this->user->id,
            "interview_type" => $type,
            "title" => $block["title"],
            "position" => $this->analysis['position'],
            "language" => $this->user->language,
        ]);

        $tts = new TextToSpeechService();
        $lang = $this->user->language;

        foreach ($block["questions"] as $index => $q) {

            $filename = "mock_interviews/" . uniqid() . ".mp3";
            $audioUrl = $tts->generate($q, $lang, $filename);

            MockInterviewQuestion::create([
                "mock_interview_id" => $interview->id,
                "order" => $index + 1,
                "difficulty" => 1,
                "question_text" => $q,
                "question_audio_url" => $audioUrl,
                "meta" => null,
            ]);
        }
    }
}
