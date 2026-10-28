<?php

namespace App\Services\Friends;

use App\Enums\AccountStatus;
use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Profile\PublicProfileService;
use App\Services\Safety\UserBlockService;
use Illuminate\Support\Collection;

/**
 * Homepage “People You May Like to Meet” suggestions.
 *
 * v1 ranking: same city peers who can participate. No fabricated compatibility scores.
 * Shared interests are omitted until a real interests schema exists.
 */
class PeopleDiscoveryService
{
    public function __construct(
        private readonly FriendshipService $friendships,
        private readonly UserBlockService $blocks,
        private readonly PublicProfileService $profiles,
    ) {}

    /**
     * @return list<array{user: array<string, mixed>, friendship: array<string, mixed>}>
     */
    public function suggest(User $viewer, int $limit = 8): array
    {
        $limit = max(1, min(24, $limit));

        $excludeIds = array_values(array_unique(array_merge(
            [(int) $viewer->id],
            $this->blocks->blockedUserIdsFor($viewer),
            $this->friendships->acceptedFriendIds($viewer),
            $this->relatedPendingIds($viewer),
        )));

        $query = User::query()
            ->where('status', AccountStatus::Active)
            ->whereNotNull('public_id')
            ->whereNotIn('id', $excludeIds)
            ->with('city');

        if ($viewer->city_id !== null) {
            $query->where('city_id', $viewer->city_id);
        }

        /** @var Collection<int, User> $candidates */
        $candidates = $query
            ->orderByDesc('id')
            ->limit($limit * 3)
            ->get()
            ->filter(fn (User $u) => $u->canParticipate())
            ->take($limit)
            ->values();

        return $candidates->map(function (User $candidate) use ($viewer) {
            return [
                'user' => $this->profiles->toPublicArray($candidate),
                'friendship' => $this->friendships->stateFor($viewer, $candidate),
            ];
        })->all();
    }

    /**
     * @return list<int>
     */
    private function relatedPendingIds(User $viewer): array
    {
        return Friendship::query()
            ->where('status', FriendshipStatus::Pending)
            ->where(function ($q) use ($viewer) {
                $q->where('requester_id', $viewer->id)
                    ->orWhere('addressee_id', $viewer->id);
            })
            ->get(['requester_id', 'addressee_id'])
            ->flatMap(fn (Friendship $row) => [
                (int) $row->requester_id,
                (int) $row->addressee_id,
            ])
            ->reject(fn (int $id) => $id === (int) $viewer->id)
            ->unique()
            ->values()
            ->all();
    }
}
