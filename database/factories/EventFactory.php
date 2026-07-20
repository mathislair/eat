<?php

namespace Database\Factories;

use App\Enums\Meal;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'creator_id' => User::factory(),
            'name' => fake()->sentence(3),
            'date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
            'meal' => fake()->randomElement(Meal::cases()),
            // Supply the code here too, so factories don't depend on model
            // events firing (seeders disable them; bulk inserts bypass them).
            'invite_code' => fn () => Event::generateUniqueInviteCode(),
        ];
    }
}
