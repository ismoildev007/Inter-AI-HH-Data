<?php

namespace Modules\Users\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name'  => 'required|string|max:100',
//            'last_name'   => 'required|string|max:100',
//            'email'       => 'required|email|unique:users,email',
            'phone'       => 'nullable|string|max:20|unique:users,phone',
//            'password'    => 'required|string|min:6',
//            'birth_date'  => 'nullable|date',
//            'avatar_path' => 'nullable|string',
//            'verify_code' => 'nullable|string|max:10',
//            'role_id'     => 'nullable|integer|exists:roles,id',

            // qo'shimcha
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx',
            'resume_text' => 'nullable|string',
//            'preferences' => 'nullable|array',
//            'preferences.*.industry_id' => 'nullable|integer|exists:industries,id',
//            'preferences.*.experience_level' => 'nullable|string',
//            'preferences.*.desired_salary_from' => 'nullable|integer',
//            'preferences.*.desired_salary_to' => 'nullable|integer',
//            'preferences.*.currency' => 'nullable|string|max:10',
//            'preferences.*.work_mode' => 'nullable|string',
//            'preferences.*.notes' => 'nullable|string',
//            'preferences.*.cover_letter' => 'nullable|string',
//
//            'locations' => 'nullable|array',
//            'locations.*.text' => 'nullable|string',
//            'locations.*.area_id' => 'nullable|integer|exists:areas,id',
//            'locations.*.is_primary' => 'nullable|boolean',
//
//            'job_types'   => 'nullable|array',
//            'job_types.*' => 'string',

//            'auto_apply_enabled'    => 'nullable|boolean',
//            'auto_apply_limit'      => 'nullable|integer',
//            'notifications_enabled' => 'nullable|boolean',
            'language'              => 'nullable|string|max:5',
        ];
    }


    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
