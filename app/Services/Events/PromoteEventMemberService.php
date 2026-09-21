<?php

namespace App\Services\Events;

use App\Enums\EventMemberRole;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PromoteEventMemberService
{
    public function promoteToAdmin(User $actor, Event $event, User $target): EventMember
    {
        if (! $event->isOwnedBy($actor)) {
            throw new DomainException(__('events.only_owner_can_promote'));
        }

        return DB::transaction(function () use ($event, $target) {
            $member = EventMember::query()
                ->where('event_id', $event->id)
                ->where('user_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($member === null) {
                throw new DomainException(__('events.user_not_member'));
            }

            if ($member->role === EventMemberRole::Owner) {
                throw new DomainException(__('events.cannot_promote_owner'));
            }

            if ($member->role === EventMemberRole::Admin) {
                throw new DomainException(__('events.already_admin'));
            }

            $member->forceFill(['role' => EventMemberRole::Admin])->save();

            return $member->refresh();
        });
    }

    public function demoteToMember(User $actor, Event $event, User $target): EventMember
    {
        if (! $event->isOwnedBy($actor)) {
            throw new DomainException(__('events.only_owner_can_promote'));
        }

        return DB::transaction(function () use ($event, $target) {
            $member = EventMember::query()
                ->where('event_id', $event->id)
                ->where('user_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($member === null) {
                throw new DomainException(__('events.user_not_member'));
            }

            if ($member->role !== EventMemberRole::Admin) {
                throw new DomainException(__('events.not_admin'));
            }

            $member->forceFill(['role' => EventMemberRole::Member])->save();

            return $member->refresh();
        });
    }
}
