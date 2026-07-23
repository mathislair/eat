<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * These abilities express *rights* only — who may touch an event — and each
 * carries a human message surfaced on the 403 error page. Phase/state rules
 * (is voting still open? is it closed yet?) are intentionally *not* here: the
 * wrong-phase case is a friendlier redirect handled in the controllers, not a
 * hard denial.
 */
class EventPolicy
{
    /**
     * Only the creator or an attendee may view an event.
     */
    public function view(User $user, Event $event): Response
    {
        return $this->isMember($user, $event)
            ? Response::allow()
            : Response::deny("You're not part of this event.");
    }

    /**
     * Only the creator may delete an event.
     */
    public function delete(User $user, Event $event): Response
    {
        return $event->creator_id === $user->id
            ? Response::allow()
            : Response::deny('Only the host can delete this event.');
    }

    /**
     * An attendee who is not the creator may leave.
     */
    public function leave(User $user, Event $event): Response
    {
        if ($event->creator_id === $user->id) {
            return Response::deny("The host can't leave their own event.");
        }

        return $event->attendees()->whereKey($user->id)->exists()
            ? Response::allow()
            : Response::deny("You're not part of this event.");
    }

    /**
     * An attendee may cast a ballot. Whether the voting window is still open is
     * a phase check handled in the controller, not a right.
     */
    public function vote(User $user, Event $event): Response
    {
        return $event->attendees()->whereKey($user->id)->exists()
            ? Response::allow()
            : Response::deny("You're not part of this event.");
    }

    /**
     * Only the creator may validate (close) the event.
     */
    public function validate(User $user, Event $event): Response
    {
        return $event->creator_id === $user->id
            ? Response::allow()
            : Response::deny('Only the host can close the vote.');
    }

    private function isMember(User $user, Event $event): bool
    {
        return $event->creator_id === $user->id
            || $event->attendees()->whereKey($user->id)->exists();
    }
}
