<?php

namespace App\Services\Events\Chat;

use App\Models\Event;
use App\Models\EventChatRead;
use App\Models\EventMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkEventChatReadService
{
    public function mark(User $user, Event $event): EventChatRead
    {
        $latestId = (int) EventMessage::query()
            ->where('event_id', $event->id)
            ->max('id');

        return EventChatRead::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'event_id' => $event->id,
            ],
            [
                'last_read_message_id' => $latestId,
                'last_read_at' => now(),
            ]
        );
    }
}
