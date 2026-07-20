<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNationalityRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:nationalities,name'],
            'groups' => ['sometimes', 'array'],
            'groups.*' => ['integer', 'exists:nationality_groups,id'],
        ];
    }
}
