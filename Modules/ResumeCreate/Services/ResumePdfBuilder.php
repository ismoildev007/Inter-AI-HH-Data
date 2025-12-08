<?php

namespace Modules\ResumeCreate\Services;

use App\Models\Resume;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ResumePdfBuilder
{
    public function download(Resume $resume, string $lang = 'ru')
    {
        $lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';

        $viewData = $this->buildViewData($resume, $lang);

        $pdf = Pdf::loadView('resumecreate::pdf.resume', $viewData)->setPaper('a4');

        $filename = 'resume-'.$resume->id.'-'.$lang.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Render PDF to a file on disk and return absolute path.
     */
    public function store(Resume $resume, string $lang = 'ru'): string
    {
        $lang = in_array($lang, ['ru', 'en'], true) ? $lang : 'ru';

        $viewData = $this->buildViewData($resume, $lang);

        $pdf = Pdf::loadView('resumecreate::pdf.resume', $viewData)->setPaper('a4');

        $fileName = 'resume-'.$resume->id.'-'.$lang.'.pdf';
        $dir = storage_path('app/resumes');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir.DIRECTORY_SEPARATOR.$fileName;
        $pdf->save($path);

        return $path;
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
}
