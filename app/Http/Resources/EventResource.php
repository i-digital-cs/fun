<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $membersCount = $this->members_count ?? $this->acceptedMembersCount();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'venue' => $this->venue,
            'location_label' => $this->locationLabel() ?: null,
            'location' => $this->locationPayload(),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'maps_url' => $this->maps_url,
            'maps_open_url' => $this->mapsOpenUrl(),
            'has_place_pin' => $this->hasPlacePin(),
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'max_participants' => $this->max_participants,
            'members_count' => $membersCount,
            'remaining_capacity' => max(0, $this->max_participants - $membersCount),
            'is_full' => $membersCount >= $this->max_participants,
            'requirements' => $this->requirements,
            'expected_cost' => $this->expected_cost,
            'cost_per_person' => $this->cost_per_person,
            'image_url' => $this->coverUrl('card'),
            'image_url_detail' => $this->coverUrl('detail'),
            'status' => $this->status->value,
            'ready_for_ratings' => $this->isReadyForRatings(),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->localizedName(),
                'name_en' => $this->category->name_en,
                'name_ar' => $this->category->name_ar,
            ]),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->localizedName(),
                'name_en' => $this->city->name_en,
                'name_ar' => $this->city->name_ar,
                'latitude' => $this->city->latitude !== null ? (float) $this->city->latitude : null,
                'longitude' => $this->city->longitude !== null ? (float) $this->city->longitude : null,
            ]),
            'distance_km' => $this->when(
                $this->distance_km !== null,
                fn () => (float) $this->distance_km
            ),
            'join_action' => $this->when(
                $this->join_action !== null,
                fn () => $this->join_action
            ),
            'is_favorited' => $this->when(
                isset($this->is_favorited),
                fn () => (bool) $this->is_favorited
            ),
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'public_id' => $this->creator->public_id,
                'name' => $this->creator->name,
                'profile_image_path' => $this->creator->profile_image_path,
                'initials' => user_initials($this->creator->name),
                'profile_url' => $this->creator->publicProfileUrl(),
            ]),
        ];
    }
}
