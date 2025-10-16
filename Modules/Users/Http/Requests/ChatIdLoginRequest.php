<?php

namespace Modules\Users\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChatIdLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chat_id' => ['required', 'exists:users,chat_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'chat_id.required' => 'Chat ID kiritilishi shart.',
            'chat_id.exists'   => 'Bunday chat ID bilan foydalanuvchi topilmadi.',
        ];
    }
}
