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
use App\Services\Safety\UserBlockService;
use App\Support\ChatMessageSanitizer;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PostEventMessageService
{
    public const MAX_LENGTH = 1000;

    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function post(User $user, Event $event, string $rawBody): EventMessage
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

        $duplicateKey = sprintf('chat:dup:%d:%d:%s', $event->id, $user->id, hash('sha256', mb_strtolower($body)));

        if (! Cache::add($duplicateKey, true, now()->addSeconds(10))) {
            throw new DomainException(__('chat.duplicate'));
        }

        $message = EventMessage::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'body' => $body,
            'is_system' => false,
        ]);

        $message->load('user:id,name,profile_image_path');

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
        $recipientIds = EventMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', '!=', $sender->id)
            ->pluck('user_id');

        if ($recipientIds->isEmpty()) {
            return;
        }

        $chatUrl = route('events.chat', $event);
        $title = $event->title;

        foreach ($recipientIds as $recipientId) {
            try {
                broadcast(new UserChatMessageReceived(
                    $message,
                    (int) $recipientId,
                    $title,
                    $chatUrl,
                ));
            } catch (BroadcastException $exception) {
                Log::warning('User chat notification broadcast failed.', [
                    'event_id' => $event->id,
                    'message_id' => $message->id,
                    'recipient_id' => $recipientId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
