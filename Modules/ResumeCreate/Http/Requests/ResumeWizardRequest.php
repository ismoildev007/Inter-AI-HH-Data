<?php

namespace Modules\ResumeCreate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth check controller ichida resolveUserFromRequest() orqali qilinadi.
        // Bu yerda faqat validatsiya uchun ruxsat beramiz.
        return true;
    }

    public function rules(): array
    {
        return [
            'personal.first_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'personal.last_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'personal.email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'personal.phone' => ['nullable', 'string', 'max:32'],
            'personal.city' => ['nullable', 'string', 'max:255'],
            'personal.country' => ['nullable', 'string', 'max:255'],
            'personal.gender' => ['nullable', 'in:male,female'],
            'personal.birth_date' => ['nullable', 'digits:4'],
            'personal.photo_path' => ['nullable', 'string', 'max:1024'],
            'personal.linkedin_url' => ['nullable', 'string', 'max:1024'],
            'personal.github_url' => ['nullable', 'string', 'max:1024'],
            'personal.portfolio_url' => ['nullable', 'string', 'max:1024'],

            'job.desired_position' => ['nullable', 'string', 'min:3', 'max:255'],
            'job.desired_salary' => ['nullable', 'string', 'max:65535'],
            'job.citizenship' => ['nullable', 'string', 'max:255'],
            'job.employment_types' => ['nullable', 'array', 'min:1'],
            'job.employment_types.*' => ['string', 'max:255'],
            'job.work_schedules' => ['nullable', 'array', 'min:1'],
            'job.work_schedules.*' => ['nullable','string', 'max:255'],
            'job.ready_to_relocate' => ['nullable','boolean'],
            'job.ready_for_trips' => ['nullable','boolean'],

            'summary.text' => ['nullable', 'string'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.position' => ['nullable', 'string', 'max:255'],
            'experiences.*.company' => ['nullable', 'string', 'max:255'],
            'experiences.*.location' => ['nullable', 'string', 'max:255'],
            'experiences.*.start_date' => ['nullable', 'string', 'max:10'],
            'experiences.*.end_date' => ['nullable', 'string', 'max:10'],
            'experiences.*.is_current' => ['nullable','boolean'],
            'experiences.*.description' => ['nullable', 'string'],

            'educations' => ['nullable', 'array'],
            'educations.*.degree' => ['nullable', 'string', 'max:255'],
            'educations.*.institution' => ['nullable', 'string', 'max:255'],
            'educations.*.location' => ['nullable', 'string', 'max:255'],
            'educations.*.start_date' => ['nullable', 'string', 'max:10'],
            'educations.*.end_date' => ['nullable', 'string', 'max:10'],
            'educations.*.is_current' => ['nullable', 'boolean'],
            'educations.*.extra_info' => ['nullable', 'string'],

            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['nullable', 'string', 'max:255'],
            'skills.*.level' => ['nullable', 'string', 'max:255'],

            'languages' => ['nullable', 'array'],
            'languages.*.name' => ['nullable', 'string', 'max:255'],
            'languages.*.level' => ['nullable', 'string', 'max:255'],

            'certificates' => ['nullable', 'array'],
            'certificates.*.title' => ['nullable', 'string', 'max:255'],
            'certificates.*.organization' => ['nullable', 'string', 'max:255'],
            'certificates.*.issued_at' => ['nullable', 'string', 'max:10'],
            'certificates.*.certificate_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
