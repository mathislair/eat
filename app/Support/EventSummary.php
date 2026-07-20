<?php

namespace App\Support;

use App\Enums\AttributeType;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates a closed event's ballots into a shareable summary: nationalities
 * ranked by votes, criteria tallied per type, and participation. Pure read —
 * computed on the fly, since votes are frozen once the event is closed.
 */
class EventSummary
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Event $event): array
    {
        return [
            'nationalities' => static::nationalityTally($event),
            'criteria' => static::criteriaTally($event),
            'participation' => [
                'voted' => $event->votes()->count(),
                'total' => $event->attendees()->count(),
            ],
        ];
    }

    /**
     * @return list<array{id: int, name: string, votes: int}>
     */
    private static function nationalityTally(Event $event): array
    {
        return DB::table('event_vote_nationality')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_nationality.event_vote_id')
            ->join('nationalities', 'nationalities.id', '=', 'event_vote_nationality.nationality_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('nationalities.id', 'nationalities.name')
            ->orderByDesc('votes')
            ->orderBy('nationalities.name')
            ->get([
                'nationalities.id as id',
                'nationalities.name as name',
                DB::raw('count(*) as votes'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'votes' => (int) $row->votes,
            ])
            ->all();
    }

    /**
     * Tally per attribute type, each ordered by votes desc.
     *
     * @return array<string, list<array{value: string, label: string, votes: int}>>
     */
    private static function criteriaTally(Event $event): array
    {
        $rows = DB::table('event_vote_attribute')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_attribute.event_vote_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('event_vote_attribute.type', 'event_vote_attribute.value')
            ->orderByDesc('votes')
            ->get([
                'event_vote_attribute.type as type',
                'event_vote_attribute.value as value',
                DB::raw('count(*) as votes'),
            ]);

        $summary = [];

        foreach (AttributeType::cases() as $type) {
            $summary[$type->value] = $rows
                ->where('type', $type->value)
                ->map(fn ($row) => [
                    'value' => $row->value,
                    'label' => static::labelFor($type, $row->value),
                    'votes' => (int) $row->votes,
                ])
                ->values()
                ->all();
        }

        return $summary;
    }

    private static function labelFor(AttributeType $type, string $value): string
    {
        foreach ($type->options() as $option) {
            if ($option->value === $value) {
                return $option->label();
            }
        }

        return $value;
    }
}
