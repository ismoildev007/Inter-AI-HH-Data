<?php

namespace Modules\ResumeCreate\Jobs;

use App\Models\Resume;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stichoza\GoogleTranslate\GoogleTranslate;

class GenerateResumeTranslationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected int $resumeId
    ) {
    }

    public function handle(): void
    {
        $resume = Resume::with(['experiences', 'educations', 'skills'])->find($this->resumeId);

        if (! $resume) {
            return;
        }

        $translator = new GoogleTranslate();
        $translator->setSource('auto');

        $translations = $resume->translations ?? [];

        $labelBase = array(
            'professional_summary' => 'Professional summary',
            'work_experience' => 'Work experience',
            'education' => 'Education',
            'skills' => 'Skills',
            'languages' => 'Languages',
            'certifications' => 'Certifications',
            'present' => 'Present',
        );

        foreach (['ru', 'en'] as $lang) {
            $translator->setTarget($lang);

            $translations[$lang] = [
                'professional_summary' => $this->translateText($translator, (string) $resume->professional_summary),
                'experiences' => $resume->experiences->map(function ($exp) use ($translator) {
                    return [
                        'position' => $this->translateText($translator, (string) $exp->position),
                        // kompaniya nomini tarjima qilmaymiz
                        'company' => (string) $exp->company,
                        'location' => $this->translateText($translator, (string) $exp->location),
                        'description' => $this->translateText($translator, (string) $exp->description),
                    ];
                })->values()->all(),
                'educations' => $resume->educations->map(function ($edu) use ($translator) {
                    return [
                        'degree' => $this->translateText($translator, (string) $edu->degree),
                        // universitet nomini tarjima qilmaymiz
                        'institution' => (string) $edu->institution,
                        'location' => $this->translateText($translator, (string) $edu->location),
                        'extra_info' => $this->translateText($translator, (string) $edu->extra_info),
                    ];
                })->values()->all(),
                'skills' => $resume->skills->map(function ($skill) use ($translator) {
                    return [
                        // skil nomi tarjima qilinmaydi
                        'name' => (string) $skill->name,
                        // faqat level tarjima qilinadi
                        'level' => $this->translateText($translator, (string) $skill->level),
                    ];
                })->values()->all(),
                'languages' => collect($resume->languages ?? [])->map(function ($langItem) use ($translator) {
                    return [
                        // til nomini tarjima qilmaymiz (uzbek, eng, rus va h.k.)
                        'name' => (string) ($langItem['name'] ?? ''),
                        'level' => $this->translateText($translator, (string) ($langItem['level'] ?? '')),
                    ];
                })->values()->all(),
                'certificates' => collect($resume->certificates ?? [])->map(function ($cert) use ($translator) {
                    return [
                        'title' => $this->translateText($translator, (string) ($cert['title'] ?? '')),
                        'organization' => $this->translateText($translator, (string) ($cert['organization'] ?? '')),
                    ];
                })->values()->all(),
                'headings' => [
                    'professional_summary' => $this->translateText($translator, $labelBase['professional_summary']),
                    'work_experience' => $this->translateText($translator, $labelBase['work_experience']),
                    'education' => $this->translateText($translator, $labelBase['education']),
                    'skills' => $this->translateText($translator, $labelBase['skills']),
                    'languages' => $this->translateText($translator, $labelBase['languages']),
                    'certifications' => $this->translateText($translator, $labelBase['certifications']),
                    'present' => $this->translateText($translator, $labelBase['present']),
                ],
            ];
        }

        $resume->translations = $translations;
        $resume->save();
    }

    protected function translateText(GoogleTranslate $translator, string $text): string
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return '';
        }

        try {
            return (string) $translator->translate($trimmed);
        } catch (\Throwable) {
            return $text;
        }
    }
}
