<?php

namespace App\Services\Events\Chat;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\MessageReaction;
use App\Models\User;
use Illuminate\Support\Collection;

class ListMessageReactionsService
{
    /**
     * @return array{likes: list<array<string, mixed>>, dislikes: list<array<string, mixed>>}
     */
    public function list(User $user, Event $event, EventMessage $message): array
    {
        if (! $event->isMember($user)) {
            throw new DomainException(__('chat.members_only'));
        }

        if ((int) $message->event_id !== (int) $event->id) {
            throw new DomainException(__('chat.message_not_found'));
        }

        $reactions = MessageReaction::query()
            ->with(['user:id,name,public_id,profile_image_path'])
            ->where('message_id', $message->id)
            ->orderByDesc('id')
            ->get();

        $map = function (Collection $items): array {
            return $items->map(function (MessageReaction $reaction) {
                $reactor = $reaction->user;

                return [
                    'type' => $reaction->type,
                    'reacted_at' => $reaction->created_at?->toIso8601String(),
                    'user' => [
                        'id' => $reactor?->id,
                        'name' => $reactor?->name,
                        'public_id' => $reactor?->public_id,
                        'initials' => user_initials($reactor?->name),
                        'profile_image_path' => $reactor?->profile_image_path,
                        'profile_url' => filled($reactor?->public_id)
                            ? route('users.public.show', $reactor->public_id)
                            : null,
                    ],
                ];
            })->values()->all();
        };

        return [
            'likes' => $map($reactions->where('type', MessageReaction::TYPE_LIKE)),
            'dislikes' => $map($reactions->where('type', MessageReaction::TYPE_DISLIKE)),
        ];
    }
}
