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

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'body' => $this->body,
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
