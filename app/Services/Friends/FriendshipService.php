<?php

namespace App\Services\Friends;

use App\Enums\FriendshipStatus;
use App\Events\UserFriendRequested;
use App\Exceptions\DomainException;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendAcceptedNotification;
use App\Notifications\FriendRequestNotification;
use App\Services\Safety\UserBlockService;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class FriendshipService
{
    public function __construct(
        private readonly UserBlockService $blocks,
    ) {
    }

    public function findBetween(User $a, User $b): ?Friendship
    {
        return Friendship::query()
            ->where(function ($outer) use ($a, $b) {
                $outer->where(function ($q) use ($a, $b) {
                    $q->where('requester_id', $a->id)->where('addressee_id', $b->id);
                })->orWhere(function ($q) use ($a, $b) {
                    $q->where('requester_id', $b->id)->where('addressee_id', $a->id);
                });
            })
            ->first();
    }

    public function areFriends(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $row = $this->findBetween($a, $b);

        return $row !== null && $row->status === FriendshipStatus::Accepted;
    }

    /**
     * Friendship state relative to $viewer looking at $other.
     *
     * @return array{status: string|null, direction: string|null, can_message: bool, can_request: bool}
     */
    public function stateFor(User $viewer, User $other): array
    {
        if ($viewer->id === $other->id) {
            return [
                'status' => 'self',
                'direction' => null,
                'can_message' => false,
                'can_request' => false,
            ];
        }

        if ($this->blocks->isBlockedEitherWay($viewer, $other)) {
            return [
                'status' => 'blocked',
                'direction' => null,
                'can_message' => false,
                'can_request' => false,
            ];
        }

        $row = $this->findBetween($viewer, $other);

        if ($row === null) {
            return [
                'status' => null,
                'direction' => null,
                'can_message' => false,
                'can_request' => $viewer->canParticipate() && $other->canParticipate(),
            ];
        }

        $direction = (int) $row->requester_id === (int) $viewer->id ? 'outgoing' : 'incoming';

        if ($row->status === FriendshipStatus::Accepted) {
            return [
                'status' => 'accepted',
                'direction' => $direction,
                'can_message' => $viewer->canParticipate() && $other->canParticipate(),
                'can_request' => false,
            ];
        }

        if ($row->status === FriendshipStatus::Pending) {
            return [
                'status' => 'pending',
                'direction' => $direction,
                'can_message' => false,
                'can_request' => false,
            ];
        }

        // Declined — viewer may re-request.
        return [
            'status' => 'declined',
            'direction' => $direction,
            'can_message' => false,
            'can_request' => $viewer->canParticipate() && $other->canParticipate(),
        ];
    }

    public function request(User $actor, User $target): Friendship
    {
        if (! $actor->canParticipate()) {
            throw new DomainException(__('friends.not_allowed'));
        }

        if ($actor->id === $target->id) {
            throw new DomainException(__('friends.cannot_request_self'));
        }

        if (! $target->canParticipate() || $target->isDeactivated()) {
            throw new DomainException(__('friends.user_unavailable'));
        }

        if ($this->blocks->isBlockedEitherWay($actor, $target)) {
            throw new DomainException(__('friends.blocked'));
        }

        $existing = $this->findBetween($actor, $target);

        if ($existing !== null) {
            if ($existing->status === FriendshipStatus::Accepted) {
                throw new DomainException(__('friends.already_friends'));
            }

            if ($existing->status === FriendshipStatus::Pending) {
                // Reverse pending: expose accept to addressee; do not create B→A.
                if ((int) $existing->addressee_id === (int) $actor->id) {
                    throw new DomainException(__('friends.incoming_pending'));
                }

                throw new DomainException(__('friends.duplicate_request'));
            }

            // Declined → pending (reuse row; actor becomes requester).
            $existing->forceFill([
                'requester_id' => $actor->id,
                'addressee_id' => $target->id,
                'status' => FriendshipStatus::Pending,
            ])->save();

            $this->notifyRequest($target, $actor, $existing->fresh());

            return $existing->fresh(['requester', 'addressee']);
        }

        $friendship = Friendship::query()->create([
            'requester_id' => $actor->id,
            'addressee_id' => $target->id,
            'status' => FriendshipStatus::Pending,
        ]);

        $this->notifyRequest($target, $actor, $friendship);

        return $friendship->load(['requester', 'addressee']);
    }

    public function accept(User $actor, User $other): Friendship
    {
        if ($this->blocks->isBlockedEitherWay($actor, $other)) {
            throw new DomainException(__('friends.blocked'));
        }

        $row = DB::transaction(function () use ($actor, $other) {
            $locked = Friendship::query()
                ->where(function ($outer) use ($actor, $other) {
                    $outer->where(function ($q) use ($actor, $other) {
                        $q->where('requester_id', $other->id)->where('addressee_id', $actor->id);
                    });
                })
                ->where('status', FriendshipStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                throw new DomainException(__('friends.cannot_accept'));
            }

            $locked->forceFill(['status' => FriendshipStatus::Accepted])->save();

            return $locked;
        });

        try {
            $other->notify(new FriendAcceptedNotification($actor));
        } catch (Throwable $e) {
            Log::warning('friend_accepted_notify_failed', [
                'actor_id' => $actor->id,
                'other_id' => $other->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $row->fresh(['requester', 'addressee']);
    }

    public function decline(User $actor, User $other): Friendship
    {
        $row = $this->requireIncomingPending($actor, $other);
        $row->forceFill(['status' => FriendshipStatus::Declined])->save();

        return $row->fresh(['requester', 'addressee']);
    }

    public function cancel(User $actor, User $other): void
    {
        $row = $this->findBetween($actor, $other);

        if ($row === null
            || $row->status !== FriendshipStatus::Pending
            || (int) $row->requester_id !== (int) $actor->id) {
            throw new DomainException(__('friends.cannot_cancel'));
        }

        $row->delete();
    }

    public function remove(User $actor, User $other): void
    {
        $row = $this->findBetween($actor, $other);

        if ($row === null || $row->status !== FriendshipStatus::Accepted) {
            throw new DomainException(__('friends.not_friends'));
        }

        $row->delete();
    }

    /**
     * Accepted friends of $user (other party), excluding blocked and deactivated.
     *
     * @return Collection<int, User>
     */
    public function listFriends(User $user): Collection
    {
        $blockedIds = $this->blocks->blockedUserIdsFor($user);

        $rows = Friendship::query()
            ->where('status', FriendshipStatus::Accepted)
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->with(['requester:id,public_id,name,profile_image_path,status', 'addressee:id,public_id,name,profile_image_path,status'])
            ->latest('updated_at')
            ->get();

        return $rows->map(function (Friendship $row) use ($user, $blockedIds) {
            $other = (int) $row->requester_id === (int) $user->id ? $row->addressee : $row->requester;

            if ($other === null || $other->isDeactivated()) {
                return null;
            }

            if (in_array((int) $other->id, $blockedIds, true)) {
                return null;
            }

            return $other;
        })->filter()->values();
    }

    /**
     * Incoming pending requests for $user.
     *
     * @return Collection<int, Friendship>
     */
    public function listIncomingRequests(User $user): Collection
    {
        $blockedIds = $this->blocks->blockedUserIdsFor($user);

        return Friendship::query()
            ->where('addressee_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->with(['requester:id,public_id,name,profile_image_path,status'])
            ->latest()
            ->get()
            ->filter(function (Friendship $row) use ($blockedIds) {
                $requester = $row->requester;

                return $requester !== null
                    && ! $requester->isDeactivated()
                    && ! in_array((int) $requester->id, $blockedIds, true);
            })
            ->values();
    }

    /**
     * Outgoing pending requests by $user.
     *
     * @return Collection<int, Friendship>
     */
    public function listOutgoingRequests(User $user): Collection
    {
        return Friendship::query()
            ->where('requester_id', $user->id)
            ->where('status', FriendshipStatus::Pending)
            ->with(['addressee:id,public_id,name,profile_image_path,status'])
            ->latest()
            ->get();
    }

    /**
     * @return list<int>
     */
    public function acceptedFriendIds(User $user): array
    {
        $asRequester = Friendship::query()
            ->where('requester_id', $user->id)
            ->where('status', FriendshipStatus::Accepted)
            ->pluck('addressee_id');

        $asAddressee = Friendship::query()
            ->where('addressee_id', $user->id)
            ->where('status', FriendshipStatus::Accepted)
            ->pluck('requester_id');

        return $asRequester->merge($asAddressee)->unique()->values()->all();
    }

    /**
     * Remove any friendship row between two users (block / purge hygiene).
     */
    public function purgeBetween(User $a, User $b): void
    {
        Friendship::query()
            ->where(function ($outer) use ($a, $b) {
                $outer->where(function ($q) use ($a, $b) {
                    $q->where('requester_id', $a->id)->where('addressee_id', $b->id);
                })->orWhere(function ($q) use ($a, $b) {
                    $q->where('requester_id', $b->id)->where('addressee_id', $a->id);
                });
            })
            ->delete();
    }

    /**
     * Remove all friendships involving a user (account purge).
     */
    public function purgeForUser(User $user): void
    {
        Friendship::query()
            ->where(function ($q) use ($user) {
                $q->where('requester_id', $user->id)
                    ->orWhere('addressee_id', $user->id);
            })
            ->delete();
    }

    private function requireIncomingPending(User $actor, User $other): Friendship
    {
        $row = $this->findBetween($actor, $other);

        if ($row === null
            || $row->status !== FriendshipStatus::Pending
            || (int) $row->addressee_id !== (int) $actor->id
            || (int) $row->requester_id !== (int) $other->id) {
            throw new DomainException(__('friends.cannot_accept'));
        }

        return $row;
    }

    private function notifyRequest(User $addressee, User $requester, Friendship $friendship): void
    {
        try {
            $addressee->notify(new FriendRequestNotification($requester, $friendship));
        } catch (Throwable $e) {
            Log::warning('friend_request_notify_failed', [
                'requester_id' => $requester->id,
                'addressee_id' => $addressee->id,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            broadcast(new UserFriendRequested($addressee, $requester, $friendship));
        } catch (BroadcastException $e) {
            Log::warning('friend_request_broadcast_failed', [
                'requester_id' => $requester->id,
                'addressee_id' => $addressee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
