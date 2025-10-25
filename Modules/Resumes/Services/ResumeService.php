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
            - "title": Identify up to **three (maximum 3)** of the most specific and relevant professional titles that accurately represent the candidate’s main expertise and experience.

            ### Title generation rules (strictly follow):

            1. 🎯 The goal is to create clean, non-repetitive, and specific professional titles that represent the candidate’s true roles and technologies.

            2. 🧩 If the resume contains technologies or frameworks (e.g., PHP, Laravel, Vue.js, React, Node.js, Django, .NET, Flutter, Spring Boot, Angular):
               - Always include **one key technology** directly next to the role (e.g., “Laravel Backend Developer”, “React Frontend Developer”).
               - Never output “Backend Developer”, “Frontend Developer”, or “Fullstack Developer” alone.
               - Examples:
                 ✅ “Fullstack Laravel Developer, PHP, Laravel, Vue.js”
                 ✅ “Backend Node.js Developer, Node.js, Express, MongoDB”
                 ✅ “Frontend React Developer, React, TypeScript”
                 ❌ “Backend Developer”, “Fullstack Developer”, “Frontend Developer”

            3. ⚙️ If the resume mentions multiple related areas (Fullstack, Backend, Frontend), choose **only the most comprehensive one**:
               - Prefer “Fullstack ... Developer” if both Backend and Frontend skills are present.
               - Do NOT list “Fullstack” together with “Backend” or “Frontend” again.

            4. 🚫 Avoid repetition completely:
               - Do not repeat the same technologies (e.g., PHP, Laravel) across multiple titles.
               - Do not repeat the same role concepts (e.g., “Backend Developer” and “Backend PHP Developer” are redundant — keep only the more specific one).
               - Do not repeat words such as “Marketing”, “PR”, or “Manager” more than once if they already appear in a previous title.

            5. 💼 For non-technical roles (managers, marketers, analysts):
               - Keep them as a single, focused domain title.
               - Add 2–3 unique focus areas or tools (avoid duplication).
               - Examples:
                 ✅ “Project Manager, Agile, Jira”
                 ✅ “Digital Marketing Specialist, SEO, Google Ads”
                 ✅ “Marketing Analyst, Market Research, Strategy”
                 ❌ “Project Manager, Marketing, PR” + “Marketing Director, Marketing, PR”

            6. 📚 Each title should be **unique, 5–7 words long**, and formatted clearly.
               - If multiple titles exist, separate them with semicolons (;) — not commas.
               - Inside each title, technologies or tools can be separated by commas.

            7. 🧠 Always prioritize:
               - The most recent and relevant experience;
               - The most specific and professional title;
               - No duplicates, no overlapping meanings.

            ---

            🔹 Correct examples:

            Input:
            “PHP, Laravel, Vue.js, MySQL, REST API”
            Output:
            ✅ “Fullstack Laravel Developer, PHP, Laravel, Vue.js”

            Input:
            “Python, Django, FastAPI, PostgreSQL, Docker”
            Output:
            ✅ “Backend Django Developer, Python, Django, FastAPI”

            Input:
            “Marketing, SEO, Google Ads, PR, Analytics”
            Output:
            ✅ “Digital Marketing Specialist, SEO, Google Ads, Analytics”

            Input:
            “Fullstack, React, Node.js, TypeScript”
            Output:
            ✅ “Fullstack React Developer, React, Node.js, TypeScript”

            ---

            - "cover_letter": Write a short, professional cover letter (5–7 sentences) focusing on three key strengths that best suit the candidate above.
              Be polite, confident, concise, and literate.
              Always include the candidate's real name at the end, in a new paragraph, with the caption "Sincerely" and their name.
              The letter must be written in Russian.

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


        $normalizedTitle = $this->extractTitle($analysis['title'] ?? null);

        $resumeAnalyze = ResumeAnalyze::updateOrCreate(
            ['resume_id' => $resume->id],
            [
                'skills'     => $analysis['skills'] ?? null,
                'strengths'  => $analysis['strengths'] ?? null,
                'weaknesses' => $analysis['weaknesses'] ?? null,
                'keywords'   => $analysis['keywords'] ?? null,
                'language'   => $analysis['language'] ?? 'en',
                'title'      => $normalizedTitle,
            ]
        );

        if ($normalizedTitle !== null) {
            $resume->update(['title' => $normalizedTitle]);
        }

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
    protected function extractTitle(null|string|array $rawTitle): ?string
    {
        if (empty($rawTitle)) {
            return null;
        }

        if (is_array($rawTitle)) {
            $parts = array_filter(array_map('trim', $rawTitle));
            if (empty($parts)) {
                return null;
            }

            return implode(', ', $parts);
        }

        $title = trim($rawTitle);

        return $title === '' ? null : $title;
    }

    protected function parseFile(string $path): ?string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        try {
            switch ($ext) {
                case 'pdf':
                    Log::info("Parsing PDF file: " . $path);

                    $parser = new \Smalot\PdfParser\Parser();

                    try {
                        $pdf = $parser->parseFile($path);
                        $text = trim($pdf->getText());

                        // Agar Smalot bo‘sh qaytarsa, pdftotext fallback ishlatamiz
                        if (!$text || strlen($text) < 50) {
                            Log::warning("Smalot returned empty text, switching to pdftotext fallback...");

                            $tmpTxt = tempnam(sys_get_temp_dir(), 'pdf_') . '.txt';
                            $cmd = sprintf(
                                'pdftotext -layout %s %s',
                                escapeshellarg($path),
                                escapeshellarg($tmpTxt)
                            );
                            exec($cmd, $output, $code);

                            if ($code === 0 && file_exists($tmpTxt)) {
                                $text = file_get_contents($tmpTxt);
                                @unlink($tmpTxt);
                            } else {
                                Log::error("pdftotext failed [code=$code]: " . implode("\n", $output));
                                return null;
                            }
                        }

                        // ✅ UTF-8 tozalash (ENG MUHIM QISM)
                        $text = $this->sanitizeText($text);

                        Log::info("Parsed text length: " . strlen($text));
                        return trim($text);
                    } catch (\Throwable $e) {
                        Log::error("PDF parse failed: " . $e->getMessage());
                        return null;
                    }


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

    protected function sanitizeText(?string $text): string
    {
        if (!$text) return '';

        // 1️⃣ Noto‘g‘ri baytlarni olib tashlash
        $text = iconv('UTF-8', 'UTF-8//IGNORE', $text);

        // 2️⃣ Boshqaruv belgilarini olib tashlash (\x00 - \x1F, \x7F)
        $text = preg_replace('/[^\P{C}\n]+/u', '', $text);

        // 3️⃣ Ba’zi PDF parserlar “hidden unicode” belgilari kiritadi
        $text = str_replace(["\xEF\xBB\xBF", "\u{FEFF}"], '', $text);

        // 4️⃣ Qayta UTF-8 normalizatsiya
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return trim($text);
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
