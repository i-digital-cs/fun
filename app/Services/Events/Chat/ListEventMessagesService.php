<?php

namespace App\Services\Events\Chat;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\User;
use App\Services\Safety\UserBlockService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListEventMessagesService
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function list(User $user, Event $event, int $perPage = 50): LengthAwarePaginator
    {
        if (! $event->isMember($user)) {
            throw new DomainException(__('chat.members_only'));
        }

        $blockedIds = $this->blocks->blockedUserIdsFor($user);

        return EventMessage::query()
            ->with(['user:id,name,public_id,profile_image_path'])
            ->where('event_id', $event->id)
            ->when($blockedIds !== [], fn ($q) => $q->whereNotIn('user_id', $blockedIds))
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100));
    }
}
