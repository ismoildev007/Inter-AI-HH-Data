<?php

namespace Modules\Resumes\Services;

use App\Models\Resume;
use App\Models\ResumeAnalyze;
use App\Models\UserPreference;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordIO;
use Modules\Resumes\Interfaces\ResumeInterface;
use Whoops\Run;

class ResumeService
{
    protected ResumeInterface $repo;

    public function __construct(ResumeInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Store a new resume and trigger analysis.
     */
    public function create(array $data): Resume
    {
        if (isset($data['file'])) {
            $path = $data['file']->store('resumes', 'spaces');
            $data['file_path'] = $path;
            $data['file_mime'] = $data['file']->getMimeType();
            $data['file_size'] = $data['file']->getSize();

            $originalExt = strtolower(pathinfo($data['file']->getClientOriginalName(), PATHINFO_EXTENSION));
            $tempPath = tempnam(sys_get_temp_dir(), 'resume_') . '.' . $originalExt;

            file_put_contents($tempPath, Storage::disk('spaces')->get($path));

            $data['parsed_text'] = $this->parseFile($tempPath);
            unlink($tempPath);
        }


        $resume = $this->repo->store($data);

        $this->analyze($resume);

        return $resume;
    }

    /**
     * Update an existing resume and re-run analysis.
     */
    public function update(Resume $resume, array $data): Resume
    {
        if (isset($data['file'])) {
            $path = $data['file']->store('resumes', 'spaces');
            $data['file_path'] = $path;
            $data['file_mime'] = $data['file']->getMimeType();
            $data['file_size'] = $data['file']->getSize();
            $data['parsed_text'] = $this->parseFile($data['file']->getPathname());
        }

        $resume = $this->repo->update($resume, $data);

        $this->analyze($resume);

        return $resume;
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
                Always include the candidate’s real name at the end (no placeholders like [Your Name]) it should be in russian language.
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
            Log::info("Invalid GPT response: " . $content);
            throw new \RuntimeException("Invalid GPT response: " . $content);
        }


        $resume->update(
            ['title' => $analysis['title'] ?? null]
        );
        $resumeAnalyze = ResumeAnalyze::updateOrCreate(
            ['resume_id' => $resume->id],
            [
                'skills'     => $analysis['skills'] ?? null,
                'strengths'  => $analysis['strengths'] ?? null,
                'weaknesses' => $analysis['weaknesses'] ?? null,
                'keywords'   => $analysis['keywords'] ?? null,
                'language'   => $analysis['language'] ?? 'en',
            ]
        );

        Log::info('Resume analyzed', ['resume_id' => $resume->id, 'analysis_id' => $resumeAnalyze->id, 'data' => $analysis]);

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
                    Log::info("Parsing PDF file: " . $path);
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($path);
                    return trim($pdf->getText());

                case 'docx':
                case 'doc':
                    Log::info("Converting DOCX/DOC file using unoconv: " . $path);

                    $tmpTxt = tempnam(sys_get_temp_dir(), 'resume_') . '.txt';
                    $loProfile = '/var/www/.config/libreoffice';

                    // ✅ Correct command: no --listener, just convert
                    $cmd = sprintf(
                        'HOME=/var/www unoconv -f txt -o %s -env:UserInstallation=file://%s %s 2>&1',
                        escapeshellarg($tmpTxt),
                        escapeshellarg($loProfile),
                        escapeshellarg($path)
                    );

                    exec($cmd, $output, $code);

                    if ($code !== 0 || !file_exists($tmpTxt)) {
                        Log::error("unoconv failed [code=$code]: " . implode("\n", $output));
                        throw new \RuntimeException("unoconv failed with code $code");
                    }

                    $text = file_get_contents($tmpTxt);
                    @unlink($tmpTxt);

                    Log::info("Parsed text length: " . strlen($text));
                    return trim($text);

                case 'txt':
                    return file_get_contents($path);

                default:
                    Log::warning("Unsupported resume format: " . $ext);
                    return null;
            }
        } catch (\Throwable $e) {
            Log::error("Resume parsing failed: " . $e->getMessage());
            return null;
        }
    }


    public function setPrimary(Resume $resume): Resume
    {
        // Reset all other resumes for this user
        Resume::where('user_id', $resume->user_id)
            ->where('id', '!=', $resume->id)
            ->update(['is_primary' => false]);

        $resume->update(['is_primary' => true]);

        return $resume;
    }
}
