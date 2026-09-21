<?php

namespace App\Broadcasting;

use App\Models\Event;
use App\Models\User;

class EventChatChannel
{
    public function join(User $user, int $eventId): bool
    {
        if (! $user->canParticipate()) {
            return false;
        }

        $event = Event::query()->find($eventId);

        if ($event === null) {
            return false;
        }

        return $event->isMember($user);
    }
}
