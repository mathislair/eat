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

    public function test_an_attendee_can_submit_a_ballot(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$italian->id, $thai->id],
            'criteria' => [
                'price' => ['€€'],
                'diet' => ['vegan'],
                'style' => ['spicy'],
            ],
        ])->assertRedirect(route('events.show', $event));

        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertNotNull($ballot);
        $this->assertEqualsCanonicalizing([$italian->id, $thai->id], $ballot->nationalities->pluck('id')->all());
        $this->assertCount(3, $ballot->criteria);
    }

    public function test_submitting_again_replaces_the_ballot_without_duplicating(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);
        $a = Nationality::factory()->create();
        $b = Nationality::factory()->create();

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$a->id],
            'criteria' => ['price' => ['€']],
        ]);
        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [$b->id],
            'criteria' => ['price' => ['€€€']],
        ]);

        $this->assertSame(1, EventVote::where('event_id', $event->id)->where('user_id', $user->id)->count());
        $ballot = EventVote::firstWhere(['event_id' => $event->id, 'user_id' => $user->id]);
        $this->assertEqualsCanonicalizing([$b->id], $ballot->nationalities->pluck('id')->all());
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

    public function test_voting_is_rejected_once_the_event_is_closed(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user, ['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'nationalities' => [],
        ])->assertForbidden();
    }

    public function test_an_invalid_criteria_value_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->post("/events/{$event->id}/vote", [
            'criteria' => ['price' => ['banana']],
        ])->assertSessionHasErrors('criteria.price.0');
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

        $this->actingAs($creator)->post("/events/{$event->id}/vote", [
            'nationalities' => [],
        ])->assertForbidden();
    }

    public function test_results_are_hidden_while_voting(): void
    {
        $user = User::factory()->create();
        $event = $this->eventWithAttendee($user);

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('event.status', 'voting')
                ->where('summary', null)
            );
    }

    public function test_the_summary_ranks_nationalities_by_votes_when_closed(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $mexican = Nationality::factory()->create(['name' => 'Mexican']);

        // Thai=3, Italian=2, Mexican=1
        $this->castBallot($event, $creator, [$italian, $thai]);
        $this->castBallot($event, User::factory()->create(), [$thai, $mexican]);
        $this->castBallot($event, User::factory()->create(), [$italian, $thai]);

        $event->update(['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($creator)->get("/events/{$event->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.nationalities.0.name', 'Thai')
                ->where('summary.nationalities.0.votes', 3)
                ->where('summary.participation.voted', 3)
            );
    }

    public function test_the_summary_tallies_criteria_by_type_when_closed(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);

        // price: €€ x2, € x1  => winner €€
        $this->castBallot($event, $creator, [], ['price' => ['€€']]);
        $this->castBallot($event, User::factory()->create(), [], ['price' => ['€€']]);
        $this->castBallot($event, User::factory()->create(), [], ['price' => ['€']]);

        $event->update(['status' => EventStatus::Closed, 'validated_at' => now()]);

        $this->actingAs($creator)->get("/events/{$event->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('summary.criteria.price.0.value', '€€')
                ->where('summary.criteria.price.0.votes', 2)
            );
    }

    private function castBallot(Event $event, User $user, array $nationalities = [], array $criteria = []): void
    {
        $event->attendees()->syncWithoutDetaching($user);
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        $ballot->nationalities()->attach(collect($nationalities)->pluck('id')->all());
        foreach ($criteria as $type => $values) {
            foreach ($values as $value) {
                $ballot->criteria()->create(['type' => $type, 'value' => $value]);
            }
        }
    }
}
