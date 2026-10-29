<?php

namespace App\Services\Events\Chat;

use App\Enums\EventStatus;
use App\Events\EventMessagePosted;
use App\Events\UserChatMessageReceived;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\EventMessage;
use App\Models\User;
use App\Notifications\ChatMessagePushNotification;
use App\Services\Chat\ChatV2Capabilities;
use App\Services\Safety\UserBlockService;
use App\Support\ChatMessageSanitizer;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostEventMessageService
{
    public const MAX_LENGTH = 1000;

    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    /**
     * @param  array{client_uuid?: string|null, reply_to_id?: int|null}  $options
     */
    public function post(User $user, Event $event, string $rawBody, array $options = []): EventMessage
    {
        if (! $user->canParticipate()) {
            throw new DomainException(__('chat.not_allowed'));
        }

        if (! $event->isMember($user)) {
            throw new DomainException(__('chat.members_only'));
        }

        if (! in_array($event->status, [EventStatus::Published, EventStatus::Completed], true)) {
            throw new DomainException(__('chat.event_not_open'));
        }

        $creator = $event->creator()->first();

        if ($creator !== null && $this->blocks->isBlockedEitherWay($user, $creator)) {
            throw new DomainException(__('safety.blocked_cannot_chat'));
        }

        $body = ChatMessageSanitizer::clean($rawBody);

        if ($body === '') {
            throw new DomainException(__('chat.empty'));
        }

        if (mb_strlen($body) > self::MAX_LENGTH) {
            throw new DomainException(__('chat.too_long'));
        }

        $clientUuid = $this->normalizedClientUuid($options['client_uuid'] ?? null);
        $replyToId = isset($options['reply_to_id']) ? (int) $options['reply_to_id'] : null;

        if ($clientUuid !== null && ChatV2Capabilities::eventMessageExtensions()) {
            $existing = EventMessage::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->where('client_uuid', $clientUuid)
                ->first();
            if ($existing !== null) {
                return $existing->loadMissing('user:id,name,public_id,profile_image_path');
            }
        }

        $duplicateKey = sprintf(
            'chat:dup:%d:%d:%s',
            $event->id,
            $user->id,
            hash('sha256', mb_strtolower($body).($clientUuid ?? ''))
        );

        if (! Cache::add($duplicateKey, true, now()->addSeconds(10))) {
            throw new DomainException(__('chat.duplicate'));
        }

        if ($replyToId !== null) {
            $ok = EventMessage::query()
                ->where('event_id', $event->id)
                ->whereKey($replyToId)
                ->exists();
            if (! $ok) {
                $replyToId = null;
            }
        }

        $attrs = [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_system' => false,
        ];
        if (ChatV2Capabilities::eventMessageExtensions()) {
            $attrs['client_uuid'] = $clientUuid;
            $attrs['reply_to_id'] = $replyToId;
            $attrs['message_type'] = 'text';
        }

        $message = EventMessage::query()->create($attrs);

        $message->load('user:id,name,public_id,profile_image_path');

        try {
            broadcast(new EventMessagePosted($message))->toOthers();
        } catch (BroadcastException $exception) {
            Log::warning('Event chat broadcast failed.', [
                'event_id' => $event->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        $this->notifyOtherMembers($event, $message, $user);

        return $message;
    }

    private function notifyOtherMembers(Event $event, EventMessage $message, User $sender): void
    {
        $recipients = User::query()
            ->whereIn(
                'id',
                EventMember::query()
                    ->where('event_id', $event->id)
                    ->where('user_id', '!=', $sender->id)
                    ->select('user_id')
            )
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $chatUrl = event_route('events.chat', $event);
        $title = $event->title;

        foreach ($recipients as $recipient) {
            try {
                broadcast(new UserChatMessageReceived(
                    $message,
                    (int) $recipient->id,
                    $title,
                    $chatUrl,
                ));
            } catch (BroadcastException $exception) {
                Log::warning('User chat notification broadcast failed.', [
                    'event_id' => $event->id,
                    'message_id' => $message->id,
                    'recipient_id' => $recipient->id,
                    'error' => $exception->getMessage(),
                ]);
            }

            try {
                $recipient->notify(new ChatMessagePushNotification(
                    event: $event,
                    message: $message,
                    sender: $sender,
                ));
            } catch (Throwable $exception) {
                Log::warning('chat_message_fcm_failed', [
                    'event_id' => $event->id,
                    'message_id' => $message->id,
                    'recipient_id' => $recipient->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
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
}
