<?php

namespace Tests\Feature;

use App\Models\Nationality;
use App\Models\NationalityGroup;
use Database\Seeders\NationalitySeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NationalityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_nationality_can_belong_to_many_groups(): void
    {
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $asian = NationalityGroup::factory()->create(['name' => 'Asian']);
        $spicy = NationalityGroup::factory()->create(['name' => 'Spicy']);

        $thai->groups()->attach([$asian->id, $spicy->id]);

        $this->assertEqualsCanonicalizing(
            ['Asian', 'Spicy'],
            $thai->fresh()->groups->pluck('name')->all(),
        );
    }

    public function test_a_group_can_hold_many_nationalities(): void
    {
        $asian = NationalityGroup::factory()->create();
        $nationalities = Nationality::factory()->count(3)->create();

        $asian->nationalities()->attach($nationalities);

        $this->assertCount(3, $asian->fresh()->nationalities);
    }

    public function test_nationality_names_are_unique(): void
    {
        Nationality::factory()->create(['name' => 'Italian']);

        $this->expectException(QueryException::class);

        Nationality::factory()->create(['name' => 'Italian']);
    }

    public function test_the_seeder_preseeds_nationalities_and_groups(): void
    {
        $this->seed(NationalitySeeder::class);

        // Thai should exist and land in both "Asian" and "Spicy".
        $thai = Nationality::where('name', 'Thai')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Asian', 'Spicy'],
            $thai->groups->pluck('name')->all(),
        );

        $this->assertTrue(NationalityGroup::whereIn('name', [
            'Asian', 'European', 'Mediterranean', 'Spicy', 'Comfort food',
        ])->count() === 5);
    }

    public function test_the_seeder_is_idempotent(): void
    {
        $this->seed(NationalitySeeder::class);
        $groupsAfterFirst = NationalityGroup::count();
        $nationalitiesAfterFirst = Nationality::count();

        $this->seed(NationalitySeeder::class);

        $this->assertSame($groupsAfterFirst, NationalityGroup::count());
        $this->assertSame($nationalitiesAfterFirst, Nationality::count());
    }
}
