<?php

namespace Modules\ResumeCreate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeWizardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'personal.first_name' => ['required', 'string', 'min:1', 'max:255'],
            'personal.last_name' => ['required', 'string', 'min:1', 'max:255'],
            'personal.email' => ['required', 'email:rfc,dns', 'max:255'],
            'personal.phone' => ['required', 'string', 'max:32'],
            'personal.city' => ['required', 'string', 'max:255'],
            'personal.country' => ['nullable', 'string', 'max:255'],
            'personal.photo_path' => ['nullable', 'string', 'max:1024'],
            'personal.linkedin_url' => ['nullable', 'string', 'max:1024'],
            'personal.github_url' => ['nullable', 'string', 'max:1024'],
            'personal.portfolio_url' => ['nullable', 'string', 'max:1024'],

            'job.desired_position' => ['required', 'string', 'min:3', 'max:255'],
            'job.desired_salary' => ['nullable', 'string', 'max:65535'],
            'job.citizenship' => ['nullable', 'string', 'max:255'],
            'job.employment_types' => ['required', 'array', 'min:1'],
            'job.employment_types.*' => ['string', 'max:255'],
            'job.work_schedules' => ['required', 'array', 'min:1'],
            'job.work_schedules.*' => ['string', 'max:255'],
            'job.ready_to_relocate' => ['boolean'],
            'job.ready_for_trips' => ['boolean'],

            'summary.text' => ['required', 'string'],

            'experiences' => ['nullable', 'array'],
            'experiences.*.position' => ['nullable', 'string', 'max:255'],
            'experiences.*.company' => ['nullable', 'string', 'max:255'],
            'experiences.*.location' => ['nullable', 'string', 'max:255'],
            'experiences.*.start_date' => ['nullable', 'string', 'max:10'],
            'experiences.*.end_date' => ['nullable', 'string', 'max:10'],
            'experiences.*.is_current' => ['boolean'],
            'experiences.*.description' => ['nullable', 'string'],

            'educations' => ['nullable', 'array'],
            'educations.*.degree' => ['required_with:educations.*.institution,educations.*.start_date', 'nullable', 'string', 'max:255'],
            'educations.*.institution' => ['required_with:educations.*.degree,educations.*.start_date', 'nullable', 'string', 'max:255'],
            'educations.*.location' => ['nullable', 'string', 'max:255'],
            'educations.*.start_date' => ['required_with:educations.*.degree,educations.*.institution', 'nullable', 'string', 'max:10'],
            'educations.*.end_date' => ['nullable', 'string', 'max:10'],
            'educations.*.is_current' => ['boolean'],
            'educations.*.extra_info' => ['nullable', 'string'],

            'skills' => ['nullable', 'array'],
            'skills.*.name' => ['required_with:skills.*.level', 'nullable', 'string', 'max:255'],
            'skills.*.level' => ['required_with:skills.*.name', 'nullable', 'string', 'max:255'],

            'languages' => ['nullable', 'array'],
            'languages.*.name' => ['required_with:languages.*.level', 'nullable', 'string', 'max:255'],
            'languages.*.level' => ['required_with:languages.*.name', 'nullable', 'string', 'max:255'],

            'certificates' => ['nullable', 'array'],
            'certificates.*.title' => ['required_with:certificates.*.organization,certificates.*.issued_at,certificates.*.certificate_id', 'nullable', 'string', 'max:255'],
            'certificates.*.organization' => ['required_with:certificates.*.title,certificates.*.issued_at,certificates.*.certificate_id', 'nullable', 'string', 'max:255'],
            'certificates.*.issued_at' => ['required_with:certificates.*.title,certificates.*.organization,certificates.*.certificate_id', 'nullable', 'string', 'max:10'],
            'certificates.*.certificate_id' => ['required_with:certificates.*.title,certificates.*.organization,certificates.*.issued_at', 'nullable', 'string', 'max:255'],
        ];
    }
}

