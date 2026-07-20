<?php

namespace Database\Factories;

use App\Models\NationalityGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NationalityGroup>
 */
class NationalityGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}
