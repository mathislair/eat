<?php

namespace Database\Seeders;

use App\Models\Nationality;
use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * A starter catalogue of places to suggest, each tagged with the same
     * price/diet/style vocabulary attendees vote on. Placeholder data — swap it
     * for a real source later; re-running is idempotent (keyed by name).
     *
     * @var list<array{name: string, cuisine: string, attributes: array<string, list<string>>}>
     */
    private const RESTAURANTS = [
        ['name' => 'Trattoria Bella', 'cuisine' => 'Italian', 'attributes' => ['price' => ['€€'], 'style' => ['savory'], 'diet' => ['vegetarian']]],
        ['name' => 'Sakura Sushi', 'cuisine' => 'Japanese', 'attributes' => ['price' => ['€€€'], 'style' => ['savory'], 'diet' => ['gluten_free']]],
        ['name' => 'Bangkok Spice', 'cuisine' => 'Thai', 'attributes' => ['price' => ['€€'], 'style' => ['spicy'], 'diet' => ['vegan']]],
        ['name' => 'Taj Mahal', 'cuisine' => 'Indian', 'attributes' => ['price' => ['€€'], 'style' => ['spicy'], 'diet' => ['vegetarian', 'halal']]],
        ['name' => 'El Toro', 'cuisine' => 'Mexican', 'attributes' => ['price' => ['€'], 'style' => ['spicy']]],
        ['name' => 'Le Petit Bistro', 'cuisine' => 'French', 'attributes' => ['price' => ['€€€'], 'style' => ['savory']]],
        ['name' => 'Pho Saigon', 'cuisine' => 'Vietnamese', 'attributes' => ['price' => ['€'], 'style' => ['savory']]],
        ['name' => 'Seoul Kitchen', 'cuisine' => 'Korean', 'attributes' => ['price' => ['€€'], 'style' => ['spicy', 'sour']]],
        ['name' => 'Athens Taverna', 'cuisine' => 'Greek', 'attributes' => ['price' => ['€€'], 'style' => ['savory'], 'diet' => ['vegetarian']]],
        ['name' => 'Beirut Mezze', 'cuisine' => 'Lebanese', 'attributes' => ['price' => ['€€'], 'diet' => ['halal', 'vegan']]],
        ['name' => 'Casa Madrid', 'cuisine' => 'Spanish', 'attributes' => ['price' => ['€€'], 'style' => ['savory']]],
        ['name' => 'Big Apple Diner', 'cuisine' => 'American', 'attributes' => ['price' => ['€'], 'style' => ['sweet']]],
    ];

    public function run(): void
    {
        foreach (self::RESTAURANTS as $entry) {
            $nationality = Nationality::firstWhere('name', $entry['cuisine']);

            $restaurant = Restaurant::firstOrCreate(
                ['name' => $entry['name']],
                ['nationality_id' => $nationality?->id],
            );

            foreach ($entry['attributes'] as $type => $values) {
                foreach ($values as $value) {
                    $restaurant->criteria()->firstOrCreate(['type' => $type, 'value' => $value]);
                }
            }
        }
    }
}
