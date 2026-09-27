<?php

namespace App\Services\Events;

use App\Enums\EventJoinAction;
use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\User;

class ResolveEventJoinActionService
{
    public function resolve(?User $user, Event $event): EventJoinAction
    {
        if ($user !== null && (int) $event->created_by === (int) $user->id) {
            return EventJoinAction::Owned;
        }

        if ($event->status !== EventStatus::Published) {
            return EventJoinAction::NotAvailable;
        }

        if ($user === null) {
            return $event->isFull() ? EventJoinAction::EventFull : EventJoinAction::RequestToJoin;
        }

        if ($event->isMember($user)) {
            return EventJoinAction::Joined;
        }

        $request = EventJoinRequest::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->first();

        if ($request?->status === JoinRequestStatus::Pending) {
            return EventJoinAction::RequestPending;
        }

        if ($request?->status === JoinRequestStatus::Rejected) {
            return $event->isFull()
                ? EventJoinAction::EventFull
                : EventJoinAction::RequestRejected;
        }

        if ($event->isFull()) {
            return EventJoinAction::EventFull;
        }

        if (! $event->allowsUserSex($user)) {
            return EventJoinAction::SexMismatch;
        }

        return EventJoinAction::RequestToJoin;
    }

    /**
     * @param  iterable<Event>  $events
     * @return array<int, EventJoinAction>
     */
    public function resolveMany(?User $user, iterable $events): array
    {
        $events = collect($events)->values();
        $actions = [];

        if ($events->isEmpty()) {
            return $actions;
        }

        $memberIds = [];
        $requestMap = [];

        if ($user !== null) {
            $eventIds = $events->pluck('id')->all();

            $memberIds = EventMember::query()
                ->whereIn('event_id', $eventIds)
                ->where('user_id', $user->id)
                ->pluck('event_id')
                ->all();

            $requestMap = EventJoinRequest::query()
                ->whereIn('event_id', $eventIds)
                ->where('user_id', $user->id)
                ->get()
                ->keyBy('event_id');
        }

        foreach ($events as $event) {
            if ($user !== null && (int) $event->created_by === (int) $user->id) {
                $actions[$event->id] = EventJoinAction::Owned;
                continue;
            }

            if ($event->status !== EventStatus::Published) {
                $actions[$event->id] = EventJoinAction::NotAvailable;
                continue;
            }

            if ($user === null) {
                $actions[$event->id] = $event->isFull()
                    ? EventJoinAction::EventFull
                    : EventJoinAction::RequestToJoin;
                continue;
            }

            if (in_array($event->id, $memberIds, true)) {
                $actions[$event->id] = EventJoinAction::Joined;
                continue;
            }

            $request = $requestMap[$event->id] ?? null;

            if ($request?->status === JoinRequestStatus::Pending) {
                $actions[$event->id] = EventJoinAction::RequestPending;
                continue;
            }

            if ($request?->status === JoinRequestStatus::Rejected) {
                $actions[$event->id] = $event->isFull()
                    ? EventJoinAction::EventFull
                    : EventJoinAction::RequestRejected;
                continue;
            }

            if ($event->isFull()) {
                $actions[$event->id] = EventJoinAction::EventFull;
                continue;
            }

            $actions[$event->id] = $event->allowsUserSex($user)
                ? EventJoinAction::RequestToJoin
                : EventJoinAction::SexMismatch;
        }

        return $actions;
    }
}
