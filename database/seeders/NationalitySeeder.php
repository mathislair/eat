<?php

namespace Database\Seeders;

use App\Models\Nationality;
use App\Models\NationalityGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class NationalitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The best-known culinary nationalities, keyed by the group they fall into.
     *
     * A nationality may appear under several groups (Thai is both "Asian" and
     * "Spicy") — that is what the many-to-many pivot is for. This map is the
     * single source of truth for the catalogue: edit it here, or change things
     * at runtime through the admin endpoints. Re-running the seeder is safe
     * (idempotent): existing rows and links are preserved.
     *
     * @var array<string, list<string>>
     */
    private const GROUPS = [
        'Asian' => ['Chinese', 'Japanese', 'Thai', 'Indian', 'Vietnamese', 'Korean'],
        'European' => ['Italian', 'French', 'Spanish', 'Greek'],
        'Mediterranean' => ['Italian', 'Greek', 'Spanish', 'Lebanese', 'Moroccan', 'Turkish'],
        'Spicy' => ['Thai', 'Indian', 'Mexican', 'Korean'],
        'Comfort food' => ['American', 'Italian', 'French'],
    ];

    public function run(): void
    {
        foreach (self::GROUPS as $groupName => $nationalityNames) {
            $group = NationalityGroup::firstOrCreate(['name' => $groupName]);

            $nationalityIds = (new Collection($nationalityNames))
                ->map(fn (string $name): int => Nationality::firstOrCreate(['name' => $name])->id)
                ->all();

            // Keep the group's other links intact; only add what's missing.
            $group->nationalities()->syncWithoutDetaching($nationalityIds);
        }
    }
}
