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

        return $user !== null && $event->isManagedBy($user);
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
}
