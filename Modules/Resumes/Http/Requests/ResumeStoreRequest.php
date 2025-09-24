<?php

namespace Modules\Resumes\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'file'        => 'nullable|file|mimes:pdf,doc,docx|max:4048',
        ];

    }
}
