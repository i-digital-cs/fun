<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Exceptions\DomainException;
use App\Mail\JoinRequestReceivedMail;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use App\Services\Admin\SiteSettingsService;
use App\Services\Safety\UserBlockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RequestToJoinEventService
{
    public function __construct(
        private readonly UserBlockService $blocks,
        private readonly SiteSettingsService $siteSettings,
    ) {
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

        if (! $event->allowsUserSex($user)) {
            throw new DomainException(__('events.sex_mismatch'));
        }

        $creator = $event->creator()->first();

        if ($creator !== null && $this->blocks->isBlockedEitherWay($user, $creator)) {
            throw new DomainException(__('safety.blocked_cannot_join'));
        }

        $joinRequest = DB::transaction(function () use ($user, $event, $message) {
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

        $this->notifyOrganizer($user, $event, $joinRequest, $creator);

        return $joinRequest;
    }

    private function notifyOrganizer(
        User $requester,
        Event $event,
        EventJoinRequest $joinRequest,
        ?User $creator,
    ): void {
        if ($creator === null || (int) $creator->id === (int) $requester->id) {
            return;
        }

        if (! filled($creator->email)) {
            return;
        }

        try {
            Mail::to($creator->email)->send(new JoinRequestReceivedMail(
                organizer: $creator,
                requester: $requester,
                event: $event,
                joinRequest: $joinRequest,
                siteName: $this->siteSettings->displayName(),
                logoUrl: $this->siteSettings->logoUrl(),
            ));
        } catch (Throwable $exception) {
            Log::warning('join_request_notify_failed', [
                'event_id' => $event->id,
                'join_request_id' => $joinRequest->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
