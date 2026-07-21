<?php

namespace App\Support;

use App\Enums\SwipeDecision;
use App\Models\Event;

/**
 * Reads the swipes cast on an event's shortlist and works out where the group
 * has landed: the best-ranked restaurant every attendee accepted (the match),
 * a "best so far" fallback while people are still swiping, and how far along
 * everyone is. Pure read — no side effects.
 */
class SwipeResult
{
    /**
     * @return array{
     *     tallies: array<int, array{accepts: int, rejects: int}>,
     *     matchId: int|null,
     *     leaderId: int|null,
     *     attendees: int,
     *     finished: int,
     * }
     */
    public static function for(Event $event): array
    {
        $attendeeIds = $event->attendees()->pluck('users.id')->all();
        $attendees = count($attendeeIds);

        // Shortlist in rank order (best match first).
        $restaurantIds = $event->restaurants()->pluck('restaurants.id')->all();

        $tallies = [];
        foreach ($restaurantIds as $id) {
            $tallies[$id] = ['accepts' => 0, 'rejects' => 0];
        }

        $swipes = $event->swipes()
            ->whereIn('user_id', $attendeeIds)
            ->get(['restaurant_id', 'user_id', 'decision']);

        foreach ($swipes as $swipe) {
            if (! isset($tallies[$swipe->restaurant_id])) {
                continue;
            }
            $key = $swipe->decision === SwipeDecision::Accept ? 'accepts' : 'rejects';
            $tallies[$swipe->restaurant_id][$key]++;
        }

        $matchId = null;
        $leaderId = null;
        foreach ($restaurantIds as $id) {
            $t = $tallies[$id];

            // Unanimous: everyone accepted, nobody rejected. First in rank wins.
            if ($matchId === null && $attendees > 0 && $t['accepts'] === $attendees && $t['rejects'] === 0) {
                $matchId = $id;
            }

            // Best so far: most accepts, then fewest rejects; ties keep rank order.
            if ($leaderId === null) {
                $leaderId = $id;
            } else {
                $best = $tallies[$leaderId];
                if ($t['accepts'] > $best['accepts']
                    || ($t['accepts'] === $best['accepts'] && $t['rejects'] < $best['rejects'])) {
                    $leaderId = $id;
                }
            }
        }

        // How many attendees have swiped the whole shortlist.
        $swipedPerUser = $swipes->groupBy('user_id');
        $finished = 0;
        $total = count($restaurantIds);
        foreach ($attendeeIds as $userId) {
            if ($total > 0 && ($swipedPerUser[$userId] ?? collect())->count() >= $total) {
                $finished++;
            }
        }

        // A leader only means something once someone has actually accepted it.
        if ($leaderId !== null && $tallies[$leaderId]['accepts'] === 0) {
            $leaderId = null;
        }

        return [
            'tallies' => $tallies,
            'matchId' => $matchId,
            'leaderId' => $leaderId,
            'attendees' => $attendees,
            'finished' => $finished,
        ];
    }
}
