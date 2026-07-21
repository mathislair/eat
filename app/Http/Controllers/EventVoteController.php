<?php

namespace App\Http\Controllers;

use App\Enums\AttributeType;
use App\Http\Requests\StoreEventVoteRequest;
use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
use App\Support\UserTaste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventVoteController extends Controller
{
    /**
     * The ballot form for an attendee, pre-filled with their current vote.
     */
    public function edit(Request $request, Event $event): Response
    {
        Gate::authorize('vote', $event);

        $user = $request->user();

        $ballot = $event->votes()
            ->with(['nationalities:id', 'criteria'])
            ->firstWhere('user_id', $user->id);

        // No ballot yet? Seed the form from the voter's standing food
        // preferences so a first-timer starts from their own tastes, not a
        // blank slate. Once they've submitted anything, their ballot wins.
        $prefilled = $ballot === null && UserTaste::isNotEmpty($user);

        $nationalities = $ballot
            ? $ballot->nationalities->mapWithKeys(fn ($n) => [$n->id => $n->pivot->preference])->all()
            : ($prefilled ? UserTaste::nationalities($user) : []);

        return Inertia::render('Events/Vote', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'nationalities' => Nationality::orderBy('name')->get(['id', 'name']),
            'criteriaTypes' => AttributeType::catalogue(),
            'prefilled' => $prefilled,
            'ballot' => [
                'nationalities' => $nationalities ?: (object) [],
                'criteria' => $prefilled ? UserTaste::criteria($user) : $this->groupCriteria($ballot),
            ],
        ]);
    }

    public function store(StoreEventVoteRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('vote', $event);

        $data = $request->validated();

        DB::transaction(function () use ($event, $request, $data): void {
            $ballot = EventVote::updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $request->user()->id],
                ['submitted_at' => now()],
            );

            // Each picked nationality carries its preference on the pivot.
            $ballot->nationalities()->sync(
                collect($data['nationalities'] ?? [])
                    ->mapWithKeys(fn (string $preference, $id) => [(int) $id => ['preference' => $preference]])
                    ->all()
            );

            // Replace criteria wholesale — the submission is the full ballot.
            $ballot->criteria()->delete();
            foreach (($data['criteria'] ?? []) as $type => $values) {
                foreach ($values as $value => $preference) {
                    $ballot->criteria()->create([
                        'type' => $type,
                        'value' => $value,
                        'preference' => $preference,
                    ]);
                }
            }
        });

        // Back to the hub so they can see status and the host can close voting.
        return redirect()->route('events.hub', $event);
    }

    /**
     * The attendee's current criteria preferences, grouped by type as a map of
     * value → preference (e.g. ['price' => ['€€' => 'want'], ...]).
     *
     * @return array<string, object|array<string, string>>
     */
    private function groupCriteria(?EventVote $ballot): array
    {
        $grouped = [];
        foreach (AttributeType::cases() as $type) {
            // An empty map so the front-end always sees an object per type.
            $grouped[$type->value] = (object) [];
        }

        foreach ($ballot?->criteria ?? [] as $criterion) {
            $type = $criterion->type->value;
            $grouped[$type] = (array) $grouped[$type];
            $grouped[$type][$criterion->value] = $criterion->preference->value;
        }

        return $grouped;
    }
}
