<?php

namespace App\Events;

use App\Models\EventMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventMessagePosted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public EventMessage $message)
    {
        $this->message->loadMissing('user:id,name,public_id,profile_image_path');
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('events.'.$this->message->event_id.'.chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.posted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'event_id' => $this->message->event_id,
            'body' => $this->message->body,
            'is_system' => $this->message->is_system,
            'created_at' => $this->message->created_at?->toIso8601String(),
            'likes_count' => 0,
            'dislikes_count' => 0,
            'my_reaction' => null,
            'user' => [
                'id' => $this->message->user?->id,
                'name' => $this->message->user?->name,
                'public_id' => $this->message->user?->public_id,
                'initials' => user_initials($this->message->user?->name),
                'profile_image_path' => $this->message->user?->profile_image_path,
            ],
        ];
    }
}
