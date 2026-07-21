<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\SwipeDecision;
use App\Models\Event;
use App\Models\Nationality;
use App\Models\Restaurant;
use App\Models\RestaurantSwipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_restaurant_belongs_to_a_nationality(): void
    {
        $italian = Nationality::factory()->create();
        $restaurant = Restaurant::factory()->create(['nationality_id' => $italian->id]);

        $this->assertTrue($restaurant->nationality->is($italian));
    }

    public function test_a_restaurant_has_criteria_cast_to_their_type(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->criteria()->create(['type' => 'price', 'value' => '€€']);

        $criterion = $restaurant->criteria()->first();
        $this->assertSame(AttributeType::Price, $criterion->type);
        $this->assertSame('€€', $criterion->value);
    }

    public function test_a_swipe_records_a_decision_cast_to_its_enum(): void
    {
        $event = Event::factory()->create();
        $restaurant = Restaurant::factory()->create();
        $user = User::factory()->create();

        $swipe = RestaurantSwipe::create([
            'event_id' => $event->id,
            'restaurant_id' => $restaurant->id,
            'user_id' => $user->id,
            'decision' => 'accept',
        ]);

        $this->assertSame(SwipeDecision::Accept, $swipe->decision);
        $this->assertTrue($swipe->restaurant->is($restaurant));
    }
}
