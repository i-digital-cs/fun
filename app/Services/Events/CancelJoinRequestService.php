<?php

namespace App\Services\Events;

use App\Enums\JoinRequestStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelJoinRequestService
{
    public function cancel(User $user, Event $event): void
    {
        DB::transaction(function () use ($user, $event) {
            $request = EventJoinRequest::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($request === null || $request->status !== JoinRequestStatus::Pending) {
                throw new DomainException(__('events.join_request_not_pending'));
            }

            $request->forceFill([
                'status' => JoinRequestStatus::Cancelled,
                'reviewed_by' => null,
                'reviewed_at' => now(),
            ])->save();
        });
    }
}
