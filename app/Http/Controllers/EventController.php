<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Http\Requests\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Support\EventSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        return redirect()->route('events.show', $event);
    }

    public function show(Request $request, Event $event): Response
    {
        Gate::authorize('view', $event);

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
        ]);
    }

    /**
     * Close voting and freeze the ballots (creator only, force-close allowed).
     */
    public function validate(Event $event): RedirectResponse
    {
        Gate::authorize('validate', $event);

        $event->update([
            'status' => EventStatus::Closed,
            'validated_at' => now(),
        ]);

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
        Gate::authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index');
    }

    public function leave(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('leave', $event);

        $event->attendees()->detach($request->user()->id);

        return redirect()->route('events.index');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function mealOptions(): array
    {
        return array_map(
            fn (\App\Enums\Meal $meal) => ['value' => $meal->value, 'label' => $meal->label()],
            \App\Enums\Meal::cases(),
        );
    }
}
