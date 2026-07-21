<?php

namespace App\Support;

use App\Enums\AttributeType;
use App\Models\Event;
use App\Models\EventVote;
use App\Models\User;

/**
 * A user's standing food profile — the cuisines and criteria they've said they
 * want (🟢) or avoid (🔴), independent of any single event.
 *
 * It reads out in exactly the ballot's wire shape so it can pre-fill the vote
 * form, and it can be stamped onto an event as a real ballot so a member's
 * tastes still shape the group's decision even when they never voted.
 */
class UserTaste
{
    /**
     * Whether the user has expressed any preference at all.
     */
    public static function isNotEmpty(User $user): bool
    {
        return $user->nationalityPreferences()->exists()
            || $user->attributePreferences()->exists();
    }

    /**
     * Cuisine tastes as a map of nationality id → preference value
     * (e.g. [3 => 'want', 5 => 'avoid']).
     *
     * @return array<int, string>
     */
    public static function nationalities(User $user): array
    {
        return $user->nationalityPreferences()
            ->get()
            ->mapWithKeys(fn ($n) => [$n->id => $n->pivot->preference])
            ->all();
    }

    /**
     * Criteria tastes grouped by type as a map of value → preference
     * (e.g. ['price' => ['€€' => 'want'], 'diet' => (object) [], ...]). Every
     * type is always present so the front-end sees an object per family.
     *
     * @return array<string, object|array<string, string>>
     */
    public static function criteria(User $user): array
    {
        $grouped = [];
        foreach (AttributeType::cases() as $type) {
            $grouped[$type->value] = (object) [];
        }

        foreach ($user->attributePreferences as $preference) {
            $type = $preference->type->value;
            $grouped[$type] = (array) $grouped[$type];
            $grouped[$type][$preference->value] = $preference->preference->value;
        }

        return $grouped;
    }

    /**
     * Stamp every attendee's standing tastes onto the event as a ballot, but
     * only for those who never voted and actually have preferences. Lets a
     * taste profile count toward the outcome without an explicit vote.
     */
    public static function seedMissingBallots(Event $event): void
    {
        $voted = $event->votes()->pluck('user_id')->all();

        $event->attendees()
            ->whereNotIn('users.id', $voted)
            ->get()
            ->each(function (User $user) use ($event): void {
                if (static::isNotEmpty($user)) {
                    static::seedBallot($event, $user);
                }
            });
    }

    /**
     * Persist a user's standing tastes as their ballot on an event.
     */
    public static function seedBallot(Event $event, User $user): void
    {
        $ballot = EventVote::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);

        $ballot->nationalities()->sync(
            collect(static::nationalities($user))
                ->mapWithKeys(fn (string $preference, $id) => [(int) $id => ['preference' => $preference]])
                ->all()
        );

        foreach ($user->attributePreferences as $preference) {
            $ballot->criteria()->create([
                'type' => $preference->type->value,
                'value' => $preference->value,
                'preference' => $preference->preference->value,
            ]);
        }
    }
}
