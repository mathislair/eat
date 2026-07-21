<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreferenceMapRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    use PreferenceMapRules;

    public function authorize(): bool
    {
        // Behind the `auth` middleware; any signed-in user edits their own.
        return $this->user() !== null;
    }

    /**
     * The taste profile is a map of option → preference; neutral options are
     * simply left out — identical in shape to an event ballot.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->preferenceMapRules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->validatePreferenceKeys($validator));
    }
}
