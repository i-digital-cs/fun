<?php

namespace App\Events;

use App\Models\Event;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserJoinAccepted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Event $event,
        public User $applicant,
        public User $accepter,
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->applicant->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'join.accepted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'join_accepted',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_url' => event_show_url($this->event),
            'accepter' => [
                'id' => $this->accepter->id,
                'name' => $this->accepter->name,
            ],
            'title' => __('events.notify_join_accepted_title'),
            'body' => __('events.notify_join_accepted_body', [
                'event' => $this->event->title,
            ]),
        ];
    }
}
