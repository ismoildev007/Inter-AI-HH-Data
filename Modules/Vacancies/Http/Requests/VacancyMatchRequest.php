<?php

namespace Modules\Vacancies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VacancyMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_id' => 'nullable',
            'query' => ['nullable', 'string', 'max:255'],
        ];
    }
}
