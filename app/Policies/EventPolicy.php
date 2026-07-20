<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Only the creator or an attendee may view an event.
     */
    public function view(User $user, Event $event): bool
    {
        return $event->creator_id === $user->id
            || $event->attendees()->whereKey($user->id)->exists();
    }

    /**
     * Only the creator may delete an event.
     */
    public function delete(User $user, Event $event): bool
    {
        return $event->creator_id === $user->id;
    }

    /**
     * An attendee who is not the creator may leave.
     */
    public function leave(User $user, Event $event): bool
    {
        return $event->creator_id !== $user->id
            && $event->attendees()->whereKey($user->id)->exists();
    }
}
