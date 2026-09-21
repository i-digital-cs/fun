<?php

namespace App\Http\Resources;

use App\Models\EventRating;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventRating */
class EventRatingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'stars' => $this->stars,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'rater' => $this->whenLoaded('rater', fn () => [
                'id' => $this->rater->id,
                'name' => $this->rater->name,
            ]),
            'rated_user' => $this->whenLoaded('ratedUser', fn () => [
                'id' => $this->ratedUser->id,
                'name' => $this->ratedUser->name,
            ]),
        ];
    }
}
