<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\PreferenceMapRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventVoteRequest extends FormRequest
{
    use PreferenceMapRules;

    public function authorize(): bool
    {
        // Authorisation is enforced by the `can:vote,event` route middleware
        // (attendee-only); the voting-window phase is checked in the controller.
        return true;
    }

    /**
     * The ballot is a map of option → preference; neutral options are simply
     * left out.
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
