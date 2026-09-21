<?php

namespace App\Services\Admin;

use App\Exceptions\DomainException;
use App\Models\EventMessage;
use App\Models\User;

class DeleteEventMessageService
{
    public function __construct(private readonly AdminAuditLogger $audit)
    {
    }

    public function delete(User $actor, EventMessage $message): void
    {
        if ($actor->platform_role?->canModerateChat() !== true) {
            throw new DomainException(__('admin.unauthorized'));
        }

        $eventId = $message->event_id;
        $messageId = $message->id;
        $bodyPreview = mb_substr($message->body, 0, 120);

        $message->delete();

        $this->audit->log($actor, 'chat.message_deleted', EventMessage::class, $messageId, [
            'event_id' => $eventId,
            'body_preview' => $bodyPreview,
        ]);
    }
}
