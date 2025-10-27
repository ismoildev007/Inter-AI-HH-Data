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
        // $prompt = <<<PROMPT
        //     You are an expert HR assistant AI.
        //     Analyze the following resume text and return a structured JSON object with the following fields only:

        //     - "skills": A list of the candidate's hard and soft skills (only relevant skills, no duplicates).
        //     - "strengths": 3–5 short bullet points describing the candidate's main strengths.
        //     - "weaknesses": 2–4 short bullet points describing areas that might need improvement.
        //     - "keywords": A list of important keywords or technologies mentioned in the resume (useful for search/matching).
        //     - "language": Detect the main language of the resume text (e.g., "en", "ru", "uz").
        //     - "title": From the resume, identify up to three (maximum 3) of the most specific and relevant professional titles that accurately represent the candidate’s main expertise and experience.
        //         Rules:
        //         • Each title must be specific and, if applicable, include both the main role and its associated technology or framework
        //           (e.g., "PHP Backend Developer", "React Frontend Developer", "Java Spring Developer", "Python Fullstack Developer").
        //         • If a title refers to Backend, Frontend, or Fullstack development, it **must include at least one programming language or framework**
        //           (e.g., PHP, Java, .NET, React, Vue, Node.js, etc.).
        //         • Titles such as **"Backend Developer"**, **"Frontend Developer"**, or **"Fullstack Developer"** alone are **strictly forbidden** —
        //           they cannot appear by themselves **nor as secondary or repeated titles after others.**
        //         • Do NOT output any title that ends with or contains only the words “Backend Developer”, “Frontend Developer”, or “Fullstack Developer”
        //           without an attached technology name.
        //         • Include other relevant non-programming roles (e.g., "Project Manager", "Marketing Specialist", "UI/UX Designer") if they clearly apply.
        //         • Do not include parentheses, notes, or explanations — only plain text titles separated by commas.
        //         • Prioritize titles that reflect the most emphasized or most recent experience.
        //         • Return up to three concise and distinct titles, separated by commas.
        //         • Additionally, after generating each title, append exactly three relevant skills, separated by commas:
        //             - For technical/developer roles: use only programming languages or frameworks (e.g., "PHP", "React", "Laravel", "Node.js", "Java", "Python", "Marketing", "HR", ...).
        //             - For non-technical roles: use general professional skills (e.g., "PR", "Recruitment", "Management", "Analytics", "Leadership", "Communication", ...).
        //             - Do not output database, infrastructure, or process terms (e.g., "MySQL", "CI/CD", "API") as these skills in this section.
        //           Example:
        //             - "PHP Backend Developer, PHP, Laravel, Node.js"
        //             - "Marketing Specialist, PR, Recruitment, Management"
        //     - "cover_letter": Write a short, professional cover letter (5–7 sentences) focusing on three key areas that best suit the candidate you listed above. Be polite, confident, concise, and literate.
        //       Always include the candidate's real name at the end, in a new paragraph, with the caption "Sincerely" and their name. The letter must be in Russian.
        //     Return only valid JSON. Do not include explanations outside the JSON.

        //     Resume text:

        //     " . ($resume->parsed_text ?? $resume->description) . "

        //     PROMPT;

        $prompt = <<<PROMPT
        You are an expert HR assistant AI.
        Analyze the following resume text and return a structured JSON object with the following fields only:

        - "skills": A list of the candidate's hard and soft skills (only relevant skills, no duplicates).
        - "strengths": 3–5 short bullet points describing the candidate's main strengths.
        - "weaknesses": 2–4 short bullet points describing areas that might need improvement.
        - "keywords": A list of important keywords or technologies mentioned in the resume (useful for search/matching).
        - "language": Detect the main language of the resume text (e.g., "en", "ru", "uz").
        - "title": From the resume, identify up to three (maximum 3) of the most specific and relevant professional titles that accurately represent the candidate’s main expertise and experience.

            Additionally, for each selected title, append up to two key skills that are most directly relevant and representative of that specific title (e.g., "PHP Backend Developer – PHP, Laravel").

            ---
            **Rules for generating "title":**

            • Each title must be highly specific and reflect both the role and its key technology, framework, or domain focus.
              - Examples: "PHP Backend Developer", "C# .NET Developer", "React Frontend Developer", "Java Spring Developer", "Digital Marketing Specialist", "HR Manager", "Accountant", "Sales Manager".

            • Each title must include one or two key skills from the resume that are directly relevant to that role and logically consistent.
              - Example:
                - "PHP Backend Developer – PHP, Laravel"
                - "React Frontend Developer – React, TypeScript"
                - "HR Manager – Talent Acquisition, Payroll"

            • When selecting the 2 skills:
              - Choose skills that strengthen or specify the title (e.g., framework, tool, or domain expertise).
              - Skills must come from the resume, not invented.
              - Do not include generic, cross-domain, or irrelevant skills such as:
                - Generic tech stack elements: "MySQL", "Git", "CI/CD", "REST API", "OOP"
                - Generic soft skills: "Communication", "Teamwork", "Leadership"
              - Only include skills that define or specialize the role, for example:
                - Developers → frameworks, languages, or tools unique to that stack (Laravel, React, Node.js, Django, Flutter, etc.)
                - Marketing → domain or platform (SEO, Google Ads, Content Strategy)
                - HR → HR-specific skills (Recruitment, Employee Relations, Compensation)

            • Strictly forbid vague or generic titles such as:
              "Developer", "Software Engineer", "Programmer", "IT Specialist", "Consultant", etc.,
              unless they include a clear technology or domain (e.g., "Python Developer", "C# Software Engineer").

            • Include non-technical roles when relevant (e.g., "HR Manager", "Project Manager", "Marketing Specialist").

            • Prioritize titles and their matching skills that reflect the most recent or most emphasized experience.

            • Return up to three concise and distinct titles, each on a new line.

            **Output format must look exactly like this:**

            For IT roles:
            PHP Backend Developer – PHP, Laravel
            C# .NET Developer – C#, .NET Core
            React Frontend Developer – React, TypeScript

            For non-IT roles:
            HR Manager – Recruitment, Payroll
            Marketing Specialist – SEO, Google Ads
            Sales Manager – B2B Sales, CRM

        - "cover_letter": Write a short, professional cover letter (5–7 sentences) focusing on three key areas that best suit the candidate you listed above. Be polite, confident, concise, and literate.
          Always include the candidate's real name at the end, in a new paragraph, with the caption "Sincerely" and their name. The letter must be in Russian.
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
