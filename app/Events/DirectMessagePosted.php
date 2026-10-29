<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessagePosted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public DirectMessage $message)
    {
        $this->message->loadMissing([
            'user:id,name,public_id,profile_image_path',
            'replyTo.user:id,name',
        ]);
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dms.'.$this->message->conversation_id),
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
        $name = $this->message->user?->name ?? '';
        $reply = null;
        if ($this->message->replyTo) {
            $rName = $this->message->replyTo->user?->name ?? '';
            $reply = [
                'id' => $this->message->replyTo->id,
                'body' => $this->message->replyTo->body,
                'user' => [
                    'id' => $this->message->replyTo->user?->id,
                    'name' => $rName,
                ],
            ];
        }

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'body' => $this->message->body,
            'client_uuid' => $this->message->client_uuid,
            'message_type' => $this->message->message_type ?? 'text',
            'media_mime' => $this->message->media_mime,
            'media_bytes' => $this->message->media_bytes,
            'media_duration_ms' => $this->message->media_duration_ms,
            'media_url' => $this->message->media_path
                ? url('/api/dms/media/'.$this->message->id)
                : null,
            'reply_to_id' => $this->message->reply_to_id,
            'reply_to' => $reply,
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
