<?php

namespace Tests\Feature;

use App\Enums\Meal;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_invite_code_is_generated_when_an_event_is_created(): void
    {
        $event = Event::factory()->create();

        $this->assertNotNull($event->invite_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $event->invite_code);
    }

    public function test_invite_codes_are_unique_across_events(): void
    {
        $codes = Event::factory()->count(25)->create()->pluck('invite_code');

        $this->assertCount(25, $codes->unique());
    }

    public function test_an_invite_code_is_set_even_when_model_events_are_disabled(): void
    {
        // Seeders use WithoutModelEvents, and bulk inserts bypass the `creating`
        // hook — the factory must still supply a valid invite code on its own.
        $event = Event::withoutEvents(fn () => Event::factory()->create());

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $event->invite_code);
    }

    public function test_meal_is_cast_to_the_enum(): void
    {
        $event = Event::factory()->create(['meal' => Meal::Dinner]);

        $this->assertSame(Meal::Dinner, $event->fresh()->meal);
    }

    public function test_an_event_belongs_to_its_creator(): void
    {
        $creator = User::factory()->create();
        $event = Event::factory()->create(['creator_id' => $creator->id]);

        $this->assertTrue($event->creator->is($creator));
    }

    public function test_attendees_can_be_attached_to_an_event(): void
    {
        $event = Event::factory()->create();
        $user = User::factory()->create();

        $event->attendees()->attach($user);

        $this->assertTrue($event->attendees->contains($user));
    }
}
