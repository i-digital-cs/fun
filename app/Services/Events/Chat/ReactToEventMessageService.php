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
     * @return array{
     *     message: EventMessage,
     *     my_reaction: string|null,
     *     likes_count: int,
     *     dislikes_count: int,
     *     emoji_counts: array<string, int>,
     *     my_emojis: list<string>
     * }
     */
    public function react(User $user, Event $event, EventMessage $message, ?string $type, ?string $emoji = null): array
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

        $emoji = $emoji !== null ? trim($emoji) : null;
        if ($emoji !== null && $emoji !== '') {
            if (! in_array($emoji, MessageReaction::ALLOWED_EMOJI, true)) {
                throw new DomainException(__('chat.invalid_reaction'));
            }
            $normalized = MessageReaction::TYPE_EMOJI;
        } else {
            $normalized = $type !== null ? strtolower(trim($type)) : null;
            if ($normalized !== null && ! in_array($normalized, [MessageReaction::TYPE_LIKE, MessageReaction::TYPE_DISLIKE], true)) {
                throw new DomainException(__('chat.invalid_reaction'));
            }
        }

        $existing = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        $previousType = $existing?->type;
        $previousEmoji = $existing?->emoji;

        if ($normalized === null) {
            $existing?->delete();
            $myReaction = null;
            $myEmoji = null;
        } elseif (
            $existing !== null
            && $existing->type === $normalized
            && (($emoji === null && blank($existing->emoji)) || $existing->emoji === $emoji)
        ) {
            // Toggle off same reaction.
            $existing->delete();
            $myReaction = null;
            $myEmoji = null;
        } elseif ($existing !== null) {
            $existing->update([
                'type' => $normalized,
                'emoji' => $normalized === MessageReaction::TYPE_EMOJI ? $emoji : null,
            ]);
            $myReaction = $normalized === MessageReaction::TYPE_EMOJI ? $emoji : $normalized;
            $myEmoji = $normalized === MessageReaction::TYPE_EMOJI ? $emoji : null;
        } else {
            MessageReaction::query()->create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'type' => $normalized,
                'emoji' => $normalized === MessageReaction::TYPE_EMOJI ? $emoji : null,
            ]);
            $myReaction = $normalized === MessageReaction::TYPE_EMOJI ? $emoji : $normalized;
            $myEmoji = $normalized === MessageReaction::TYPE_EMOJI ? $emoji : null;
        }

        $likes = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('type', MessageReaction::TYPE_LIKE)
            ->count();
        $dislikes = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('type', MessageReaction::TYPE_DISLIKE)
            ->count();

        $emojiCounts = MessageReaction::query()
            ->selectRaw('emoji, COUNT(*) as c')
            ->where('message_id', $message->id)
            ->where('type', MessageReaction::TYPE_EMOJI)
            ->whereNotNull('emoji')
            ->groupBy('emoji')
            ->pluck('c', 'emoji')
            ->map(fn ($c) => (int) $c)
            ->all();

        $myEmojis = $myEmoji !== null ? [$myEmoji] : [];

        $message->setAttribute('likes_count', $likes);
        $message->setAttribute('dislikes_count', $dislikes);
        $message->setAttribute('my_reaction', $myReaction);
        $message->setAttribute('emoji_counts', $emojiCounts);
        $message->setAttribute('my_emojis', $myEmojis);

        event(new EventMessageReacted(
            message: $message,
            actorId: (int) $user->id,
            myReaction: is_string($myReaction) ? $myReaction : null,
            likesCount: $likes,
            dislikesCount: $dislikes,
        ));

        if (
            $myReaction !== null
            && ($myReaction !== $previousType && $myReaction !== $previousEmoji)
            && (int) $message->user_id !== (int) $user->id
            && $message->user_id !== null
        ) {
            $this->notifyOwner($event, $message, $user, is_string($myReaction) ? $myReaction : 'emoji');
        }

        return [
            'message' => $message,
            'my_reaction' => $myReaction,
            'likes_count' => $likes,
            'dislikes_count' => $dislikes,
            'emoji_counts' => $emojiCounts,
            'my_emojis' => $myEmojis,
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
