<?php

namespace App\Events;

use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinRequested implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Event $event,
        public User $creator,
        public User $requester,
        public EventJoinRequest $joinRequest,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->creator->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'join.requested';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'join_request',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_url' => event_route('events.requests', $this->event),
            'join_request_id' => $this->joinRequest->id,
            'requester' => [
                'id' => $this->requester->id,
                'name' => $this->requester->name,
            ],
            'title' => __('events.notify_join_request_title'),
            'body' => __('events.notify_join_request_body', [
                'requester' => $this->requester->name,
                'event' => $this->event->title,
            ]),
        ];
    }
}
