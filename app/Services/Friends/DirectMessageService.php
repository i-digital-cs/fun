<?php

namespace App\Services\Friends;

use App\Events\DirectMessagePosted;
use App\Events\UserDirectMessageReceived;
use App\Exceptions\DomainException;
use App\Models\DirectConversation;
use App\Models\DirectConversationRead;
use App\Models\DirectMessage;
use App\Models\User;
use App\Notifications\DirectMessagePushNotification;
use App\Services\Safety\UserBlockService;
use App\Support\ChatMessageSanitizer;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DirectMessageService
{
    public const MAX_LENGTH = 1000;

    public function __construct(
        private readonly FriendshipService $friendships,
        private readonly UserBlockService $blocks,
    ) {
    }

    public function assertCanMessage(User $actor, User $other): void
    {
        if (! $actor->canParticipate() || ! $other->canParticipate()) {
            throw new DomainException(__('friends.dm_not_allowed'));
        }

        if ($this->blocks->isBlockedEitherWay($actor, $other)) {
            throw new DomainException(__('friends.blocked'));
        }

        if (! $this->friendships->areFriends($actor, $other)) {
            throw new DomainException(__('friends.dm_requires_friendship'));
        }
    }

    public function assertParticipant(User $user, DirectConversation $conversation): void
    {
        if (! $conversation->involves((int) $user->id)) {
            throw new DomainException(__('friends.dm_forbidden'));
        }

        if (! $user->canParticipate()) {
            throw new DomainException(__('friends.dm_not_allowed'));
        }

        $otherId = $conversation->otherUserId((int) $user->id);
        $other = User::query()->find($otherId);

        if ($other === null || $other->isDeactivated()) {
            throw new DomainException(__('friends.user_unavailable'));
        }

        $this->assertCanMessage($user, $other);
    }

    /**
     * Create or get conversation and post first/next message.
     * Conversation is created on first message (concurrent-safe via unique pair).
     *
     * @param  array{client_uuid?: string|null, reply_to_id?: int|null}  $options
     */
    public function send(User $sender, User $recipient, string $rawBody, array $options = []): DirectMessage
    {
        $this->assertCanMessage($sender, $recipient);

        $body = ChatMessageSanitizer::clean($rawBody);

        if ($body === '') {
            throw new DomainException(__('chat.empty'));
        }

        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new DomainException(__('chat.too_long'));
        }

        $clientUuid = $this->normalizedClientUuid($options['client_uuid'] ?? null);
        $replyToId = isset($options['reply_to_id']) ? (int) $options['reply_to_id'] : null;

        [$low, $high] = DirectConversation::orderedPair((int) $sender->id, (int) $recipient->id);

        if ($clientUuid !== null && \App\Services\Chat\ChatV2Capabilities::directMessageExtensions()) {
            $existing = DirectMessage::query()
                ->where('user_id', $sender->id)
                ->where('client_uuid', $clientUuid)
                ->whereHas('conversation', fn ($q) => $q->where('user_low_id', $low)->where('user_high_id', $high))
                ->first();
            if ($existing !== null) {
                return $existing->loadMissing('user:id,name,public_id,profile_image_path');
            }
        }

        $duplicateKey = sprintf(
            'dm:dup:%d:%d:%s',
            $low,
            $sender->id,
            hash('sha256', mb_strtolower($body).($clientUuid ?? ''))
        );

        if (! Cache::add($duplicateKey, true, now()->addSeconds(10))) {
            throw new DomainException(__('chat.duplicate'));
        }

        $message = DB::transaction(function () use ($sender, $low, $high, $body, $clientUuid, $replyToId) {
            $conversation = DirectConversation::query()->firstOrCreate(
                [
                    'user_low_id' => $low,
                    'user_high_id' => $high,
                ],
                [
                    'last_message_at' => now(),
                ]
            );

            if ($replyToId !== null) {
                $ok = DirectMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->whereKey($replyToId)
                    ->exists();
                if (! $ok) {
                    $replyToId = null;
                }
            }

            $attrs = [
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'body' => $body,
            ];
            if (\App\Services\Chat\ChatV2Capabilities::directMessageExtensions()) {
                $attrs['client_uuid'] = $clientUuid;
                $attrs['reply_to_id'] = $replyToId;
                $attrs['message_type'] = 'text';
            }

            $message = DirectMessage::query()->create($attrs);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return $message->load('user:id,name,public_id,profile_image_path');
        });

        $conversation = $message->conversation()->first();

        try {
            broadcast(new DirectMessagePosted($message))->toOthers();
        } catch (BroadcastException $exception) {
            Log::warning('DM broadcast failed.', [
                'conversation_id' => $conversation?->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->notifyRecipient($message, $sender, $recipient, $conversation);

        return $message;
    }

    /**
     * Post to an existing conversation (recipient derived from pair).
     *
     * @param  array{client_uuid?: string|null, reply_to_id?: int|null}  $options
     */
    public function sendToConversation(
        User $sender,
        DirectConversation $conversation,
        string $rawBody,
        array $options = [],
    ): DirectMessage {
        $this->assertParticipant($sender, $conversation);

        $otherId = $conversation->otherUserId((int) $sender->id);
        $recipient = User::query()->findOrFail($otherId);

        return $this->send($sender, $recipient, $rawBody, $options);
    }

    private function normalizedClientUuid(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || strlen($value) > 36) {
            return null;
        }
        if (! preg_match('/^[A-Za-z0-9_-]{8,36}$/', $value)) {
            return null;
        }

        return $value;
    }

    /**
     * Open or create conversation with a friend (without sending). Used for UI navigation.
     */
    public function findOrCreateWith(User $actor, User $other): DirectConversation
    {
        $this->assertCanMessage($actor, $other);

        [$low, $high] = DirectConversation::orderedPair((int) $actor->id, (int) $other->id);

        return DirectConversation::query()->firstOrCreate([
            'user_low_id' => $low,
            'user_high_id' => $high,
        ]);
    }

    public function listMessages(User $user, DirectConversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        $this->assertParticipant($user, $conversation);

        return DirectMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with('user:id,name,public_id,profile_image_path')
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }

    public function markRead(User $user, DirectConversation $conversation): DirectConversationRead
    {
        $this->assertParticipant($user, $conversation);

        $lastId = DirectMessage::query()
            ->where('conversation_id', $conversation->id)
            ->max('id');

        return DirectConversationRead::query()->updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => $lastId,
            ]
        );
    }

    /**
     * Inbox rows for the user.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listConversations(User $user): Collection
    {
        $conversations = DirectConversation::query()
            ->where(function ($q) use ($user) {
                $q->where('user_low_id', $user->id)
                    ->orWhere('user_high_id', $user->id);
            })
            ->whereNotNull('last_message_at')
            ->with(['userLow:id,public_id,name,profile_image_path,status', 'userHigh:id,public_id,name,profile_image_path,status'])
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();

        if ($conversations->isEmpty()) {
            return collect();
        }

        $blockedIds = $this->blocks->blockedUserIdsFor($user);
        $friendIds = array_flip($this->friendships->acceptedFriendIds($user));
        $conversationIds = $conversations->pluck('id')->all();

        $reads = DirectConversationRead::query()
            ->where('user_id', $user->id)
            ->whereIn('conversation_id', $conversationIds)
            ->get()
            ->keyBy('conversation_id');

        $lastMessageIds = DirectMessage::query()
            ->selectRaw('MAX(id) as id')
            ->whereIn('conversation_id', $conversationIds)
            ->groupBy('conversation_id')
            ->pluck('id');

        $lastByConversation = DirectMessage::query()
            ->whereIn('id', $lastMessageIds)
            ->with('user:id,name')
            ->get()
            ->keyBy('conversation_id');

        $unreadByConversation = DirectMessage::query()
            ->selectRaw('direct_messages.conversation_id, COUNT(*) as unread_count')
            ->leftJoin('direct_conversation_reads as reads', function ($join) use ($user) {
                $join->on('reads.conversation_id', '=', 'direct_messages.conversation_id')
                    ->where('reads.user_id', '=', $user->id);
            })
            ->whereIn('direct_messages.conversation_id', $conversationIds)
            ->where('direct_messages.user_id', '!=', $user->id)
            ->whereRaw('direct_messages.id > COALESCE(reads.last_read_message_id, 0)')
            ->groupBy('direct_messages.conversation_id')
            ->pluck('unread_count', 'conversation_id');

        return $conversations->map(function (DirectConversation $c) use ($user, $blockedIds, $friendIds, $lastByConversation, $unreadByConversation) {
            $otherId = $c->otherUserId((int) $user->id);

            if (in_array($otherId, $blockedIds, true)) {
                return null;
            }

            if (! isset($friendIds[$otherId])) {
                return null;
            }

            $other = (int) $c->user_low_id === (int) $user->id ? $c->userHigh : $c->userLow;

            if ($other === null || $other->isDeactivated()) {
                return null;
            }

            $last = $lastByConversation->get($c->id);
            $unread = (int) ($unreadByConversation[$c->id] ?? 0);

            return [
                'id' => $c->id,
                'other_user' => [
                    'id' => $other->id,
                    'public_id' => $other->public_id,
                    'name' => $other->name,
                    'initials' => user_initials($other->name),
                    'profile_image_path' => $other->profile_image_path,
                    'profile_url' => $other->publicProfileUrl(),
                ],
                'last_message' => $last ? [
                    'id' => $last->id,
                    'body' => $last->body,
                    'user_id' => $last->user_id,
                    'user_name' => $last->user?->name,
                    'created_at' => $last->created_at?->toIso8601String(),
                ] : null,
                'last_message_at' => $c->last_message_at?->toIso8601String(),
                'unread_count' => $unread,
                'url' => route('dms.show', $c),
                'api_path' => '/dms/'.$c->id.'/messages',
            ];
        })->filter()->values();
    }

    private function notifyRecipient(
        DirectMessage $message,
        User $sender,
        User $recipient,
        ?DirectConversation $conversation,
    ): void {
        if ($conversation === null) {
            return;
        }

        $chatUrl = route('dms.show', $conversation);

        try {
            broadcast(new UserDirectMessageReceived(
                $message,
                (int) $recipient->id,
                $sender->name,
                $chatUrl,
            ));
        } catch (BroadcastException $exception) {
            Log::warning('User DM notification broadcast failed.', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $recipient->notify(new DirectMessagePushNotification(
                conversation: $conversation,
                message: $message,
                sender: $sender,
            ));
        } catch (Throwable $exception) {
            Log::warning('dm_message_fcm_failed', [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
                'recipient_id' => $recipient->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
