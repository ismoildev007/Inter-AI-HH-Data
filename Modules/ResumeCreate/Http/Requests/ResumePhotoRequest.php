<?php

namespace Modules\ResumeCreate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResumePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:6144'],
        ];
    }
}
