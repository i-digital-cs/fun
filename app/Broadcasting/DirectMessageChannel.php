<?php

namespace App\Broadcasting;

use App\Models\DirectConversation;
use App\Models\User;
use App\Services\Friends\FriendshipService;
use App\Services\Safety\UserBlockService;

class DirectMessageChannel
{
    public function __construct(
        private readonly FriendshipService $friendships,
        private readonly UserBlockService $blocks,
    ) {
    }

    public function join(User $user, int $conversationId): bool
    {
        if (! $user->canParticipate()) {
            return false;
        }

        $conversation = DirectConversation::query()->find($conversationId);

        if ($conversation === null || ! $conversation->involves((int) $user->id)) {
            return false;
        }

        $otherId = $conversation->otherUserId((int) $user->id);
        $other = User::query()->find($otherId);

        if ($other === null || $other->isDeactivated()) {
            return false;
        }

        if ($this->blocks->isBlockedEitherWay($user, $other)) {
            return false;
        }

        return $this->friendships->areFriends($user, $other);
    }
}
