<?php

namespace Modules\ResumeCreate\Repositories;

use App\Models\Resume;
use App\Models\ResumeEducation;
use App\Models\ResumeExperience;
use App\Models\ResumeSkill;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\ResumeCreate\Interfaces\ResumeCreateInterface;

class ResumeCreateRepository implements ResumeCreateInterface
{
    public function saveForUser(int $userId, array $data): Resume
    {
        return DB::transaction(function () use ($userId, $data) {
            $resume = Resume::where('user_id', $userId)->first();

            $payload = $this->mapPayloadToResumeAttributes($data);

            if ($resume === null) {
                $payload['user_id'] = $userId;
                $resume = Resume::create($payload);
            } else {
                $resume->update($payload);
            }

            $this->syncExperiences($resume, Arr::get($data, 'experiences', []));
            $this->syncEducations($resume, Arr::get($data, 'educations', []));
            $this->syncSkills($resume, Arr::get($data, 'skills', []));

            $resume->languages = Arr::get($data, 'languages', []);
            $resume->certificates = Arr::get($data, 'certificates', []);
            $resume->translations = null;
            $resume->save();

            return $resume->fresh(['experiences', 'educations', 'skills']);
        });
    }

    public function getForUser(int $userId): ?Resume
    {
        return Resume::with(['experiences', 'educations', 'skills'])
            ->where('user_id', $userId)
            ->first();
    }

    protected function mapPayloadToResumeAttributes(array $data): array
    {
        $personal = $data['personal'] ?? [];
        $job = $data['job'] ?? [];
        $summary = $data['summary'] ?? [];

        $attributes = [
            'first_name' => Arr::get($personal, 'first_name'),
            'last_name' => Arr::get($personal, 'last_name'),
            'contact_email' => Arr::get($personal, 'email'),
            'phone' => Arr::get($personal, 'phone'),
            'city' => Arr::get($personal, 'city'),
            'country' => Arr::get($personal, 'country'),
            'gender' => $this->normalizeGender(Arr::get($personal, 'gender')),
            // Front qanday yuborsa (masalan, 17-12-2000) shunday saqlaymiz,
            // lekin yoshni hisoblash uchun alohida yil ham saqlanadi.
            'birth_date' => Arr::get($personal, 'birth_date'),
            'birth_year' => $this->normalizeBirthYear(Arr::get($personal, 'birth_date')),
            'profile_photo_path' => Arr::get($personal, 'photo_path'),
            'linkedin_url' => Arr::get($personal, 'linkedin_url'),
            'github_url' => Arr::get($personal, 'github_url'),
            'portfolio_url' => Arr::get($personal, 'portfolio_url'),
            'desired_position' => Arr::get($job, 'desired_position'),
            'desired_salary' => Arr::get($job, 'desired_salary'),
            'citizenship' => Arr::get($job, 'citizenship'),
            'employment_types' => Arr::get($job, 'employment_types', []),
            'work_schedules' => Arr::get($job, 'work_schedules', []),
            'ready_to_relocate' => (bool) Arr::get($job, 'ready_to_relocate', false),
            'ready_for_trips' => (bool) Arr::get($job, 'ready_for_trips', false),
            'professional_summary' => Arr::get($summary, 'text'),
        ];

        if (! empty($attributes['desired_position'])) {
            $attributes['title'] = $attributes['desired_position'];
        }

        if (! empty($attributes['professional_summary'])) {
            $attributes['description'] = $attributes['professional_summary'];
        }

        return $attributes;
    }

    protected function syncExperiences(Resume $resume, array $items): void
    {
        $resume->experiences()->delete();

        foreach ($items as $item) {
            if ($this->isEmptyExperience($item)) {
                continue;
            }

            ResumeExperience::create([
                'resume_id' => $resume->id,
                'position' => $item['position'] ?? null,
                'company' => $item['company'] ?? null,
                'location' => $item['location'] ?? null,
                'start_date' => $this->normalizeYearMonth($item['start_date'] ?? null),
                'end_date' => $this->normalizeYearMonth(
                    ($item['is_current'] ?? false) ? null : ($item['end_date'] ?? null)
                ),
                'is_current' => (bool) ($item['is_current'] ?? false),
                'description' => $item['description'] ?? null,
            ]);
        }
    }

    protected function syncEducations(Resume $resume, array $items): void
    {
        $resume->educations()->delete();

        foreach ($items as $item) {
            if ($this->isEmptyEducation($item)) {
                continue;
            }

            ResumeEducation::create([
                'resume_id' => $resume->id,
                'degree' => $item['degree'] ?? null,
                'institution' => $item['institution'] ?? null,
                'location' => $item['location'] ?? null,
                'start_date' => $this->normalizeYearMonth($item['start_date'] ?? null),
                'end_date' => $this->normalizeYearMonth(
                    ($item['is_current'] ?? false) ? null : ($item['end_date'] ?? null)
                ),
                'is_current' => (bool) ($item['is_current'] ?? false),
                'extra_info' => $item['extra_info'] ?? null,
            ]);
        }
    }

    protected function syncSkills(Resume $resume, array $items): void
    {
        $resume->skills()->delete();

        foreach ($items as $item) {
            $name = trim((string) ($item['name'] ?? ''));
            $level = trim((string) ($item['level'] ?? ''));

            if ($name === '' || $level === '') {
                continue;
            }

            ResumeSkill::create([
                'resume_id' => $resume->id,
                'name' => $name,
                'level' => $level,
            ]);
        }
    }

    protected function normalizeYearMonth(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value.'-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    protected function normalizeGender(?string $value): ?string
    {
        $value = $value !== null ? strtolower(trim($value)) : null;
        return in_array($value, ['male', 'female'], true ) ? $value : null;
    }

    protected function normalizeBirthYear(?string $value): ?int
    {
        $value = $value !== null ? trim($value) : null;
        if ($value === null || $value === '') {
            return null;
        }

        // Agar faqat yil kelgan bo'lsa (old format, masalan: "2000")
        if (ctype_digit($value) && strlen($value) === 4) {
            $year = (int) $value;
        } elseif (preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            // Yangi format: "DD-MM-YYYY" – faqat yil qismini olamiz
            $year = (int) substr($value, -4);
        } else {
            return null;
        }

        if ($year < 1900 || $year > (int) date('Y')) {
            return null;
        }

        return $year;
    }

    protected function isEmptyExperience(array $item): bool
    {
        $fields = Arr::only($item, ['position', 'company', 'location', 'start_date', 'end_date', 'description']);

        foreach ($fields as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function isEmptyEducation(array $item): bool
    {
        $fields = Arr::only($item, ['degree', 'institution', 'location', 'start_date', 'end_date', 'extra_info']);

        foreach ($fields as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
