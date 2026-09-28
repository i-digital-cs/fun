<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDirectMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public DirectMessage $message,
        public int $recipientUserId,
        public string $senderName,
        public string $chatUrl,
    ) {
        $this->message->loadMissing('user:id,name,profile_image_path,public_id');
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->recipientUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dm.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $name = $this->message->user?->name ?? $this->senderName;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'chat_url' => $this->chatUrl,
            'sender_name' => $name,
            'created_at' => $this->message->created_at?->toIso8601String(),
            'user' => [
                'id' => $this->message->user?->id,
                'name' => $name,
                'public_id' => $this->message->user?->public_id,
                'initials' => user_initials($name),
                'profile_image_path' => $this->message->user?->profile_image_path,
            ],
        ];
    }
}
