<?php

namespace Modules\ResumeCreate\Services;

use App\Models\Resume;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;

class ResumeDocxBuilder
{
    public function download(Resume $resume, string $lang = 'ru')
    {
        $lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';

        $viewData = $this->buildViewData($resume, $lang);
        $phpWord = $this->buildDocument($viewData);

        $tmpPath = tempnam(sys_get_temp_dir(), 'resume-docx-');
        $fileName = $this->buildDisplayFileName($resume, $lang, 'docx');

        $phpWord->save($tmpPath, 'Word2007', true);

        return response()->download($tmpPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Render DOCX to a file on disk and return absolute path.
     */
    public function store(Resume $resume, string $lang = 'ru'): string
    {
        $lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';

        $viewData = $this->buildViewData($resume, $lang);
        $phpWord = $this->buildDocument($viewData);

        $dir = storage_path('app/resumes');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $fileName = 'resume-'.$resume->id.'-'.$lang.'.docx';
        $path = $dir.DIRECTORY_SEPARATOR.$fileName;

        $phpWord->save($path, 'Word2007', true);

        return $path;
    }

    /**
     * Build a simple DOCX document using PhpWord API (no HTML parser).
     */
    protected function buildDocument(array $viewData): PhpWord
    {
        /** @var Resume $resume */
        $resume = $viewData['resume'];
        $lang = $viewData['lang'];
        $t = $viewData['t'] ?? [];
        $labels = $viewData['labels'] ?? [];

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $h1 = ['bold' => true, 'size' => 20];
        $h2 = ['bold' => true, 'size' => 14];
        $bold = ['bold' => true];
        $italic = ['italic' => true];

        // Header: photo + main info (approximate to PDF layout)
        $table = $section->addTable();
        $table->addRow();

        // Left cell: photo
        $photoCell = $table->addCell(2000);
        $photoPath = $this->photoPathForDocx($resume);
        if ($photoPath) {
            // 96x96 px approx (72 DPI → ~1.3 inch)
            $photoCell->addImage($photoPath, [
                'width' => 96,
                'height' => 96,
            ]);
        }

        // Right cell: name, position, contacts
        $infoCell = $table->addCell(8000);
        $infoCell->addText(trim($resume->first_name.' '.$resume->last_name), $h1);

        if ($resume->desired_position) {
            $infoCell->addText($resume->desired_position, $italic);
        }

        $contactParts = [];
        if ($resume->phone) {
            $contactParts[] = $resume->phone;
        }
        if ($resume->contact_email) {
            $contactParts[] = $resume->contact_email;
        }
        if ($contactParts) {
            $infoCell->addText(implode(' | ', $contactParts));
        }

        if ($resume->city || $resume->country) {
            $location = trim($resume->city.($resume->city && $resume->country ? ', ' : '').$resume->country);
            if ($location !== '') {
                $infoCell->addText($location);
            }
        }

        if ($resume->linkedin_url) {
            $infoCell->addText('LinkedIn: '.$resume->linkedin_url);
        }
        if ($resume->github_url) {
            $infoCell->addText('GitHub: '.$resume->github_url);
        }
        if ($resume->portfolio_url) {
            $infoCell->addText('Portfolio: '.$resume->portfolio_url);
        }

        $section->addTextBreak(1);

        // Professional summary
        $section->addText($labels['section_professional_summary'] ?? 'PROFESSIONAL SUMMARY', $h2);
        $summaryText = $t['professional_summary'] ?? $resume->professional_summary;
        if ($summaryText) {
            $section->addText($summaryText);
        }

        // Experiences
        if ($resume->experiences->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText($labels['section_work_experience'] ?? 'WORK EXPERIENCE', $h2);

            $txExpList = $t['experiences'] ?? [];

            foreach ($resume->experiences as $index => $exp) {
                $txExp = is_array($txExpList) && array_key_exists($index, $txExpList) ? $txExpList[$index] : null;

                $section->addText($exp->position ?? '', $bold);

                $dateLine = '';
                if ($exp->start_date) {
                    $dateLine .= $exp->start_date->format('M Y');
                }
                $dateLine .= ' - ';
                if ($exp->is_current) {
                    $dateLine .= $labels['present'] ?? 'Present';
                } elseif ($exp->end_date) {
                    $dateLine .= $exp->end_date->format('M Y');
                }
                if (trim($dateLine) !== '-') {
                    $section->addText($dateLine);
                }

                $company = $txExp['company'] ?? $exp->company;
                $loc = $txExp['location'] ?? $exp->location;
                $companyLine = trim($company.($company && $loc ? ', ' : '').$loc);
                if ($companyLine !== '') {
                    $section->addText($companyLine);
                }

                $desc = $txExp['description'] ?? $exp->description;
                if ($desc) {
                    $section->addText($desc);
                }

                $section->addTextBreak(1);
            }
        }

        // Education
        if ($resume->educations->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText($labels['section_education'] ?? 'EDUCATION', $h2);

            $txEduList = $t['educations'] ?? [];

            foreach ($resume->educations as $index => $edu) {
                $txEdu = is_array($txEduList) && array_key_exists($index, $txEduList) ? $txEduList[$index] : null;

                $section->addText($edu->degree ?? '', $bold);

                if ($edu->start_date) {
                    $section->addText($edu->start_date->format('Y'));
                }

                $inst = $txEdu['institution'] ?? $edu->institution;
                $loc = $txEdu['location'] ?? $edu->location;
                $eduLine = trim($inst.($inst && $loc ? ', ' : '').$loc);
                if ($eduLine !== '') {
                    $section->addText($eduLine);
                }

                $extra = $txEdu['extra_info'] ?? $edu->extra_info;
                if ($extra) {
                    $section->addText($extra);
                }

                $section->addTextBreak(1);
            }
        }

        // Skills (grouped by level)
        if ($resume->skills->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText($labels['section_skills'] ?? 'SKILLS', $h2);

            $txSkills = $t['skills'] ?? [];
            $grouped = [];

            foreach ($resume->skills as $index => $skill) {
                $txSkill = is_array($txSkills) && array_key_exists($index, $txSkills) ? $txSkills[$index] : null;
                $level = $txSkill['level'] ?? $skill->level;
                $name = $skill->name;
                if (! $level || ! $name) {
                    continue;
                }
                $grouped[$level][] = $name;
            }

            foreach ($grouped as $level => $names) {
                $section->addText($level.': '.implode(', ', $names));
            }
        }

        // Job preferences
        $employmentItems = (array) ($resume->employment_types ?? []);
        $scheduleItems = (array) ($resume->work_schedules ?? []);

        $employmentMap = [
            'full_time' => ['ru' => 'Полная занятость', 'en' => 'Full time'],
            'part_time' => ['ru' => 'Частичная занятость', 'en' => 'Part time'],
            'project' => ['ru' => 'Проектная работа', 'en' => 'Project work'],
            'internship' => ['ru' => 'Стажировка', 'en' => 'Internship'],
            'volunteer' => ['ru' => 'Волонтёрство', 'en' => 'Volunteering'],
        ];

        $scheduleMap = [
            'full_day' => ['ru' => 'Полный день', 'en' => 'Full day'],
            'shift' => ['ru' => 'Сменный график', 'en' => 'Shift schedule'],
            'flex' => ['ru' => 'Гибкий график', 'en' => 'Flexible schedule'],
            'remote' => ['ru' => 'Удалённая работа', 'en' => 'Remote work'],
            'rotation' => ['ru' => 'Вахтовый метод', 'en' => 'Rotation'],
        ];

        $locale = $lang === 'ru' ? 'ru' : 'en';

        $mapLabel = function (string $value, array $map, string $locale): string {
            $key = strtolower($value);
            if (isset($map[$key][$locale])) {
                return $map[$key][$locale];
            }
            $value = str_replace('_', ' ', $value);
            return ucfirst($value);
        };

        $employmentText = implode(', ', array_map(
            fn($v) => $mapLabel((string) $v, $employmentMap, $locale),
            $employmentItems
        ));

        $scheduleText = implode(', ', array_map(
            fn($v) => $mapLabel((string) $v, $scheduleMap, $locale),
            $scheduleItems
        ));

        $prefLabels = $lang === 'ru'
            ? [
                'salary' => 'Желаемая зарплата',
                'citizenship' => 'Гражданство',
                'employment' => 'Занятость',
                'schedule' => 'График работы',
            ]
            : [
                'salary' => 'Desired salary',
                'citizenship' => 'Citizenship',
                'employment' => 'Employment',
                'schedule' => 'Work schedule',
            ];

        $readinessLabels = $lang === 'ru'
            ? ['relocate' => 'Готов к переезду', 'trips' => 'Готов к командировкам']
            : ['relocate' => 'Willing to relocate', 'trips' => 'Willing to travel'];

        if (
            $resume->desired_salary ||
            $resume->citizenship ||
            $employmentText ||
            $scheduleText ||
            $resume->ready_to_relocate ||
            $resume->ready_for_trips
        ) {
            $section->addTextBreak(1);
            $section->addText($labels['section_preferences'] ?? 'JOB PREFERENCES', $h2);

            if ($resume->desired_salary) {
                $section->addText($prefLabels['salary'].': '.$resume->desired_salary);
            }
            if ($resume->citizenship) {
                $section->addText($prefLabels['citizenship'].': '.$resume->citizenship);
            }
            if ($employmentText) {
                $section->addText($prefLabels['employment'].': '.$employmentText);
            }
            if ($scheduleText) {
                $section->addText($prefLabels['schedule'].': '.$scheduleText);
            }
            if ($resume->ready_to_relocate) {
                $section->addText($readinessLabels['relocate']);
            }
            if ($resume->ready_for_trips) {
                $section->addText($readinessLabels['trips']);
            }
        }

        // Languages
        $langItems = collect($resume->languages ?? []);
        if ($langItems->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText($labels['section_languages'] ?? 'LANGUAGES', $h2);

            $txLangs = $t['languages'] ?? [];
            $parts = [];

            foreach ($langItems as $index => $langItem) {
                $txLang = is_array($txLangs) && array_key_exists($index, $txLangs) ? $txLangs[$index] : null;
                $name = $langItem['name'] ?? '';
                $level = $txLang['level'] ?? ($langItem['level'] ?? '');
                if (! $name || ! $level) {
                    continue;
                }
                $parts[] = $name.': '.$level;
            }

            if ($parts) {
                $section->addText(implode(', ', $parts));
            }
        }

        // Certifications
        $certItems = collect($resume->certificates ?? []);
        if ($certItems->isNotEmpty()) {
            $section->addTextBreak(1);
            $section->addText($labels['section_certifications'] ?? 'CERTIFICATIONS', $h2);

            $txCerts = $t['certificates'] ?? [];

            foreach ($certItems as $index => $cert) {
                $txCert = is_array($txCerts) && array_key_exists($index, $txCerts) ? $txCerts[$index] : null;
                $title = $txCert['title'] ?? ($cert['title'] ?? '');
                $org = $txCert['organization'] ?? ($cert['organization'] ?? '');
                $issued = $cert['issued_at'] ?? '';

                $line = '✔ '.$title;
                if ($org || $issued) {
                    $line .= ' — '.$org;
                    if ($issued) {
                        $line .= ', '.$issued;
                    }
                }

                $section->addText($line);
            }
        }

        return $phpWord;
    }

    protected function buildViewData(Resume $resume, string $lang): array
    {
        $rawTranslations = $resume->translations;

        if (is_string($rawTranslations)) {
            $decoded = json_decode($rawTranslations, true) ?: [];
        } else {
            $decoded = $rawTranslations ?? [];
        }

        $translations = $decoded[$lang] ?? null;

        return [
            'resume' => $resume->load(['experiences', 'educations', 'skills']),
            'lang' => $lang,
            't' => $translations,
            'labels' => $this->labelsFor($lang),
            'photo_base64' => $this->photoToBase64($resume),
        ];
    }

    protected function labelsFor(string $lang): array
    {
        $locale = $lang === 'ru' ? 'ru' : 'en';

        app()->setLocale($locale);

        return [
            'section_professional_summary' => __('resume.pdf.professional_summary'),
            'section_work_experience' => __('resume.pdf.work_experience'),
            'section_education' => __('resume.pdf.education'),
            'section_skills' => __('resume.pdf.skills'),
            'section_preferences' => __('resume.pdf.preferences'),
            'section_languages' => __('resume.pdf.languages'),
            'section_certifications' => __('resume.pdf.certifications'),
            'present' => __('resume.pdf.present'),
        ];
    }

    protected function photoToBase64(Resume $resume): ?string
    {
        $path = $resume->profile_photo_path;

        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        $contents = Storage::disk('public')->get($path);
        $mime = 'image/jpeg';

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'png') {
            $mime = 'image/png';
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    /**
     * Get absolute path to photo for DOCX image embedding.
     */
    protected function photoPathForDocx(Resume $resume): ?string
    {
        $path = $resume->profile_photo_path;

        if (! $path) {
            return null;
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    public function getDisplayFileName(Resume $resume, string $lang = 'ru'): string
    {
        $lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';

        return $this->buildDisplayFileName($resume, $lang, 'docx');
    }

    protected function buildDisplayFileName(Resume $resume, string $lang, string $extension): string
    {
        $base = trim(($resume->first_name ?? '').' '.($resume->last_name ?? ''));

        if ($base !== '') {
            $base = preg_replace('~[^\pL\d]+~u', '-', $base);
            $base = trim($base, '-');
            $base = mb_strtolower($base);
        } else {
            $base = 'resume';
        }

        if ($lang === 'ru') {
            $code = random_int(1000, 9999);
        } else {
            $code = random_int(10000, 99999);
        }

        return $base.'-'.$code.'.'.$extension;
    }
}
