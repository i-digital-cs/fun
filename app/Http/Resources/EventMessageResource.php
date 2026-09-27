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
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'body' => $this->body,
            'is_system' => (bool) $this->is_system,
            'created_at' => $this->created_at?->toIso8601String(),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'dislikes_count' => (int) ($this->dislikes_count ?? 0),
            'my_reaction' => $this->my_reaction ?? null,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'public_id' => $this->user?->public_id,
                'profile_image_path' => $this->user?->profile_image_path,
            ],
        ];
    }
}
