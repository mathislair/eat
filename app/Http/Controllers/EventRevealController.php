<?php

namespace App\Http\Controllers;

use App\Enums\AttributeType;
use App\Enums\SwipeDecision;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\RestaurantSwipe;
use App\Support\RestaurantMatcher;
use App\Support\SwipeResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EventRevealController extends Controller
{
    /**
     * The Tinder-style reveal: a ranked shortlist to swipe through, plus where
     * the group currently stands.
     */
    public function show(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);
        abort_unless($event->isClosed(), 403, 'Voting is still open.');

        RestaurantMatcher::ensureFor($event);

        $event->load(['restaurants.nationality', 'restaurants.criteria']);
        $result = SwipeResult::for($event);

        $mySwipes = $event->swipes()
            ->where('user_id', $request->user()->id)
            ->pluck('decision', 'restaurant_id');

        $restaurants = $event->restaurants->map(fn (Restaurant $restaurant) => [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'description' => $restaurant->description,
            'cuisine' => $restaurant->nationality?->name,
            'criteria' => $restaurant->criteria
                ->map(fn ($criterion) => [
                    'type' => $criterion->type->value,
                    'label' => $this->attributeLabel($criterion->type, $criterion->value),
                ])
                ->values(),
            'position' => $restaurant->pivot->position,
            'accepts' => $result['tallies'][$restaurant->id]['accepts'] ?? 0,
            'rejects' => $result['tallies'][$restaurant->id]['rejects'] ?? 0,
            'mine' => $mySwipes[$restaurant->id] ?? null,
        ]);

        $byId = $restaurants->keyBy('id');

        return Inertia::render('Events/Reveal', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'meal_label' => $event->meal->label(),
            ],
            'restaurants' => $restaurants->values(),
            'match' => $result['matchId'] ? $byId->get($result['matchId']) : null,
            'leader' => $result['leaderId'] ? $byId->get($result['leaderId']) : null,
            'stats' => [
                'attendees' => $result['attendees'],
                'finished' => $result['finished'],
            ],
        ]);
    }

    /**
     * Record (or change) the current attendee's swipe on one restaurant.
     */
    public function swipe(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('view', $event);
        abort_unless($event->isClosed(), 403, 'Voting is still open.');

        $data = $request->validate([
            'restaurant_id' => [
                'required',
                'integer',
                Rule::exists('event_restaurant', 'restaurant_id')->where('event_id', $event->id),
            ],
            'decision' => ['required', Rule::enum(SwipeDecision::class)],
        ]);

        RestaurantSwipe::updateOrCreate(
            [
                'event_id' => $event->id,
                'restaurant_id' => $data['restaurant_id'],
                'user_id' => $request->user()->id,
            ],
            ['decision' => $data['decision']],
        );

        return redirect()->route('events.reveal', $event);
    }

    private function attributeLabel(AttributeType $type, string $value): string
    {
        foreach ($type->options() as $option) {
            if ($option->value === $value) {
                return $option->label();
            }
        }

        return $value;
    }
}
