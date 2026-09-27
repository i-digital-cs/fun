<?php

namespace App\Services\Events\Chat;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\MessageReaction;
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

        $messages = EventMessage::query()
            ->with(['user:id,name,public_id,profile_image_path'])
            ->withCount([
                'reactions as likes_count' => fn ($q) => $q->where('type', MessageReaction::TYPE_LIKE),
                'reactions as dislikes_count' => fn ($q) => $q->where('type', MessageReaction::TYPE_DISLIKE),
            ])
            ->where('event_id', $event->id)
            ->when($blockedIds !== [], fn ($q) => $q->whereNotIn('user_id', $blockedIds))
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100));

        $ids = $messages->getCollection()->pluck('id')->all();
        $mine = [];
        if ($ids !== []) {
            $mine = MessageReaction::query()
                ->where('user_id', $user->id)
                ->whereIn('message_id', $ids)
                ->pluck('type', 'message_id')
                ->all();
        }

        $messages->getCollection()->transform(function (EventMessage $message) use ($mine) {
            $message->setAttribute('my_reaction', $mine[$message->id] ?? null);

            return $message;
        });

        return $messages;
    }
}
