<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserFriendAccepted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public User $recipient,
        public User $accepter,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->recipient->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'friend.accepted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'friend_accepted',
            'accepter_id' => $this->accepter->id,
            'accepter_name' => $this->accepter->name,
            'accepter_public_id' => $this->accepter->public_id,
            'title' => __('friends.notify_accepted_title'),
            'body' => __('friends.notify_accepted_body', ['name' => $this->accepter->name]),
            'url' => route('friends.index'),
            'api_path' => '/friends',
        ];
    }
}
