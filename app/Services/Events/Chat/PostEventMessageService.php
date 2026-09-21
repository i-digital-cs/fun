<?php

namespace App\Services\Events\Chat;

use App\Enums\EventStatus;
use App\Events\EventMessagePosted;
use App\Exceptions\DomainException;
use App\Models\Event;
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

        $message->load('user:id,name');

        try {
            broadcast(new EventMessagePosted($message))->toOthers();
        } catch (BroadcastException $exception) {
            Log::warning('Event chat broadcast failed.', [
                'event_id' => $event->id,
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $message;
    }
}
