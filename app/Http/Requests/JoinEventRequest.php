<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string', 'exists:events,invite_code'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_code.exists' => 'No event was found for that invite code.',
        ];
    }
}
