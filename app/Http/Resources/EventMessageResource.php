<?php

namespace App\Http\Resources;

use App\Models\EventMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventMessage */
class EventMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reply = $this->whenLoaded('replyTo', function () {
            if ($this->replyTo === null) {
                return null;
            }

            return [
                'id' => $this->replyTo->id,
                'body' => $this->replyTo->body,
                'user' => [
                    'id' => $this->replyTo->user?->id,
                    'name' => $this->replyTo->user?->name,
                ],
            ];
        });

        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'body' => $this->body,
            'is_system' => (bool) $this->is_system,
            'client_uuid' => $this->client_uuid,
            'message_type' => $this->message_type ?? 'text',
            'media_path' => $this->media_path,
            'media_mime' => $this->media_mime,
            'media_bytes' => $this->media_bytes,
            'media_duration_ms' => $this->media_duration_ms,
            'media_url' => $this->media_path
                ? url('/api/events/'.$this->event_id.'/messages/'.$this->id.'/media')
                : null,
            'reply_to_id' => $this->reply_to_id,
            'reply_to' => $reply,
            'created_at' => $this->created_at?->toIso8601String(),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'dislikes_count' => (int) ($this->dislikes_count ?? 0),
            'my_reaction' => $this->my_reaction ?? null,
            'emoji_counts' => $this->emoji_counts ?? new \stdClass,
            'my_emojis' => $this->my_emojis ?? [],
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'public_id' => $this->user?->public_id,
                'profile_image_path' => $this->user?->profile_image_path,
            ],
        ];
    }
}
