<?php

namespace App\Services\Events;

use App\Enums\EventMemberRole;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransferEventOwnershipService
{
    public function transfer(User $actor, Event $event, User $newOwner): Event
    {
        if (! $event->isOwnedBy($actor)) {
            throw new DomainException(__('events.only_owner_can_transfer'));
        }

        if ($actor->id === $newOwner->id) {
            throw new DomainException(__('events.cannot_transfer_to_self'));
        }

        return DB::transaction(function () use ($actor, $event, $newOwner) {
            $lockedEvent = Event::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();

            $currentOwnerMembership = EventMember::query()
                ->where('event_id', $lockedEvent->id)
                ->where('user_id', $actor->id)
                ->lockForUpdate()
                ->first();

            $newOwnerMembership = EventMember::query()
                ->where('event_id', $lockedEvent->id)
                ->where('user_id', $newOwner->id)
                ->lockForUpdate()
                ->first();

            if ($currentOwnerMembership === null || $currentOwnerMembership->role !== EventMemberRole::Owner) {
                throw new DomainException(__('events.only_owner_can_transfer'));
            }

            if ($newOwnerMembership === null) {
                throw new DomainException(__('events.user_not_member'));
            }

            $currentOwnerMembership->forceFill(['role' => EventMemberRole::Admin])->save();
            $newOwnerMembership->forceFill(['role' => EventMemberRole::Owner])->save();
            $lockedEvent->forceFill(['created_by' => $newOwner->id])->save();

            return $lockedEvent->refresh()->load('members');
        });
    }
}
