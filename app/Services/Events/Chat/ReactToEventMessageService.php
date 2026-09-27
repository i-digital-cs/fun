<?php

namespace App\Services\Events\Chat;

use App\Events\EventMessageReacted;
use App\Events\UserMessageReacted;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMessage;
use App\Models\MessageReaction;
use App\Models\User;
use App\Notifications\MessageReactedNotification;

class ReactToEventMessageService
{
    /**
     * @return array{message: EventMessage, my_reaction: string|null, likes_count: int, dislikes_count: int}
     */
    public function react(User $user, Event $event, EventMessage $message, ?string $type): array
    {
        if (! $event->isMember($user)) {
            throw new DomainException(__('chat.members_only'));
        }

        if ((int) $message->event_id !== (int) $event->id) {
            throw new DomainException(__('chat.message_not_found'));
        }

        if ($message->is_system) {
            throw new DomainException(__('chat.cannot_react_system'));
        }

        $normalized = $type !== null ? strtolower(trim($type)) : null;
        if ($normalized !== null && ! in_array($normalized, [MessageReaction::TYPE_LIKE, MessageReaction::TYPE_DISLIKE], true)) {
            throw new DomainException(__('chat.invalid_reaction'));
        }

        $existing = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        $previousType = $existing?->type;

        if ($normalized === null || ($existing !== null && $existing->type === $normalized)) {
            $existing?->delete();
            $myReaction = null;
        } elseif ($existing !== null) {
            $existing->update(['type' => $normalized]);
            $myReaction = $normalized;
        } else {
            MessageReaction::query()->create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'type' => $normalized,
            ]);
            $myReaction = $normalized;
        }

        $likes = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('type', MessageReaction::TYPE_LIKE)
            ->count();
        $dislikes = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('type', MessageReaction::TYPE_DISLIKE)
            ->count();

        $message->setAttribute('likes_count', $likes);
        $message->setAttribute('dislikes_count', $dislikes);
        $message->setAttribute('my_reaction', $myReaction);

        event(new EventMessageReacted(
            message: $message,
            actorId: (int) $user->id,
            myReaction: $myReaction,
            likesCount: $likes,
            dislikesCount: $dislikes,
        ));

        if (
            $myReaction !== null
            && $myReaction !== $previousType
            && (int) $message->user_id !== (int) $user->id
            && $message->user_id !== null
        ) {
            $this->notifyOwner($event, $message, $user, $myReaction);
        }

        return [
            'message' => $message,
            'my_reaction' => $myReaction,
            'likes_count' => $likes,
            'dislikes_count' => $dislikes,
        ];
    }

    private function notifyOwner(Event $event, EventMessage $message, User $actor, string $reaction): void
    {
        $owner = $message->relationLoaded('user')
            ? $message->user
            : User::query()->find($message->user_id);

        if ($owner === null || (int) $owner->id === (int) $actor->id) {
            return;
        }

        $owner->notify(new MessageReactedNotification($event, $message, $actor, $reaction));
        broadcast(new UserMessageReacted($event, $message, $owner, $actor, $reaction));
    }
}
