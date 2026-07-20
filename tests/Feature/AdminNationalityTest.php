<?php

namespace Tests\Feature;

use App\Models\Nationality;
use App\Models\NationalityGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNationalityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_rejected_from_admin_endpoints(): void
    {
        $this->getJson('/admin/nationalities')->assertUnauthorized();
    }

    public function test_non_admins_are_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/admin/nationalities')
            ->assertForbidden();
    }

    public function test_an_admin_can_list_nationalities(): void
    {
        Nationality::factory()->create(['name' => 'Italian']);

        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/admin/nationalities')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Italian']);
    }

    public function test_an_admin_can_create_a_nationality_with_groups(): void
    {
        $admin = User::factory()->admin()->create();
        $asian = NationalityGroup::factory()->create(['name' => 'Asian']);

        $this->actingAs($admin)
            ->postJson('/admin/nationalities', [
                'name' => 'Thai',
                'groups' => [$asian->id],
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Thai']);

        $thai = Nationality::where('name', 'Thai')->firstOrFail();
        $this->assertTrue($thai->groups->contains($asian));
    }

    public function test_creating_a_nationality_rejects_duplicates(): void
    {
        Nationality::factory()->create(['name' => 'Thai']);

        $this->actingAs(User::factory()->admin()->create())
            ->postJson('/admin/nationalities', ['name' => 'Thai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('name');
    }

    public function test_an_admin_can_sync_a_nationalitys_groups(): void
    {
        $admin = User::factory()->admin()->create();
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $asian = NationalityGroup::factory()->create(['name' => 'Asian']);
        $spicy = NationalityGroup::factory()->create(['name' => 'Spicy']);
        $thai->groups()->attach($asian);

        $this->actingAs($admin)
            ->patchJson("/admin/nationalities/{$thai->id}", [
                'groups' => [$spicy->id],
            ])
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            ['Spicy'],
            $thai->fresh()->groups->pluck('name')->all(),
        );
    }

    public function test_an_admin_can_delete_a_nationality(): void
    {
        $admin = User::factory()->admin()->create();
        $thai = Nationality::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/admin/nationalities/{$thai->id}")
            ->assertNoContent();

        $this->assertModelMissing($thai);
    }

    public function test_an_admin_can_create_a_group_with_nationalities(): void
    {
        $admin = User::factory()->admin()->create();
        $thai = Nationality::factory()->create(['name' => 'Thai']);

        $this->actingAs($admin)
            ->postJson('/admin/nationality-groups', [
                'name' => 'Asian',
                'nationalities' => [$thai->id],
            ])
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Asian']);

        $asian = NationalityGroup::where('name', 'Asian')->firstOrFail();
        $this->assertTrue($asian->nationalities->contains($thai));
    }
}
