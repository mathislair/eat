<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventVoteModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_event_defaults_to_the_voting_status(): void
    {
        $event = Event::factory()->create();

        $this->assertSame(EventStatus::Voting, $event->status);
        $this->assertTrue($event->isVoting());
        $this->assertFalse($event->isClosed());
    }

    public function test_an_event_has_many_votes(): void
    {
        $event = Event::factory()->create();
        EventVote::factory()->count(2)->create(['event_id' => $event->id]);

        $this->assertCount(2, $event->votes);
    }

    public function test_a_ballot_belongs_to_its_event_and_user(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();
        $vote = EventVote::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

        $this->assertTrue($vote->event->is($event));
        $this->assertTrue($vote->user->is($user));
    }

    public function test_a_ballot_can_hold_nationalities(): void
    {
        $vote = EventVote::factory()->create();
        $nationality = Nationality::factory()->create();

        $vote->nationalities()->attach($nationality);

        $this->assertTrue($vote->nationalities->contains($nationality));
    }

    public function test_a_ballot_can_hold_criteria_selections_cast_to_their_type(): void
    {
        $vote = EventVote::factory()->create();

        $vote->criteria()->create(['type' => 'diet', 'value' => 'vegan']);

        $criterion = $vote->criteria()->first();
        $this->assertSame(AttributeType::Diet, $criterion->type);
        $this->assertSame('vegan', $criterion->value);
    }

    public function test_a_user_can_only_have_one_ballot_per_event(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();
        EventVote::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        EventVote::factory()->create(['event_id' => $event->id, 'user_id' => $user->id]);
    }
}
