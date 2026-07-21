<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
use App\Models\User;
use App\Support\UserTaste;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PreferenceImpactTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $nationalities  [nationality id => preference]
     * @param  array<string, array<string, string>>  $criteria  [type => [value => preference]]
     */
    private function saveTaste(User $user, array $nationalities = [], array $criteria = []): void
    {
        $user->nationalityPreferences()->sync(
            collect($nationalities)->mapWithKeys(fn ($pref, $id) => [$id => ['preference' => $pref]])->all()
        );
        foreach ($criteria as $type => $values) {
            foreach ($values as $value => $pref) {
                $user->attributePreferences()->create(['type' => $type, 'value' => $value, 'preference' => $pref]);
            }
        }
    }

    private function eventWithAttendee(User $attendee, array $attributes = []): Event
    {
        $event = Event::factory()->create($attributes);
        $event->attendees()->attach($attendee);

        return $event;
    }

    public function test_the_vote_form_is_prefilled_from_saved_preferences(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create(['name' => 'Italian']);

        $this->saveTaste($user, [$italian->id => 'want'], ['price' => ['€€' => 'avoid']]);

        $this->actingAs($user)->get("/events/{$event->id}/vote")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Vote')
                ->where('prefilled', true)
                ->where("ballot.nationalities.{$italian->id}", 'want')
                ->where('ballot.criteria.price.€€', 'avoid')
            );
    }

    public function test_the_vote_form_is_not_prefilled_once_a_ballot_exists(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create();
        $thai = Nationality::factory()->create();

        // Standing taste says Italian, but the cast ballot says Thai — the
        // ballot wins and nothing is re-seeded.
        $this->saveTaste($user, [$italian->id => 'want']);
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        $ballot->nationalities()->sync([$thai->id => ['preference' => 'avoid']]);

        $this->actingAs($user)->get("/events/{$event->id}/vote")
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefilled', false)
                ->where("ballot.nationalities.{$thai->id}", 'avoid')
                ->missing("ballot.nationalities.{$italian->id}")
            );
    }

    public function test_the_vote_form_is_not_prefilled_without_any_preferences(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->get("/events/{$event->id}/vote")
            ->assertInertia(fn (Assert $page) => $page->where('prefilled', false));
    }

    public function test_closing_seeds_a_ballot_from_preferences_for_non_voters(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        // A silent attendee who never votes but hard-vetoes Sushi.
        $silent = User::factory()->create();
        $event->attendees()->attach($silent);
        $sushi = Nationality::factory()->create(['name' => 'Sushi']);
        $this->saveTaste($silent, [$sushi->id => 'avoid']);

        $this->actingAs($creator)->post("/events/{$event->id}/validate");

        // Their taste became a real ballot that shapes the outcome.
        $this->assertDatabaseHas('event_votes', [
            'event_id' => $event->id,
            'user_id' => $silent->id,
        ]);
        $this->assertTrue($event->fresh()->isClosed());

        $this->actingAs($creator)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.nationalities.0.name', 'Sushi')
                ->where('summary.nationalities.0.vetoed', true)
                ->where('summary.participation.voted', 1)
            );
    }

    public function test_attendees_without_preferences_are_not_seeded(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        $ghost = User::factory()->create();
        $event->attendees()->attach($ghost);

        $this->actingAs($creator)->post("/events/{$event->id}/validate");

        $this->assertDatabaseMissing('event_votes', [
            'event_id' => $event->id,
            'user_id' => $ghost->id,
        ]);
    }

    public function test_seeding_never_overwrites_an_existing_ballot(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        $voter = User::factory()->create();
        $event->attendees()->attach($voter);
        $italian = Nationality::factory()->create();
        $thai = Nationality::factory()->create();

        // The voter's standing taste (Italian) differs from what they actually
        // cast (Thai) — closing must keep the cast ballot untouched.
        $this->saveTaste($voter, [$italian->id => 'want']);
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $voter->id,
            'submitted_at' => now(),
        ]);
        $ballot->nationalities()->sync([$thai->id => ['preference' => 'want']]);

        $this->actingAs($creator)->post("/events/{$event->id}/validate");

        $fresh = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $voter->id]);
        $this->assertEqualsCanonicalizing([$thai->id], $fresh->nationalities->pluck('id')->all());
    }

    public function test_seed_ballot_carries_criteria_preferences(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();
        $event->attendees()->attach($user);
        $this->saveTaste($user, [], ['diet' => ['vegan' => 'want'], 'price' => ['€' => 'avoid']]);

        UserTaste::seedBallot($event, $user);

        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertSame('want', $ballot->criteria->firstWhere('value', 'vegan')->preference->value);
        $this->assertSame('avoid', $ballot->criteria->firstWhere('value', '€')->preference->value);
    }
}
