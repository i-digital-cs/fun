<?php

namespace App\Events;

use App\Models\EventMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EventMessageReacted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public EventMessage $message,
        public int $actorId,
        public ?string $myReaction,
        public int $likesCount,
        public int $dislikesCount,
    ) {
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
        return 'message.reacted';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'event_id' => $this->message->event_id,
            'actor_id' => $this->actorId,
            'likes_count' => $this->likesCount,
            'dislikes_count' => $this->dislikesCount,
            // Viewer-specific reaction is only accurate for the actor;
            // other clients keep their local my_reaction and update counts.
            'my_reaction' => $this->myReaction,
        ];
    }
}
