<?php

namespace App\Policies;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Event $event): bool
    {
        if ($event->status === EventStatus::Published) {
            return true;
        }

        if ($event->status === EventStatus::Completed) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        // Platform moderators may inspect drafts/cancelled for management.
        // Guests and unrelated members still cannot see unpublished events.
        if ($user->platform_role?->canModerateEvents() === true) {
            return true;
        }

        return $event->isManagedBy($user);
    }

    public function create(User $user): bool
    {
        return $user->canParticipate();
    }

    public function update(User $user, Event $event): bool
    {
        return $event->isManagedBy($user)
            && ! in_array($event->status, [EventStatus::Cancelled, EventStatus::Completed], true);
    }

    public function publish(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function cancel(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function complete(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function join(User $user, Event $event): bool
    {
        return $user->canParticipate() && $event->status === EventStatus::Published;
    }

    public function reviewJoinRequests(User $user, Event $event): bool
    {
        return $event->isManagedBy($user);
    }

    public function manageMembers(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }

    public function leave(User $user, Event $event): bool
    {
        return $event->isMember($user);
    }

    public function cancelJoinRequest(User $user, Event $event): bool
    {
        return $user->canParticipate();
    }

    public function chat(User $user, Event $event): bool
    {
        return $user->canParticipate()
            && $event->isMember($user)
            && in_array($event->status, [EventStatus::Published, EventStatus::Completed], true);
    }

    public function viewTicket(User $user, Event $event): bool
    {
        return $event->isMember($user);
    }

    public function rate(User $user, Event $event): bool
    {
        return $user->canParticipate()
            && $event->isReadyForRatings()
            && $event->isMember($user)
            && (int) $event->created_by !== (int) $user->id;
    }

    public function moderate(User $user, Event $event): bool
    {
        return $user->platform_role?->canModerateEvents() === true;
    }
}
