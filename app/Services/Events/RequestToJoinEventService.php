<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use App\Services\Safety\UserBlockService;
use Illuminate\Support\Facades\DB;

class RequestToJoinEventService
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function request(User $user, Event $event, ?string $message = null): EventJoinRequest
    {
        if (! $user->canParticipate()) {
            throw new DomainException(__('auth.pending_documents'));
        }

        if ($event->status !== EventStatus::Published) {
            throw new DomainException(__('events.join_not_open'));
        }

        if ($event->isMember($user)) {
            throw new DomainException(__('events.already_member'));
        }

        if ($event->isFull()) {
            throw new DomainException(__('events.event_full'));
        }

        $creator = $event->creator()->first();

        if ($creator !== null && $this->blocks->isBlockedEitherWay($user, $creator)) {
            throw new DomainException(__('safety.blocked_cannot_join'));
        }

        return DB::transaction(function () use ($user, $event, $message) {
            $existing = EventJoinRequest::query()
                ->where('event_id', $event->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($existing?->status === JoinRequestStatus::Pending) {
                throw new DomainException(__('events.join_already_pending'));
            }

            if ($existing) {
                $existing->forceFill([
                    'status' => JoinRequestStatus::Pending,
                    'message' => $message,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ])->save();

                return $existing->refresh();
            }

            return EventJoinRequest::query()->create([
                'event_id' => $event->id,
                'user_id' => $user->id,
                'status' => JoinRequestStatus::Pending,
                'message' => $message,
            ]);
        });
    }
}
