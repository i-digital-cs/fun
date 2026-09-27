<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\User;
use Illuminate\Support\Collection;

class ListUserHomeParticipationsService
{
    /**
     * @return array{
     *     memberships: Collection<int, EventMember>,
     *     pendingRequests: Collection<int, EventJoinRequest>
     * }
     */
    public function forUser(User $user, int $limit = 12): array
    {
        $memberships = EventMember::query()
            ->where('user_id', $user->id)
            ->whereHas('event', function ($q) {
                $q->where('status', EventStatus::Published)
                    ->where(function ($inner) {
                        $inner->where('starts_at', '>=', now()->subDay())
                            ->orWhere(function ($stillOngoing) {
                                $stillOngoing->whereNotNull('ends_at')
                                    ->where('ends_at', '>=', now());
                            });
                    });
            })
            ->with([
                'event.category',
                'event.city',
                'event.creator:id,public_id,name,profile_image_path',
            ])
            ->join('events', 'events.id', '=', 'event_members.event_id')
            ->orderBy('events.starts_at')
            ->select('event_members.*')
            ->limit($limit)
            ->get();

        $pendingRequests = EventJoinRequest::query()
            ->where('user_id', $user->id)
            ->where('status', JoinRequestStatus::Pending)
            ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published))
            ->with([
                'event.category',
                'event.city',
                'event.creator:id,public_id,name,profile_image_path',
            ])
            ->latest()
            ->limit($limit)
            ->get();

        return [
            'memberships' => $memberships,
            'pendingRequests' => $pendingRequests,
        ];
    }
}
