<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionEventStatusService
{
    public function publish(User $actor, Event $event): Event
    {
        return $this->transition($actor, $event, EventStatus::Published);
    }

    public function cancel(User $actor, Event $event): Event
    {
        return $this->transition($actor, $event, EventStatus::Cancelled);
    }

    public function complete(User $actor, Event $event): Event
    {
        return $this->transition($actor, $event, EventStatus::Completed);
    }

    private function transition(User $actor, Event $event, EventStatus $target): Event
    {
        if (! $event->isManagedBy($actor)) {
            throw new DomainException(__('events.unauthorized'));
        }

        if (! $event->status->canTransitionTo($target)) {
            throw new DomainException(__('events.invalid_status_transition'));
        }

        if ($target === EventStatus::Published) {
            $event->loadMissing('category', 'city');

            if (! $event->category?->is_active) {
                throw new DomainException(__('events.category_unavailable'));
            }

            if (! $event->city?->is_active) {
                throw new DomainException(__('events.city_unavailable'));
            }
        }

        return DB::transaction(function () use ($event, $target) {
            $locked = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $locked->status->canTransitionTo($target)) {
                throw new DomainException(__('events.invalid_status_transition'));
            }

            $locked->forceFill(['status' => $target])->save();

            return $locked->refresh()->load(['category', 'city', 'members']);
        });
    }
}
