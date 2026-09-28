<?php

namespace App\Services\Safety;

use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBlock;

class UserBlockService
{
    public function isBlockedEitherWay(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        return UserBlock::query()
            ->where(function ($outer) use ($a, $b) {
                $outer->where(function ($query) use ($a, $b) {
                    $query->where('blocker_id', $a->id)->where('blocked_id', $b->id);
                })->orWhere(function ($query) use ($a, $b) {
                    $query->where('blocker_id', $b->id)->where('blocked_id', $a->id);
                });
            })
            ->exists();
    }

    public function hasBlocked(User $blocker, User $blocked): bool
    {
        return UserBlock::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->exists();
    }

    /**
     * @return list<int>
     */
    public function blockedUserIdsFor(User $user): array
    {
        $blockedByMe = UserBlock::query()
            ->where('blocker_id', $user->id)
            ->pluck('blocked_id');

        $blockedMe = UserBlock::query()
            ->where('blocked_id', $user->id)
            ->pluck('blocker_id');

        return $blockedByMe->merge($blockedMe)->unique()->values()->all();
    }

    public function block(User $blocker, User $blocked): UserBlock
    {
        if ($blocker->id === $blocked->id) {
            throw new \App\Exceptions\DomainException(__('safety.cannot_block_self'));
        }

        if (! $blocker->canParticipate()) {
            throw new \App\Exceptions\DomainException(__('safety.not_allowed'));
        }

        $row = UserBlock::query()->firstOrCreate([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);

        // Block supersedes friendship — remove so DM cannot resume after unblock without a new request.
        Friendship::query()
            ->where(function ($outer) use ($blocker, $blocked) {
                $outer->where(function ($q) use ($blocker, $blocked) {
                    $q->where('requester_id', $blocker->id)->where('addressee_id', $blocked->id);
                })->orWhere(function ($q) use ($blocker, $blocked) {
                    $q->where('requester_id', $blocked->id)->where('addressee_id', $blocker->id);
                });
            })
            ->delete();

        return $row;
    }

    public function unblock(User $blocker, User $blocked): void
    {
        UserBlock::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }
}
