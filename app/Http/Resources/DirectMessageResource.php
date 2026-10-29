<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DirectMessage */
class DirectMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $name = $this->user?->name ?? '';

        $reply = $this->whenLoaded('replyTo', function () {
            if ($this->replyTo === null) {
                return null;
            }
            $rName = $this->replyTo->user?->name ?? '';

            return [
                'id' => $this->replyTo->id,
                'body' => $this->replyTo->body,
                'user' => [
                    'id' => $this->replyTo->user?->id,
                    'name' => $rName,
                ],
            ];
        });

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'body' => $this->body,
            'client_uuid' => $this->client_uuid,
            'message_type' => $this->message_type ?? 'text',
            'media_path' => $this->media_path,
            'media_mime' => $this->media_mime,
            'media_bytes' => $this->media_bytes,
            'media_duration_ms' => $this->media_duration_ms,
            'media_url' => $this->media_path
                ? url('/api/dms/media/'.$this->id)
                : null,
            'reply_to_id' => $this->reply_to_id,
            'reply_to' => $reply,
            'created_at' => $this->created_at?->toIso8601String(),
            'user' => [
                'id' => $this->user?->id,
                'name' => $name,
                'public_id' => $this->user?->public_id,
                'initials' => user_initials($name),
                'profile_image_path' => $this->user?->profile_image_path,
            ],
        ];
    }
}
