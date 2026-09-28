<?php

namespace App\Events;

use App\Models\Friendship;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFriendRequested implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public User $addressee,
        public User $requester,
        public Friendship $friendship,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->addressee->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'friend.requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'friend_request',
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_public_id' => $this->requester->public_id,
            'friendship_id' => $this->friendship->id,
            'title' => __('friends.notify_request_title'),
            'body' => __('friends.notify_request_body', ['name' => $this->requester->name]),
            'url' => route('friends.index', ['tab' => 'requests']),
            'api_path' => '/friends/requests',
        ];
    }
}
