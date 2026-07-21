<?php

namespace Database\Factories;

use App\Models\Nationality;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Kitchen',
            'nationality_id' => Nationality::factory(),
            'description' => fake()->sentence(),
        ];
    }
}
