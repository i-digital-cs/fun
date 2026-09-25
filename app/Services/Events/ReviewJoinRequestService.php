<?php

namespace App\Services\Events;

use App\Enums\EventMemberRole;
use App\Enums\JoinRequestStatus;
use App\Events\UserJoinAccepted;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\User;
use App\Notifications\JoinRequestAcceptedNotification;
use App\Services\Safety\UserBlockService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReviewJoinRequestService
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function accept(User $actor, EventJoinRequest $joinRequest): EventMember
    {
        return $this->review($actor, $joinRequest, JoinRequestStatus::Accepted);
    }

    public function reject(User $actor, EventJoinRequest $joinRequest): EventJoinRequest
    {
        return $this->review($actor, $joinRequest, JoinRequestStatus::Rejected);
    }

    private function review(
        User $actor,
        EventJoinRequest $joinRequest,
        JoinRequestStatus $decision,
    ): EventMember|EventJoinRequest {
        if (! $joinRequest->event->isManagedBy($actor)) {
            throw new DomainException(__('events.unauthorized'));
        }

        if (! $joinRequest->isPending()) {
            throw new DomainException(__('events.join_not_pending'));
        }

        $result = DB::transaction(function () use ($actor, $joinRequest, $decision) {
            $lockedEvent = Event::query()
                ->whereKey($joinRequest->event_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedRequest = EventJoinRequest::query()
                ->whereKey($joinRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== JoinRequestStatus::Pending) {
                throw new DomainException(__('events.join_not_pending'));
            }

            $applicant = $lockedRequest->user()->first();

            if (
                $decision === JoinRequestStatus::Accepted
                && $applicant !== null
                && $this->blocks->isBlockedEitherWay($actor, $applicant)
            ) {
                throw new DomainException(__('safety.blocked_cannot_accept'));
            }

            if ($decision === JoinRequestStatus::Accepted) {
                if ($lockedEvent->members()->where('user_id', $lockedRequest->user_id)->exists()) {
                    throw new DomainException(__('events.already_member'));
                }

                $membersCount = $lockedEvent->members()->lockForUpdate()->count();

                if ($membersCount >= $lockedEvent->max_participants) {
                    throw new DomainException(__('events.event_full'));
                }

                $member = EventMember::query()->create([
                    'event_id' => $lockedEvent->id,
                    'user_id' => $lockedRequest->user_id,
                    'role' => EventMemberRole::Member,
                ]);

                $lockedRequest->forceFill([
                    'status' => JoinRequestStatus::Accepted,
                    'reviewed_by' => $actor->id,
                    'reviewed_at' => now(),
                ])->save();

                return [
                    'result' => $member,
                    'event' => $lockedEvent->fresh(),
                    'applicant' => $applicant,
                ];
            }

            $lockedRequest->forceFill([
                'status' => JoinRequestStatus::Rejected,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ])->save();

            return [
                'result' => $lockedRequest->refresh(),
                'event' => $lockedEvent,
                'applicant' => null,
            ];
        });

        if ($decision === JoinRequestStatus::Accepted && $result['applicant'] instanceof User) {
            $this->notifyApplicantAccepted(
                applicant: $result['applicant'],
                event: $result['event'],
                accepter: $actor,
            );
        }

        return $result['result'];
    }

    private function notifyApplicantAccepted(User $applicant, Event $event, User $accepter): void
    {
        try {
            $applicant->notify(new JoinRequestAcceptedNotification(
                event: $event,
                accepter: $accepter,
            ));
        } catch (Throwable $exception) {
            Log::warning('join_accepted_notify_failed', [
                'event_id' => $event->id,
                'applicant_id' => $applicant->id,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            broadcast(new UserJoinAccepted(
                event: $event,
                applicant: $applicant,
                accepter: $accepter,
            ));
        } catch (BroadcastException $exception) {
            Log::warning('join_accepted_broadcast_failed', [
                'event_id' => $event->id,
                'applicant_id' => $applicant->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
