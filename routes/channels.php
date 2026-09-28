<?php

use App\Broadcasting\DirectMessageChannel;
use App\Broadcasting\EventChatChannel;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('events.{eventId}.chat', EventChatChannel::class);

Broadcast::channel('dms.{conversationId}', DirectMessageChannel::class);
