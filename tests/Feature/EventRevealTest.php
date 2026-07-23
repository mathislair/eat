<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\SwipeDecision;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\RestaurantSwipe;
use App\Models\User;
use App\Support\RestaurantMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventRevealTest extends TestCase
{
    use RefreshDatabase;

    private function closedEventWith(User $attendee, int $restaurants = 2): Event
    {
        $event = Event::factory()->create(['status' => EventStatus::Closed, 'validated_at' => now()]);
        $event->attendees()->attach($attendee);
        Restaurant::factory()->count($restaurants)->create();
        RestaurantMatcher::generate($event);

        return $event;
    }

    public function test_a_non_attendee_cannot_open_the_reveal(): void
    {
        $event = $this->closedEventWith(User::factory()->create());
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get("/events/{$event->id}/reveal")->assertForbidden();
    }

    public function test_the_reveal_redirects_to_the_vote_while_voting_is_open(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(); // defaults to voting
        $event->attendees()->attach($user);

        // Wrong phase, not a permission problem: an attendee who lands on the
        // reveal too early is sent back to cast their ballot, with a flash.
        $this->actingAs($user)->get("/events/{$event->id}/reveal")
            ->assertRedirect(route('events.vote.edit', $event))
            ->assertSessionHas('info');
    }

    public function test_an_attendee_sees_the_shortlist_when_closed(): void
    {
        $user = User::factory()->create();
        $event = $this->closedEventWith($user, restaurants: 3);

        $this->actingAs($user)->get("/events/{$event->id}/reveal")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Events/Reveal')
                ->has('restaurants', 3)
            );
    }

    public function test_an_attendee_can_swipe_a_restaurant(): void
    {
        $user = User::factory()->create();
        $event = $this->closedEventWith($user);
        $restaurant = $event->restaurants()->first();

        $this->actingAs($user)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $restaurant->id,
            'decision' => 'accept',
        ])->assertRedirect(route('events.reveal', $event));

        $this->assertDatabaseHas('restaurant_swipes', [
            'event_id' => $event->id,
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'decision' => 'accept',
        ]);
    }

    public function test_swiping_again_updates_the_decision(): void
    {
        $user = User::factory()->create();
        $event = $this->closedEventWith($user);
        $restaurant = $event->restaurants()->first();

        $this->actingAs($user)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $restaurant->id, 'decision' => 'accept',
        ]);
        $this->actingAs($user)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $restaurant->id, 'decision' => 'reject',
        ]);

        $this->assertSame(1, RestaurantSwipe::where('user_id', $user->id)->count());
        $this->assertSame(SwipeDecision::Reject, RestaurantSwipe::where('user_id', $user->id)->value('decision'));
    }

    public function test_a_restaurant_outside_the_shortlist_is_rejected(): void
    {
        $user = User::factory()->create();
        $event = $this->closedEventWith($user);
        $stray = Restaurant::factory()->create(); // never shortlisted for this event

        $this->actingAs($user)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $stray->id, 'decision' => 'accept',
        ])->assertSessionHasErrors('restaurant_id');
    }

    public function test_when_every_attendee_accepts_a_place_it_becomes_the_match(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create([
            'creator_id' => $creator->id,
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);
        $friend = User::factory()->create();
        $event->attendees()->attach([$creator->id, $friend->id]);
        Restaurant::factory()->count(2)->create();
        RestaurantMatcher::generate($event);
        $pick = $event->restaurants()->first();

        foreach ([$creator, $friend] as $user) {
            $this->actingAs($user)->post("/events/{$event->id}/reveal/swipe", [
                'restaurant_id' => $pick->id, 'decision' => 'accept',
            ]);
        }

        $this->actingAs($creator)->get("/events/{$event->id}/reveal")
            ->assertInertia(fn (Assert $page) => $page
                ->where('match.id', $pick->id)
                ->where('stats.attendees', 2)
            );
    }

    public function test_a_lone_rejection_prevents_a_match(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create([
            'creator_id' => $creator->id,
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);
        $friend = User::factory()->create();
        $event->attendees()->attach([$creator->id, $friend->id]);
        Restaurant::factory()->count(2)->create();
        RestaurantMatcher::generate($event);
        $pick = $event->restaurants()->first();

        $this->actingAs($creator)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $pick->id, 'decision' => 'accept',
        ]);
        $this->actingAs($friend)->post("/events/{$event->id}/reveal/swipe", [
            'restaurant_id' => $pick->id, 'decision' => 'reject',
        ]);

        $this->actingAs($creator)->get("/events/{$event->id}/reveal")
            ->assertInertia(fn (Assert $page) => $page->where('match', null));
    }

    public function test_validating_an_event_builds_the_shortlist(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);
        Restaurant::factory()->count(3)->create();

        $this->actingAs($creator)->post("/events/{$event->id}/validate");

        $this->assertSame(3, $event->restaurants()->count());
    }
}
