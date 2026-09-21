<?php

namespace App\Services\Admin;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\User;

class ModerateEventService
{
    public function __construct(private readonly AdminAuditLogger $audit)
    {
    }

    public function cancel(User $actor, Event $event): Event
    {
        $this->assertCanModerate($actor);

        if ($event->status === EventStatus::Cancelled) {
            throw new DomainException(__('admin.event_already_cancelled'));
        }

        if ($event->status === EventStatus::Completed) {
            throw new DomainException(__('admin.event_already_completed'));
        }

        // Platform admins may cancel without being event managers.
        $previous = $event->status->value;
        $event->forceFill(['status' => EventStatus::Cancelled])->save();

        $this->audit->log($actor, 'event.cancelled', Event::class, $event->id, [
            'previous_status' => $previous,
        ]);

        return $event->refresh();
    }

    public function complete(User $actor, Event $event): Event
    {
        $this->assertCanModerate($actor);

        if ($event->status !== EventStatus::Published) {
            throw new DomainException(__('admin.event_not_published'));
        }

        $previous = $event->status->value;
        $event->forceFill(['status' => EventStatus::Completed])->save();

        $this->audit->log($actor, 'event.completed', Event::class, $event->id, [
            'previous_status' => $previous,
        ]);

        return $event->refresh();
    }

    private function assertCanModerate(User $actor): void
    {
        if ($actor->platform_role?->canModerateEvents() !== true) {
            throw new DomainException(__('admin.unauthorized'));
        }
    }
}
