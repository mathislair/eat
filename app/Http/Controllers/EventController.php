<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Enums\Meal;
use App\Http\Requests\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\Restaurant;
use App\Support\EventSummary;
use App\Support\RestaurantMatcher;
use App\Support\SwipeResult;
use App\Support\UserTaste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Events the current user created or joined.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $events = Event::query()
            ->where('creator_id', $user->id)
            ->orWhereHas('attendees', fn ($q) => $q->whereKey($user->id))
            ->withCount('attendees')
            ->orderBy('date')
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->toDateString(),
                'meal' => $event->meal->value,
                'meal_label' => $event->meal->label(),
                'status' => $event->status->value,
                'attendees_count' => $event->attendees_count,
                'is_creator' => $event->creator_id === $user->id,
            ]);

        return Inertia::render('Events/Index', ['events' => $events]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Create', [
            'meals' => $this->mealOptions(),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create([
            ...$request->validated(),
            'creator_id' => $request->user()->id,
        ]);

        // Creator auto-joins the attendees list.
        $event->attendees()->attach($request->user());

        // Land on the hub so the host can grab the invite code and rally people.
        return redirect()->route('events.hub', $event);
    }

    /**
     * Opening an event jumps straight into its current phase: the vote while
     * it's open, the restaurant reveal once it's closed. The details/host
     * controls live on the hub.
     */
    public function show(Request $request, Event $event): RedirectResponse
    {
        return $event->isClosed()
            ? redirect()->route('events.reveal', $event)
            : redirect()->route('events.vote.edit', $event);
    }

    /**
     * The event hub: details, invite code, attendees, the vote verdict, and
     * host controls (validate, delete).
     */
    public function hub(Request $request, Event $event): Response
    {
        $event->load(['creator', 'attendees']);
        $user = $request->user();

        $hasVoted = $event->votes()->where('user_id', $user->id)->exists();

        return Inertia::render('Events/Show', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'date' => $event->date->toDateString(),
                'meal' => $event->meal->value,
                'meal_label' => $event->meal->label(),
                'status' => $event->status->value,
                'invite_code' => $event->invite_code,
                'join_url' => route('events.join', $event->invite_code),
                'creator' => ['id' => $event->creator->id, 'name' => $event->creator->name],
                'attendees' => $event->attendees->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                ]),
                'is_creator' => $event->creator_id === $user->id,
                'has_voted' => $hasVoted,
            ],
            // Participation is always visible; the tally is a secret ballot,
            // revealed only once the event is closed.
            'participation' => [
                'voted' => $event->votes()->count(),
                'total' => $event->attendees->count(),
            ],
            'summary' => $event->isClosed() ? EventSummary::build($event) : null,
            'reveal' => $this->revealSummary($event),
        ]);
    }

    /**
     * A peek at the restaurant reveal for the event card: how many spots made
     * the shortlist and whether the group has already agreed on one.
     *
     * @return array{count: int, match: array{name: string, cuisine: string|null}|null}|null
     */
    private function revealSummary(Event $event): ?array
    {
        if (! $event->isClosed()) {
            return null;
        }

        RestaurantMatcher::ensureFor($event);
        $result = SwipeResult::for($event);

        $match = $result['matchId']
            ? Restaurant::with('nationality')->find($result['matchId'])
            : null;

        return [
            'count' => count($result['tallies']),
            'match' => $match ? [
                'name' => $match->name,
                'cuisine' => $match->nationality?->name,
            ] : null,
        ];
    }

    /**
     * Close voting and freeze the ballots (creator only, force-close allowed).
     */
    public function validate(Event $event): RedirectResponse
    {
        // Right (creator-only) is enforced by the `can:validate,event` middleware.
        // Here we guard the *phase*: closing an already-closed event is a no-op,
        // so send the host to the hub with a gentle note rather than 403.
        if (! $event->isVoting()) {
            return redirect()->route('events.hub', $event)
                ->with('info', 'This event is already closed.');
        }

        $event->update([
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);

        // Attendees who never voted still count through their standing food
        // preferences, so a taste profile shapes the group's outcome even when
        // someone forgets to cast a ballot.
        UserTaste::seedMissingBallots($event);

        // Freeze the votes into a ranked restaurant shortlist to swipe through.
        RestaurantMatcher::generate($event);

        return redirect()->route('events.show', $event);
    }

    /**
     * Show the join form, optionally pre-filled from a shared link.
     */
    public function joinForm(?string $code = null): Response
    {
        return Inertia::render('Events/Join', ['code' => $code]);
    }

    public function join(JoinEventRequest $request): RedirectResponse
    {
        $event = Event::firstWhere('invite_code', $request->validated()['invite_code']);

        // syncWithoutDetaching keeps a repeat join idempotent.
        $event->attendees()->syncWithoutDetaching([$request->user()->id]);

        return redirect()->route('events.show', $event);
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return redirect()->route('events.index');
    }

    public function leave(Request $request, Event $event): RedirectResponse
    {
        $event->attendees()->detach($request->user()->id);

        return redirect()->route('events.index');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function mealOptions(): array
    {
        return array_map(
            fn (Meal $meal) => ['value' => $meal->value, 'label' => $meal->label()],
            Meal::cases(),
        );
    }
}
