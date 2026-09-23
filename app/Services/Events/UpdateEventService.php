<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Services\Media\PublicMediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class UpdateEventService
{
    public function __construct(
        private readonly PublicMediaUploader $uploader,
        private readonly ResolveEventLocationService $locations,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Event $event, array $data, ?UploadedFile $image = null): Event
    {
        if (! $event->isManagedBy($actor)) {
            throw new DomainException(__('events.unauthorized'));
        }

        if (in_array($event->status, [EventStatus::Cancelled, EventStatus::Completed], true)) {
            throw new DomainException(__('events.immutable_status'));
        }

        if (array_key_exists('category_id', $data)) {
            $this->assertActiveCategory((int) $data['category_id']);
        }

        if (array_key_exists('city_id', $data)) {
            $this->assertActiveCity((int) $data['city_id']);
        }

        $startsAt = $data['starts_at'] ?? $event->starts_at;
        $endsAt = array_key_exists('ends_at', $data) ? $data['ends_at'] : $event->ends_at;

        if ($endsAt !== null && $endsAt <= $startsAt) {
            throw new DomainException(__('events.invalid_schedule'));
        }

        if (array_key_exists('max_participants', $data)) {
            $max = (int) $data['max_participants'];
            if ($max < 1 || $max < $event->acceptedMembersCount()) {
                throw new DomainException(__('events.capacity_below_members'));
            }
        }

        return DB::transaction(function () use ($event, $data, $image) {
            $place = $this->locations->resolve(
                array_merge([
                    'latitude' => $event->latitude,
                    'longitude' => $event->longitude,
                    'maps_url' => $event->maps_url,
                ], $data),
                isset($data['city_id']) ? (int) $data['city_id'] : $event->city_id
            );

            $event->fill(collect($data)->only([
                'category_id',
                'city_id',
                'title',
                'description',
                'venue',
                'starts_at',
                'ends_at',
                'max_participants',
                'requirements',
                'expected_cost',
                'cost_per_person',
            ])->all());

            $event->forceFill([
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'maps_url' => $place['maps_url'],
            ])->save();

            if ($image !== null) {
                if ($event->image_path) {
                    $this->uploader->delete(
                        $event->image_disk ?: \App\Support\MediaDisk::public(),
                        $event->image_path
                    );
                }
                $stored = $this->uploader->storeEventImage($image, $event->id);
                $event->forceFill([
                    'image_path' => $stored['path'],
                    'image_disk' => $stored['disk'],
                ])->save();
            }

            return $event->refresh()->load(['category', 'city', 'members']);
        });
    }

    private function assertActiveCategory(int $categoryId): void
    {
        if (! Category::query()->active()->whereKey($categoryId)->exists()) {
            throw new DomainException(__('events.category_unavailable'));
        }
    }

    private function assertActiveCity(int $cityId): void
    {
        if (! City::query()->active()->whereKey($cityId)->exists()) {
            throw new DomainException(__('events.city_unavailable'));
        }
    }
}
