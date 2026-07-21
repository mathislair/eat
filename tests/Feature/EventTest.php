<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\Meal;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/events')->assertRedirect('/login');
    }

    public function test_index_shows_only_events_the_user_created_or_joined(): void
    {
        $user = User::factory()->create();
        $created = Event::factory()->create(['creator_id' => $user->id]);
        $joined = Event::factory()->create();
        $joined->attendees()->attach($user);
        $unrelated = Event::factory()->create();

        $response = $this->actingAs($user)->get('/events');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Events/Index')
            ->has('events', 2)
        );
    }

    public function test_a_user_can_view_the_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/events/create')
            ->assertInertia(fn (Assert $page) => $page->component('Events/Create'));
    }

    public function test_a_user_can_create_an_event_and_is_auto_joined(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/events', [
            'name' => 'Team dinner',
            'date' => '2026-08-01',
            'meal' => 'dinner',
        ]);

        $event = Event::firstWhere('name', 'Team dinner');

        $this->assertNotNull($event);
        $this->assertSame($user->id, $event->creator_id);
        $this->assertSame(Meal::Dinner, $event->meal);
        $this->assertNotEmpty($event->invite_code);
        $this->assertTrue($event->attendees->contains($user), 'creator should auto-join');
        $response->assertRedirect(route('events.hub', $event));
    }

    public function test_creating_an_event_requires_valid_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/events', [
            'name' => '',
            'date' => 'not-a-date',
            'meal' => 'brunch',
        ])->assertSessionHasErrors(['name', 'date', 'meal']);
    }

    public function test_an_attendee_can_open_the_event_hub(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $user->id]);
        $event->attendees()->attach($user);

        $this->actingAs($user)->get("/events/{$event->id}/hub")
            ->assertInertia(fn (Assert $page) => $page->component('Events/Show'));
    }

    public function test_opening_a_voting_event_jumps_to_the_vote(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $user->id]);
        $event->attendees()->attach($user);

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertRedirect(route('events.vote.edit', $event));
    }

    public function test_opening_a_closed_event_jumps_to_the_reveal(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create([
            'creator_id' => $user->id,
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);
        $event->attendees()->attach($user);

        $this->actingAs($user)->get("/events/{$event->id}")
            ->assertRedirect(route('events.reveal', $event));
    }

    public function test_a_non_attendee_cannot_view_an_event(): void
    {
        $event = Event::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get("/events/{$event->id}")->assertForbidden();
    }

    public function test_a_user_can_join_an_event_with_a_valid_code(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/events/join', [
            'invite_code' => $event->invite_code,
        ]);

        $this->assertTrue($event->fresh()->attendees->contains($user));
        $response->assertRedirect(route('events.show', $event));
    }

    public function test_joining_with_an_invalid_code_fails(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/events/join', [
            'invite_code' => 'NOPE0000',
        ])->assertSessionHasErrors('invite_code');
    }

    public function test_joining_an_event_twice_does_not_duplicate(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();
        $event->attendees()->attach($user);

        $this->actingAs($user)->post('/events/join', [
            'invite_code' => $event->invite_code,
        ]);

        $this->assertSame(1, $event->fresh()->attendees()->where('user_id', $user->id)->count());
    }

    public function test_the_creator_can_delete_an_event(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $user->id]);

        $this->actingAs($user)->delete("/events/{$event->id}")
            ->assertRedirect(route('events.index'));

        $this->assertModelMissing($event);
    }

    public function test_a_non_creator_cannot_delete_an_event(): void
    {
        $event = Event::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->delete("/events/{$event->id}")->assertForbidden();
        $this->assertModelExists($event);
    }

    public function test_an_attendee_can_leave_an_event(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $attendee = User::factory()->create();
        $event->attendees()->attach($attendee);

        $this->actingAs($attendee)->delete("/events/{$event->id}/leave")
            ->assertRedirect(route('events.index'));

        $this->assertFalse($event->fresh()->attendees->contains($attendee));
    }

    public function test_the_creator_cannot_leave_their_own_event(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);
        $event->attendees()->attach($creator);

        $this->actingAs($creator)->delete("/events/{$event->id}/leave")->assertForbidden();

        $this->assertTrue($event->fresh()->attendees->contains($creator));
    }
}
