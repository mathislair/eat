<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
use App\Models\Restaurant;
use App\Models\User;
use App\Support\RestaurantMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function restaurant(string $name, ?Nationality $cuisine, array $attributes = []): Restaurant
    {
        $restaurant = Restaurant::factory()->create([
            'name' => $name,
            'nationality_id' => $cuisine?->id,
        ]);

        foreach ($attributes as $type => $values) {
            foreach ($values as $value) {
                $restaurant->criteria()->create(['type' => $type, 'value' => $value]);
            }
        }

        return $restaurant;
    }

    private function ballot(Event $event, array $nationalities = [], array $criteria = []): void
    {
        $user = User::factory()->create();
        $event->attendees()->syncWithoutDetaching($user);
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
        $ballot->nationalities()->sync(
            collect($nationalities)->mapWithKeys(fn ($p, $id) => [$id => ['preference' => $p]])->all()
        );
        foreach ($criteria as $type => $values) {
            foreach ($values as $value => $preference) {
                $ballot->criteria()->create(['type' => $type, 'value' => $value, 'preference' => $preference]);
            }
        }
    }

    public function test_it_ranks_the_most_wanted_cuisine_first(): void
    {
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $this->restaurant('Bella', $italian);
        $this->restaurant('Bangkok', $thai);
        $event = Event::factory()->create();

        // Italian wanted twice, Thai once.
        $this->ballot($event, [$italian->id => 'want']);
        $this->ballot($event, [$italian->id => 'want', $thai->id => 'want']);

        RestaurantMatcher::generate($event);

        $ordered = $event->restaurants()->get();
        $this->assertSame('Bella', $ordered[0]->name);
        $this->assertSame(2, (int) $ordered[0]->pivot->match_score);
        $this->assertSame(1, (int) $ordered[0]->pivot->position);
        $this->assertSame('Bangkok', $ordered[1]->name);
        $this->assertSame(1, (int) $ordered[1]->pivot->match_score);
    }

    public function test_it_drops_restaurants_whose_cuisine_was_vetoed(): void
    {
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $this->restaurant('Bella', $italian);
        $this->restaurant('Bangkok', $thai);
        $event = Event::factory()->create();

        // Thai is wanted by one, but a single veto rules it (and its place) out.
        $this->ballot($event, [$thai->id => 'want', $italian->id => 'want']);
        $this->ballot($event, [$thai->id => 'avoid']);

        RestaurantMatcher::generate($event);

        $names = $event->restaurants()->pluck('name');
        $this->assertContains('Bella', $names);
        $this->assertNotContains('Bangkok', $names);
    }

    public function test_it_drops_restaurants_with_a_vetoed_attribute(): void
    {
        $thai = Nationality::factory()->create(['name' => 'Thai']);
        $this->restaurant('Bangkok', $thai, ['style' => ['spicy']]);
        $event = Event::factory()->create();

        $this->ballot($event, [$thai->id => 'want'], ['style' => ['spicy' => 'avoid']]);

        RestaurantMatcher::generate($event);

        $this->assertNotContains('Bangkok', $event->restaurants()->pluck('name'));
    }

    public function test_it_adds_wanted_attributes_to_the_score(): void
    {
        $italian = Nationality::factory()->create(['name' => 'Italian']);
        $this->restaurant('Bella', $italian, ['price' => ['€€']]);
        $event = Event::factory()->create();

        // 1 want on the cuisine + 2 wants on its price = score 3.
        $this->ballot($event, [$italian->id => 'want'], ['price' => ['€€' => 'want']]);
        $this->ballot($event, [], ['price' => ['€€' => 'want']]);

        RestaurantMatcher::generate($event);

        $this->assertSame(3, (int) $event->restaurants()->first()->pivot->match_score);
    }

    public function test_it_keeps_neutral_restaurants_when_nothing_is_vetoed(): void
    {
        $this->restaurant('Somewhere', null);
        $event = Event::factory()->create();

        RestaurantMatcher::generate($event);

        $this->assertSame(1, $event->restaurants()->count());
        $this->assertSame(0, (int) $event->restaurants()->first()->pivot->match_score);
    }
}
