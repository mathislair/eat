<?php

namespace App\Http\Requests;

use App\Enums\AttributeType;
use App\Enums\VotePreference;
use App\Models\Nationality;
use Illuminate\Contracts\Validation\Validator;
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
     * The ballot is a map of option → preference; neutral options are simply
     * left out. Keys are validated in {@see withValidator()} since Laravel's
     * wildcard rules reach the values, not the array keys.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'nationalities' => ['sometimes', 'array'],
            'nationalities.*' => [Rule::enum(VotePreference::class)],
            'criteria' => ['sometimes', 'array'],
        ];

        // One rule set per attribute type; each value maps to a preference.
        foreach (AttributeType::cases() as $type) {
            $rules["criteria.{$type->value}"] = ['sometimes', 'array'];
            $rules["criteria.{$type->value}.*"] = [Rule::enum(VotePreference::class)];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateNationalityKeys($validator);
            $this->validateCriteriaKeys($validator);
        });
    }

    /**
     * Every nationality key must reference a real nationality.
     */
    private function validateNationalityKeys(Validator $validator): void
    {
        $ids = array_keys((array) $this->input('nationalities', []));

        if ($ids === []) {
            return;
        }

        $known = Nationality::whereIn('id', $ids)->pluck('id')->all();

        foreach ($ids as $id) {
            if (! in_array((int) $id, $known, true)) {
                $validator->errors()->add("nationalities.{$id}", 'The selected nationality is invalid.');
            }
        }
    }

    /**
     * Every criteria key must be a valid value for its attribute type.
     */
    private function validateCriteriaKeys(Validator $validator): void
    {
        foreach (AttributeType::cases() as $type) {
            $values = array_keys((array) $this->input("criteria.{$type->value}", []));

            foreach ($values as $value) {
                if (! in_array($value, $type->values(), true)) {
                    $validator->errors()->add("criteria.{$type->value}", "The selected {$type->value} value is invalid.");
                }
            }
        }
    }
}
