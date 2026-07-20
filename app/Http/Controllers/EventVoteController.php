<?php

namespace App\Http\Controllers;

use App\Enums\AttributeType;
use App\Http\Requests\StoreEventVoteRequest;
use App\Models\Event;
use App\Models\EventVote;
use App\Models\Nationality;
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

        $ballot = $event->votes()
            ->with(['nationalities:id', 'criteria'])
            ->firstWhere('user_id', $request->user()->id);

        return Inertia::render('Events/Vote', [
            'event' => ['id' => $event->id, 'name' => $event->name],
            'nationalities' => Nationality::orderBy('name')->get(['id', 'name']),
            'criteriaTypes' => $this->criteriaTypes(),
            'ballot' => [
                'nationalities' => $ballot?->nationalities->pluck('id')->all() ?? [],
                'criteria' => $this->groupCriteria($ballot),
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

            $ballot->nationalities()->sync($data['nationalities'] ?? []);

            // Replace criteria wholesale — the submission is the full ballot.
            $ballot->criteria()->delete();
            foreach (($data['criteria'] ?? []) as $type => $values) {
                foreach ($values as $value) {
                    $ballot->criteria()->create(['type' => $type, 'value' => $value]);
                }
            }
        });

        return redirect()->route('events.show', $event);
    }

    /**
     * @return list<array{type: string, label: string, options: list<array{value: string, label: string}>}>
     */
    private function criteriaTypes(): array
    {
        return array_map(fn (AttributeType $type) => [
            'type' => $type->value,
            'label' => $type->label(),
            'options' => array_map(
                fn ($option) => ['value' => $option->value, 'label' => $option->label()],
                $type->options(),
            ),
        ], AttributeType::cases());
    }

    /**
     * The attendee's current criteria selections, grouped by type.
     *
     * @return array<string, list<string>>
     */
    private function groupCriteria(?EventVote $ballot): array
    {
        $grouped = [];
        foreach (AttributeType::cases() as $type) {
            $grouped[$type->value] = [];
        }

        foreach ($ballot?->criteria ?? [] as $criterion) {
            $grouped[$criterion->type->value][] = $criterion->value;
        }

        return $grouped;
    }
}
