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
use Modules\TelegramChannel\Services\VacancyCategoryService;

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
        // Build allowed categories list (labels only, excluding "Other") for classification
        $categoryService = app(VacancyCategoryService::class);
        $allowedCategoryLabels = $categoryService->getLabelsExceptOther();
        $allowedCategoriesJson = json_encode($allowedCategoryLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $resumeText = (string) ($resume->parsed_text ?? $resume->description);

        $prompt = <<<PROMPT
            You are an expert HR assistant AI.
            Analyze the following resume text and return a structured JSON object with the following fields only:

            - "skills": A list of the candidate's hard and soft skills (only relevant skills, no duplicates).
            - "strengths": 3–5 short bullet points describing the candidate's main strengths.
            - "weaknesses": 2–4 short bullet points describing areas that might need improvement.
            - "keywords": A list of important keywords or technologies mentioned in the resume (useful for search/matching).
            - "language": Detect the main language of the resume text (e.g., "en", "ru", "uz").
            - "title": From the resume, identify up to three (maximum 3) of the most specific and relevant professional titles that accurately represent the candidate’s main expertise and experience.
                Rules for generating "title":
                • Each title must be highly specific and reflect both the role and its key technology, framework, or domain focus.
                    - Examples: "PHP Backend Developer", "C# .NET Developer", "ASP.NET Developer", "Java Spring Developer", "Python Fullstack Developer", "React Frontend Developer", "Digital Marketing Specialist", "HR Manager", "Accountant", "Nurse", "Sales Manager", "Teacher".
                • Titles referring to Backend, Frontend, or Fullstack development must include at least one programming language or framework (e.g., PHP, Java, .NET, React, Vue, Node.js, etc.).
                • Strictly forbid vague or generic titles such as: "Backend Developer","Developer", "Software Engineer", "Software Developer", "Engineer", "Frontend Developer", "Programmer", "IT Specialist", "Consultant" — unless they include a clear technology or domain (e.g., "C# Software Engineer", "Python Developer").
                • Include non-technical roles (e.g., "HR Manager", "Recruitment Specialist", "Project Manager", "Accountant", "Marketing Specialist", "Banking Operations Manager") when relevant.
                • Do not include parentheses, notes, or explanations — only plain text titles separated by commas.
                • Prioritize titles that reflect the most recent or most emphasized experience.
                • Return up to three concise and distinct titles, separated by commas.
                • Each title should include one main defining technology or domain — avoid duplication or overlapping meanings.
                • The output format must look exactly like this:
                    - Example for IT:
                    PHP Backend Developer,
                    C# .NET Developer,
                    React Frontend Developer
                    - Example for non-IT:
                    HR Manager,
                    Recruitment Specialist,
                    Banking Operations Manager
                • After the titles, also append up to two meaningful core skills that logically match the main title(s) directly in the same "title" field.
                    - For example: (PHP Full-Stack Developer, Laravel Developer, Vue.js Developer, PHP, Laravel)
                    - For technical roles: use main languages/frameworks (e.g., PHP, Python, JavaScript, Java, C#, React, Node.js, etc.).
                    - For non-technical roles: use domain skills (e.g., Marketing, SMM, HR, Accounting, Graphic Design, Financial Analysis, Teaching, Project Management, etc.).
                    - Do NOT include infrastructure/tools such as SQL, Git, CI/CD, Docker, etc.
                    - Ensure skills are appended in the same comma-separated list, no extra text or brackets.

            - "cover_letter": Write a short, professional cover letter (5–7 sentences) focusing on three key areas that best suit the candidate listed above. Be polite, confident, concise, and literate. Always include the candidate's real name at the end, in a new paragraph, with the caption "Sincerely" and their name. The letter must be in Russian.

            - "category": Choose exactly one category from the allowed list below that best matches this resume. Output the category label as an exact string match from the list. Do not invent new labels. Do not choose "Other".
              Allowed categories (labels): {$allowedCategoriesJson}

            Return only valid JSON. Do not include explanations outside the JSON.

            Resume text:
            {$resumeText}
            PROMPT;


        $response = Http::timeout(120)
            ->withToken(env('OPENAI_API_KEY'))
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

        if ($normalizedTitle !== null) {
            $resume->update(['title' => $normalizedTitle]);
        }

        $categoryFromAi = isset($analysis['category']) && is_string($analysis['category'])
            ? trim($analysis['category'])
            : null;

        $finalCategory = null;
        if ($categoryFromAi !== null && $categoryFromAi !== '') {
            foreach ($allowedCategoryLabels as $label) {
                if (mb_strtolower($label, 'UTF-8') === mb_strtolower($categoryFromAi, 'UTF-8')) {
                    $finalCategory = $label;
                    break;
                }
            }

            if ($finalCategory === null) {
                $maybeLabel = $categoryService->fromSlug($categoryFromAi);
                if ($maybeLabel && in_array($maybeLabel, $allowedCategoryLabels, true)) {
                    $finalCategory = $maybeLabel;
                }
            }
        }

        if ($finalCategory === null) {
            $inferred = $categoryService->categorize(null, $normalizedTitle, $resumeText);
            if ($inferred && in_array($inferred, $allowedCategoryLabels, true)) {
                $finalCategory = $inferred;
            }
        }

        if ($finalCategory !== null) {
            $resume->update(['category' => $finalCategory]);
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
