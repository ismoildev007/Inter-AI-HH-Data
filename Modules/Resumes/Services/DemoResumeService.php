<?php

namespace Modules\Resumes\Services;

use App\Models\Resume;
use App\Models\ResumeAnalyze;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIO;
use Modules\Resumes\Interfaces\ResumeInterface;

class DemoResumeService
{
    /**
     * Store a new resume and trigger analysis.
     */
    public function create(array $data): Resume
    {
        $user = User::updateOrCreate(
            ['chat_id' => $data['chat_id']],
            [
                'password' => $data['chat_id'],
                'is_primary' => true
            ]
        );
        if (isset($data['file'])) {
            $path = $data['file']->store('resumes', 'spaces');
            $data['file_path'] = $path;
            $data['file_mime'] = $data['file']->getMimeType();
            $data['file_size'] = $data['file']->getSize();
        
            // ✅ Download temporarily for parsing
            $tempPath = tempnam(sys_get_temp_dir(), 'resume_');
            file_put_contents($tempPath, Storage::disk('spaces')->get($path));
        
            $data['parsed_text'] = $this->parseFile($tempPath);
            unlink($tempPath);
        }
        

        $demoResume = Resume::create($data);

        $this->analyze($demoResume);

        return $demoResume;
    }

    /**
     * Call GPT API to analyze resume and store results.
     */
    public function analyze(Resume $resume): void
    {
        $prompt = <<<PROMPT
            You are an expert HR assistant AI.
            Analyze the following resume text and return a structured JSON object with the following fields only:

            - "skills": A list of the candidate's hard and soft skills (only relevant skills, no duplicates).
            - "strengths": 3–5 short bullet points describing the candidate's main strengths.
            - "weaknesses": 2–4 short bullet points describing areas that might need improvement.
            - "keywords": A list of important keywords or technologies mentioned in the resume (useful for search/matching).
            - "language": Detect the main language of the resume text (e.g., "en", "ru", "uz").
            - "cover_letter": Write a short professional cover letter (5–7 sentences) introducing the candidate,
                          tailored for general job applications. Keep it polite, confident, and concise.
            - "title": Identify the fields and professions that apply to this resume and write them down using commas in sequence.

            Return only valid JSON. Do not include explanations outside the JSON.

            Resume text:

            " . ($resume->parsed_text ?? $resume->description) . "

            PROMPT;

        $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful AI for analyzing resumes.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.2,
            ]);

        if (! $response->successful()) {
            Log::error("GPT API failed: " . $response->body());
            return;
        }

        $content = $response->json('choices.0.message.content');

        $content = trim($content);
        $content = preg_replace('/^```(json)?/i', '', $content);
        $content = preg_replace('/```$/', '', $content);
        $content = trim($content);

        $analysis = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($analysis)) {
            throw new \RuntimeException("Invalid GPT response: " . $content);
        }


        ResumeAnalyze::updateOrCreate(
            ['resume_id' => $resume->id],
            [
                'skills'     => $analysis['skills'] ?? null,
                'strengths'  => $analysis['strengths'] ?? null,
                'weaknesses' => $analysis['weaknesses'] ?? null,
                'keywords'   => $analysis['keywords'] ?? null,
                'language'   => $analysis['language'] ?? 'en',
                'title'      => $analysis['title'] ?? null,
            ]
        );

        if (!empty($analysis['title'])) {
            $resume->update(['title' => $analysis['title']]);
        }

        if (!empty($analysis['cover_letter'])) {
            UserPreference::updateOrCreate(
                ['user_id' => $resume->user_id],
                ['cover_letter' => $analysis['cover_letter']]
            );
        }
    }

    /**
     * Example: Parse PDF/Docx file into plain text.
     */
    protected function parseFile(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        try {
            switch ($ext) {
                case 'pdf':
                    $parser = new PdfParser();
                    $pdf = $parser->parseFile($path);
                    return trim($pdf->getText());

                case 'docx':
                case 'doc':
                    $phpWord = WordIO::load($path);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        $elements = $section->getElements();
                        foreach ($elements as $element) {
                            if (method_exists($element, 'getText')) {
                                $text .= $element->getText() . " ";
                            }
                        }
                    }
                    return trim($text);

                case 'txt':
                    return file_get_contents($path);

                default:
                    return null;
            }
        } catch (\Throwable $e) {
            Log::error("Resume parsing failed: " . $e->getMessage());
            return null;
        }
    }
}
