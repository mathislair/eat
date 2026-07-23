<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventVoteTest extends TestCase
{
    use RefreshDatabase;

    private function eventWithAttendee(User $attendee, array $attributes = []): Event
    {
        $event = Event::factory()->create($attributes);
        $event->attendees()->attach($attendee);

        return $event;
    }

    public function test_an_attendee_can_open_the_vote_page(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->get("/events/{$event->id}/vote")
            ->assertInertia(fn (Assert $page) => $page->component('Events/Vote'));
    }

    public function test_a_non_attendee_cannot_open_the_vote_page(): void
    {
        $event = Event::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get("/events/{$event->id}/vote")->assertForbidden();
    }

    public function test_opening_the_vote_page_after_close_redirects_to_the_reveal(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user, ['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($user)->get("/events/{$event->id}/vote")
            ->assertRedirect(route('events.reveal', $event));
    }

    public function test_an_attendee_can_submit_a_ballot_with_preferences(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$italian->id => 'want', $thai->id => 'avoid'],
            'criteria' => [
                'price' => ['€€' => 'want'],
                'diet' => ['vegan' => 'avoid'],
                'style' => ['spicy' => 'want'],
            ],
        ])->assertRedirect(route('events.hub', $event));

        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertNotNull($ballot);
        $this->assertEqualsCanonicalizing([$italian->id, $thai->id], $ballot->nationalities->pluck('id')->all());
        $this->assertSame('want', $ballot->nationalities->firstWhere('id', $italian->id)->pivot->preference);
        $this->assertSame('avoid', $ballot->nationalities->firstWhere('id', $thai->id)->pivot->preference);
        $this->assertCount(3, $ballot->criteria);
        $this->assertSame('avoid', $ballot->criteria->firstWhere('value', 'vegan')->preference->value);
    }

    public function test_neutral_options_are_not_stored(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create();

        // Only "want"/"avoid" are ever sent; a neutral option is simply omitted.
        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$italian->id => 'want'],
            'criteria' => ['price' => [], 'diet' => [], 'style' => []],
        ]);

        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertCount(1, $ballot->nationalities);
        $this->assertCount(0, $ballot->criteria);
    }

    public function test_submitting_again_replaces_the_ballot_without_duplicating(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $a = Nationality::factory()->create();
        $b = Nationality::factory()->create();

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$a->id => 'want'],
            'criteria' => ['price' => ['€' => 'want']],
        ]);
        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$b->id => 'avoid'],
            'criteria' => ['price' => ['€€€' => 'want']],
        ]);

        $this->assertSame(1, EventVote::where('event_id', $event->id)->where('user_id', $user->id)->count());
        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertEqualsCanonicalizing([$b->id], $ballot->nationalities->pluck('id')->all());
        $this->assertSame('avoid', $ballot->nationalities->firstWhere('id', $b->id)->pivot->preference);
        $this->assertSame('€€€', $ballot->criteria->firstWhere('type.value', 'price')->value);
    }

    public function test_a_non_attendee_cannot_vote(): void
    {
        $event = Event::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->post("/events/{$event->id}/vote", [
            'nationalities' => [],
        ])->assertForbidden();
    }

    public function test_voting_after_close_redirects_to_the_reveal_without_saving(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user, ['status' => EventStatus::Closed, 'validated_at' => now()]);

        // A closed event is a phase, not a permission problem: the attendee is
        // redirected onto the reveal (with a flash) rather than hitting a 403,
        // and no ballot is recorded.
        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [],
        ])
            ->assertRedirect(route('events.reveal', $event))
            ->assertSessionHas('info');

        $this->assertDatabaseCount('event_votes', 0);
    }

    public function test_an_invalid_criteria_value_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'criteria' => ['price' => ['banana' => 'want']],
        ])->assertSessionHasErrors('criteria.price');
    }

    public function test_an_invalid_preference_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create();

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$italian->id => 'maybe'],
        ])->assertSessionHasErrors("nationalities.{$italian->id}");
    }

    public function test_an_unknown_nationality_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [999999 => 'want'],
        ])->assertSessionHasErrors('nationalities.999999');
    }

    public function test_the_creator_can_validate_the_event(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        $this->actingAs($creator)->post("/events/{$event->id}/validate")
            ->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertSame(EventStatus::Closed, $event->status);
        $this->assertNotNull($event->validated_at);
    }

    public function test_validating_an_already_closed_event_redirects_without_error(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create([
            'creator_id' => $creator->id,
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);
        $event->attendees()->attach($creator);

        $this->actingAs($creator)->post("/events/{$event->id}/validate")
            ->assertRedirect(route('events.hub', $event))
            ->assertSessionHas('info');
    }

    public function test_a_non_creator_cannot_validate_the_event(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $attendee = User::factory()->create();
        $event->attendees()->attach($attendee);

        $this->actingAs($attendee)->post("/events/{$event->id}/validate")->assertForbidden();
        $this->assertTrue($event->fresh()->isVoting());
    }

    public function test_validating_freezes_voting(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        $this->actingAs($creator)->post("/events/{$event->id}/validate");

        // Once frozen, a late ballot is turned away to the reveal, not saved.
        $this->actingAs($creator)->post("/events/{$event->id}/vote", [
            'nationalities' => [],
        ])->assertRedirect(route('events.reveal', $event));

        $this->assertDatabaseCount('event_votes', 0);
    }

    public function test_results_are_hidden_while_voting(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.status', 'voting')
                ->where('summary', null)
            );
    }

    public function test_the_summary_ranks_cuisines_by_wants_when_none_are_vetoed(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $mexican = Nationality::factory()->create(['name' => 'Mexican']);

        // Wants — Thai=3, Italian=2, Mexican=1. No vetoes.
        $this->castBallot($event, $creator, [$italian->id => 'want', $thai->id => 'want']);
        $this->castBallot($event, User::factory()->create(), [$thai->id => 'want', $mexican->id => 'want']);
        $this->castBallot($event, User::factory()->create(), [$italian->id => 'want', $thai->id => 'want']);

        $event->update(['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($creator)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.nationalities.0.name', 'Thai')
                ->where('summary.nationalities.0.wants', 3)
                ->where('summary.nationalities.0.vetoed', false)
                ->where('summary.participation.voted', 3)
            );
    }

    public function test_a_single_avoid_vetoes_a_cuisine_no_matter_how_wanted(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $pizza = Nationality::factory()->create(['name' => 'Pizza']);
        $kebab = Nationality::factory()->create(['name' => 'Kebab']);

        // Kebab is wanted more (3 vs 2) but one lone veto rules it out entirely,
        // so Pizza wins despite fewer wants — the "surtout pas" decides.
        $this->castBallot($event, $creator, [$pizza->id => 'want', $kebab->id => 'want']);
        $this->castBallot($event, User::factory()->create(), [$pizza->id => 'want', $kebab->id => 'want']);
        $this->castBallot($event, User::factory()->create(), [$kebab->id => 'want']);
        $this->castBallot($event, User::factory()->create(), [$kebab->id => 'avoid']);

        $event->update(['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($creator)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.nationalities.0.name', 'Pizza')
                ->where('summary.nationalities.0.wants', 2)
                ->where('summary.nationalities.0.vetoed', false)
                ->where('summary.nationalities.1.name', 'Kebab')
                ->where('summary.nationalities.1.wants', 3)
                ->where('summary.nationalities.1.vetoed', true)
                ->where('summary.nationalities.1.avoids', 1)
            );
    }

    public function test_the_summary_tallies_criteria_by_type_when_closed(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);

        // price: €€ wanted x2, € wanted x1  => winner €€ (2 wants, no veto).
        $this->castBallot($event, $creator, [], ['price' => ['€€' => 'want']]);
        $this->castBallot($event, User::factory()->create(), [], ['price' => ['€€' => 'want']]);
        $this->castBallot($event, User::factory()->create(), [], ['price' => ['€' => 'want']]);

        $event->update(['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($creator)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.criteria.price.0.value', '€€')
                ->where('summary.criteria.price.0.wants', 2)
                ->where('summary.criteria.price.0.vetoed', false)
            );
    }

    /**
     * @param  array<int, string>  $nationalities  [nationality id => preference]
     * @param  array<string, array<string, string>>  $criteria  [type => [value => preference]]
     */
    private function castBallot(Event $event, User $user, array $nationalities = [], array $criteria = []): void
    {
        $event->attendees()->syncWithoutDetaching($user);
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        $ballot->nationalities()->sync(
            collect($nationalities)->mapWithKeys(fn ($pref, $id) => [$id => ['preference' => $pref]])->all()
        );
        foreach ($criteria as $type => $values) {
            foreach ($values as $value => $pref) {
                $ballot->criteria()->create(['type' => $type, 'value' => $value, 'preference' => $pref]);
            }
        }
    }
}
