<?php

namespace App\Support;

use App\Enums\AttributeType;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates a closed event's ballots into a shareable summary and, above all,
 * a group decision: every option is scored by how the room felt about it —
 * "want" (🟢) lifts it, "avoid" (🔴) drags it down. The highest net score is
 * where the group is heading. Pure read, computed on the fly since votes are
 * frozen once the event is closed.
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
     * @return list<array{id: int, name: string, wants: int, avoids: int, score: int}>
     */
    private static function nationalityTally(Event $event): array
    {
        return DB::table('event_vote_nationality')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_nationality.event_vote_id')
            ->join('nationalities', 'nationalities.id', '=', 'event_vote_nationality.nationality_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('nationalities.id', 'nationalities.name')
            ->get([
                'nationalities.id as id',
                'nationalities.name as name',
                DB::raw(static::wantsExpr('event_vote_nationality').' as wants'),
                DB::raw(static::avoidsExpr('event_vote_nationality').' as avoids'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'wants' => (int) $row->wants,
                'avoids' => (int) $row->avoids,
                'score' => (int) $row->wants - (int) $row->avoids,
            ])
            ->sort(static::byScore(fn ($n) => $n['name']))
            ->values()
            ->all();
    }

    /**
     * Tally per attribute type, each ranked by net score (wants − avoids).
     *
     * @return array<string, list<array{value: string, label: string, wants: int, avoids: int, score: int}>>
     */
    private static function criteriaTally(Event $event): array
    {
        $rows = DB::table('event_vote_attribute')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_attribute.event_vote_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('event_vote_attribute.type', 'event_vote_attribute.value')
            ->get([
                'event_vote_attribute.type as type',
                'event_vote_attribute.value as value',
                DB::raw(static::wantsExpr('event_vote_attribute').' as wants'),
                DB::raw(static::avoidsExpr('event_vote_attribute').' as avoids'),
            ]);

        $summary = [];

        foreach (AttributeType::cases() as $type) {
            $summary[$type->value] = $rows
                ->where('type', $type->value)
                ->map(fn ($row) => [
                    'value' => $row->value,
                    'label' => static::labelFor($type, $row->value),
                    'wants' => (int) $row->wants,
                    'avoids' => (int) $row->avoids,
                    'score' => (int) $row->wants - (int) $row->avoids,
                ])
                ->sort(static::byScore(fn ($item) => static::labelFor($type, $item['value'])))
                ->values()
                ->all();
        }

        return $summary;
    }

    private static function wantsExpr(string $table): string
    {
        return "sum(case when {$table}.preference = 'want' then 1 else 0 end)";
    }

    private static function avoidsExpr(string $table): string
    {
        return "sum(case when {$table}.preference = 'avoid' then 1 else 0 end)";
    }

    /**
     * Rank by score desc, then most wants, then a stable tiebreak label asc.
     *
     * @param  callable(array<string, mixed>): string  $label
     * @return callable(array<string, mixed>, array<string, mixed>): int
     */
    private static function byScore(callable $label): callable
    {
        return fn (array $a, array $b): int => [$b['score'], $b['wants']] <=> [$a['score'], $a['wants']]
            ?: strcmp($label($a), $label($b));
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
