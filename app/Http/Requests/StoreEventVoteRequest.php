<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access is enforced by the `can:vote,event` route middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nationalities' => ['sometimes', 'array'],
            'nationalities.*' => ['integer', 'exists:nationalities,id'],
            'criteria' => ['sometimes', 'array'],
        ];

        // One rule set per attribute type, so each value must belong to its type.
        foreach (AttributeType::cases() as $type) {
            $rules["criteria.{$type->value}"] = ['sometimes', 'array'];
            $rules["criteria.{$type->value}.*"] = [Rule::in($type->values())];
        }

        return $rules;
    }
}
