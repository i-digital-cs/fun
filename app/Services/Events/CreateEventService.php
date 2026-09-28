<?php

namespace App\Services\Events;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\UserSex;
use App\Exceptions\DomainException;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;
use App\Services\Media\PublicMediaUploader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateEventService
{
    public function __construct(
        private readonly PublicMediaUploader $uploader,
        private readonly ResolveEventLocationService $locations,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $creator, array $data, ?UploadedFile $image = null): Event
    {
        if (! $creator->canParticipate()) {
            throw new DomainException(__('auth.pending_documents'));
        }

        $this->assertActiveCategory((int) $data['category_id']);
        $this->assertActiveCity((int) $data['city_id']);
        $this->assertSchedule($data);
        $this->assertCapacity((int) $data['max_participants']);

        $sex = $data['sex'] instanceof UserSex
            ? $data['sex']
            : UserSex::from((string) $data['sex']);

        $place = $this->locations->resolve($data, (int) $data['city_id']);
        $data = array_merge($data, $place);

        return DB::transaction(function () use ($creator, $data, $image, $sex) {
            $event = new Event([
                'category_id' => $data['category_id'],
                'city_id' => $data['city_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'venue' => $data['venue'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'maps_url' => $data['maps_url'] ?? null,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'] ?? null,
                'max_participants' => (int) $data['max_participants'],
                'sex' => $sex,
                'requirements' => $data['requirements'] ?? null,
                'expected_cost' => $data['expected_cost'] ?? null,
                'cost_per_person' => $data['cost_per_person'] ?? null,
            ]);
            $event->forceFill([
                'created_by' => $creator->id,
                'status' => EventStatus::Draft,
            ])->save();

            EventMember::query()->create([
                'event_id' => $event->id,
                'user_id' => $creator->id,
                'role' => EventMemberRole::Owner,
            ]);

            if ($image !== null) {
                $stored = $this->uploader->storeEventImage($image, $event->id);
                $event->forceFill([
                    'image_path' => $stored['path'],
                    'image_disk' => $stored['disk'],
                ])->save();
            }

            // Capacity must leave room for the owner membership.
            if ($event->acceptedMembersCount() > $event->max_participants) {
                throw new DomainException(__('events.capacity_too_small'));
            }

            return $event->refresh()->load([
                'category',
                'city',
                'members',
                'creator:id,public_id,name,profile_image_path',
            ]);
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertSchedule(array $data): void
    {
        if (! empty($data['ends_at']) && $data['ends_at'] <= $data['starts_at']) {
            throw new DomainException(__('events.invalid_schedule'));
        }
    }

    private function assertCapacity(int $maxParticipants): void
    {
        if ($maxParticipants < 1) {
            throw new DomainException(__('events.capacity_too_small'));
        }
    }
}
