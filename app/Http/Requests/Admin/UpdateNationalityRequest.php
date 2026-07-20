<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNationalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is enforced by the `can:admin` route middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('nationalities', 'name')->ignore($this->route('nationality')),
            ],
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['integer', 'exists:nationality_groups,id'],
        ];
    }
}
