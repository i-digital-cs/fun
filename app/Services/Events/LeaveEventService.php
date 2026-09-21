<?php

namespace App\Services\Events;

use App\Enums\EventMemberRole;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveEventService
{
    public function leave(User $user, Event $event): void
    {
        DB::transaction(function () use ($user, $event) {
            $membership = EventMember::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($membership === null) {
                throw new DomainException(__('events.user_not_member'));
            }

            if ($membership->role === EventMemberRole::Owner) {
                throw new DomainException(__('events.owner_must_transfer_before_leave'));
            }

            $membership->delete();
        });
    }
}
