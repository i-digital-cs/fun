<?php

namespace App\Services\Events\Chat;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListEventMessagesService
{
    public function list(User $user, Event $event, int $perPage = 50): LengthAwarePaginator
    {
        if (! $event->isMember($user)) {
            throw new DomainException(__('chat.members_only'));
        }

        return EventMessage::query()
            ->with(['user:id,name'])
            ->where('event_id', $event->id)
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
