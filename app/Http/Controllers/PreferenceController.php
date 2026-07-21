<?php

namespace App\Http\Controllers;

use App\Enums\AttributeType;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Models\Nationality;
use App\Support\UserTaste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The user's standing food preferences — a personal taste profile that
 * pre-fills their event ballots and stands in for them when they don't vote.
 */
class PreferenceController extends Controller
{
    /**
     * The preferences form, filled with the user's current tastes.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Preferences/Edit', [
            'nationalities' => Nationality::orderBy('name')->get(['id', 'name']),
            'criteriaTypes' => AttributeType::catalogue(),
            'preferences' => [
                'nationalities' => UserTaste::nationalities($user) ?: (object) [],
                'criteria' => UserTaste::criteria($user),
            ],
            'status' => session('status'),
        ]);
    }

    /**
     * Replace the user's taste profile with the submitted map.
     */
    public function update(UpdatePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data): void {
            // Each picked cuisine carries its preference on the pivot.
            $user->nationalityPreferences()->sync(
                collect($data['nationalities'] ?? [])
                    ->mapWithKeys(fn (string $preference, $id) => [(int) $id => ['preference' => $preference]])
                    ->all()
            );

            // Replace criteria wholesale — the submission is the full profile.
            $user->attributePreferences()->delete();
            foreach (($data['criteria'] ?? []) as $type => $values) {
                foreach ($values as $value => $preference) {
                    $user->attributePreferences()->create([
                        'type' => $type,
                        'value' => $value,
                        'preference' => $preference,
                    ]);
                }
            }
        });

        return redirect()->route('preferences.edit')->with('status', 'preferences-updated');
    }
}
