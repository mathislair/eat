<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNationalityGroupRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:nationality_groups,name'],
            'nationalities' => ['sometimes', 'array'],
            'nationalities.*' => ['integer', 'exists:nationalities,id'],
        ];
    }
}
