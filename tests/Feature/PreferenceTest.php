<?php

namespace Tests\Feature;

use App\Models\Nationality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_open_the_preferences_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/preferences')
            ->assertInertia(fn (Assert $page) => $page->component('Preferences/Edit'));
    }

    public function test_a_guest_cannot_open_the_preferences_page(): void
    {
        $this->get('/preferences')->assertRedirect('/login');
    }

    public function test_a_user_can_save_their_food_preferences(): void
    {
        $user = User::factory()->create();
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);

        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [$italian->id => 'want', $thai->id => 'avoid'],
            'criteria' => [
                'price' => ['€€' => 'want'],
                'diet' => ['vegan' => 'avoid'],
            ],
        ])->assertRedirect(route('preferences.edit'));

        $user->refresh();
        $this->assertEqualsCanonicalizing(
            [$italian->id, $thai->id],
            $user->nationalityPreferences->pluck('id')->all()
        );
        $this->assertSame('want', $user->nationalityPreferences->firstWhere('id', $italian->id)->pivot->preference);
        $this->assertSame('avoid', $user->nationalityPreferences->firstWhere('id', $thai->id)->pivot->preference);
        $this->assertCount(2, $user->attributePreferences);
        $this->assertSame('avoid', $user->attributePreferences->firstWhere('value', 'vegan')->preference->value);
    }

    public function test_saving_replaces_the_previous_profile(): void
    {
        $user = User::factory()->create();
        $a = Nationality::factory()->create();
        $b = Nationality::factory()->create();

        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [$a->id => 'want'],
            'criteria' => ['price' => ['€' => 'want']],
        ]);
        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [$b->id => 'avoid'],
            'criteria' => ['price' => ['€€€' => 'want']],
        ]);

        $user->refresh();
        $this->assertEqualsCanonicalizing([$b->id], $user->nationalityPreferences->pluck('id')->all());
        $this->assertSame('avoid', $user->nationalityPreferences->firstWhere('id', $b->id)->pivot->preference);
        $this->assertCount(1, $user->attributePreferences);
        $this->assertSame('€€€', $user->attributePreferences->first()->value);
    }

    public function test_neutral_preferences_are_not_stored(): void
    {
        $user = User::factory()->create();
        $italian = Nationality::factory()->create();

        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [$italian->id => 'want'],
            'criteria' => ['price' => [], 'diet' => [], 'style' => []],
        ]);

        $user->refresh();
        $this->assertCount(1, $user->nationalityPreferences);
        $this->assertCount(0, $user->attributePreferences);
    }

    public function test_an_invalid_preference_is_rejected(): void
    {
        $user = User::factory()->create();
        $italian = Nationality::factory()->create();

        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [$italian->id => 'maybe'],
        ])->assertSessionHasErrors("nationalities.{$italian->id}");
    }

    public function test_an_invalid_criteria_value_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/preferences', [
            'criteria' => ['price' => ['banana' => 'want']],
        ])->assertSessionHasErrors('criteria.price');
    }

    public function test_an_unknown_nationality_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/preferences', [
            'nationalities' => [999999 => 'want'],
        ])->assertSessionHasErrors('nationalities.999999');
    }
}
