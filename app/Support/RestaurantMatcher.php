<?php

namespace App\Support;

use App\Models\Event;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;

/**
 * Turns a closed event's votes into a ranked restaurant shortlist.
 *
 * Strict veto is honoured: any restaurant whose cuisine — or any of whose
 * price/diet/style tags — was vetoed by even one attendee is dropped entirely.
 * Whatever survives is scored by how many "wants" it collects across the same
 * axes, best first, and stored on the event so everyone swipes the same list.
 */
class RestaurantMatcher
{
    /** How many suggestions to keep on the shortlist. */
    private const LIMIT = 12;

    /**
     * Build (or rebuild) the shortlist for a closed event.
     */
    public static function generate(Event $event): void
    {
        [$natWants, $natVetoed] = static::nationalityProfile($event);
        [$attrWants, $attrVetoed] = static::attributeProfile($event);

        $scored = [];

        foreach (Restaurant::with('criteria')->get() as $restaurant) {
            if ($restaurant->nationality_id && isset($natVetoed[$restaurant->nationality_id])) {
                continue; // its cuisine is off the table for someone
            }

            $vetoed = false;
            $score = $restaurant->nationality_id ? ($natWants[$restaurant->nationality_id] ?? 0) : 0;

            foreach ($restaurant->criteria as $criterion) {
                $type = $criterion->type->value;
                if (isset($attrVetoed[$type][$criterion->value])) {
                    $vetoed = true;
                    break;
                }
                $score += $attrWants[$type][$criterion->value] ?? 0;
            }

            if ($vetoed) {
                continue;
            }

            $scored[] = ['id' => $restaurant->id, 'name' => $restaurant->name, 'score' => $score];
        }

        usort(
            $scored,
            fn ($a, $b) => $b['score'] <=> $a['score'] ?: strcmp($a['name'], $b['name']),
        );

        $position = 1;
        $sync = [];
        foreach (array_slice($scored, 0, self::LIMIT) as $row) {
            $sync[$row['id']] = ['match_score' => $row['score'], 'position' => $position++];
        }

        $event->restaurants()->sync($sync);
    }

    /**
     * Generate the shortlist only if one hasn't been computed yet.
     */
    public static function ensureFor(Event $event): void
    {
        if (! $event->restaurants()->exists()) {
            static::generate($event);
        }
    }

    /**
     * @return array{0: array<int, int>, 1: array<int, true>} [wants by id, vetoed ids]
     */
    private static function nationalityProfile(Event $event): array
    {
        $rows = DB::table('event_vote_nationality')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_nationality.event_vote_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('event_vote_nationality.nationality_id')
            ->get([
                'event_vote_nationality.nationality_id as id',
                DB::raw("sum(case when event_vote_nationality.preference = 'want' then 1 else 0 end) as wants"),
                DB::raw("sum(case when event_vote_nationality.preference = 'avoid' then 1 else 0 end) as avoids"),
            ]);

        $wants = [];
        $vetoed = [];
        foreach ($rows as $row) {
            $wants[(int) $row->id] = (int) $row->wants;
            if ((int) $row->avoids > 0) {
                $vetoed[(int) $row->id] = true;
            }
        }

        return [$wants, $vetoed];
    }

    /**
     * @return array{0: array<string, array<string, int>>, 1: array<string, array<string, true>>}
     */
    private static function attributeProfile(Event $event): array
    {
        $rows = DB::table('event_vote_attribute')
            ->join('event_votes', 'event_votes.id', '=', 'event_vote_attribute.event_vote_id')
            ->where('event_votes.event_id', $event->id)
            ->groupBy('event_vote_attribute.type', 'event_vote_attribute.value')
            ->get([
                'event_vote_attribute.type as type',
                'event_vote_attribute.value as value',
                DB::raw("sum(case when event_vote_attribute.preference = 'want' then 1 else 0 end) as wants"),
                DB::raw("sum(case when event_vote_attribute.preference = 'avoid' then 1 else 0 end) as avoids"),
            ]);

        $wants = [];
        $vetoed = [];
        foreach ($rows as $row) {
            $wants[$row->type][$row->value] = (int) $row->wants;
            if ((int) $row->avoids > 0) {
                $vetoed[$row->type][$row->value] = true;
            }
        }

        return [$wants, $vetoed];
    }
}
