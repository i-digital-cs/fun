<?php

namespace App\Services\Admin;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Services\Media\PublicMediaUploader;
use App\Support\MediaDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ModerateEventService
{
    public function __construct(
        private readonly AdminAuditLogger $audit,
        private readonly PublicMediaUploader $uploader,
    ) {}

    public function cancel(User $actor, Event $event): Event
    {
        $this->assertCanModerate($actor);

        if ($event->status === EventStatus::Cancelled) {
            throw new DomainException(__('admin.event_already_cancelled'));
        }

        if ($event->status === EventStatus::Completed) {
            throw new DomainException(__('admin.event_already_completed'));
        }

        $previous = $event->status->value;
        $event->forceFill(['status' => EventStatus::Cancelled])->save();

        $this->audit->log($actor, 'event.cancelled', Event::class, $event->id, [
            'previous_status' => $previous,
        ]);

        return $event->refresh();
    }

    public function complete(User $actor, Event $event): Event
    {
        $this->assertCanModerate($actor);

        if ($event->status !== EventStatus::Published) {
            throw new DomainException(__('admin.event_not_published'));
        }

        $previous = $event->status->value;
        $event->forceFill(['status' => EventStatus::Completed])->save();

        $this->audit->log($actor, 'event.completed', Event::class, $event->id, [
            'previous_status' => $previous,
        ]);

        return $event->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Event $event, array $data, ?UploadedFile $image = null): Event
    {
        $this->assertCanModerate($actor);

        if (array_key_exists('category_id', $data)) {
            $this->assertCategoryExists((int) $data['category_id']);
        }

        if (array_key_exists('city_id', $data)) {
            $this->assertCityExists((int) $data['city_id']);
        }

        $startsAt = $data['starts_at'] ?? $event->starts_at;
        $endsAt = array_key_exists('ends_at', $data) ? $data['ends_at'] : $event->ends_at;

        if ($endsAt !== null && $endsAt !== '' && $endsAt <= $startsAt) {
            throw new DomainException(__('events.invalid_schedule'));
        }

        if (array_key_exists('max_participants', $data)) {
            $max = (int) $data['max_participants'];
            if ($max < 1 || $max < $event->acceptedMembersCount()) {
                throw new DomainException(__('events.capacity_below_members'));
            }
        }

        return DB::transaction(function () use ($actor, $event, $data, $image) {
            $previousStatus = $event->status->value;

            $payload = collect($data)->only([
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
            ])->all();

            if (array_key_exists('status', $data) && filled($data['status'])) {
                $payload['status'] = EventStatus::from((string) $data['status']);
            }

            if (array_key_exists('ends_at', $payload) && blank($payload['ends_at'])) {
                $payload['ends_at'] = null;
            }

            $event->forceFill($payload)->save();

            if ($image !== null) {
                if ($event->image_path) {
                    $this->uploader->delete(
                        $event->image_disk ?: MediaDisk::public(),
                        $event->image_path
                    );
                }
                $stored = $this->uploader->storeEventImage($image, $event->id);
                $event->forceFill([
                    'image_path' => $stored['path'],
                    'image_disk' => $stored['disk'],
                ])->save();
            }

            $this->audit->log($actor, 'event.updated', Event::class, $event->id, [
                'previous_status' => $previousStatus,
                'status' => $event->status->value,
                'fields' => array_keys($payload),
            ]);

            return $event->refresh()->load(['category', 'city', 'creator']);
        });
    }

    public function delete(User $actor, Event $event): void
    {
        $this->assertCanModerate($actor);

        DB::transaction(function () use ($actor, $event) {
            $id = $event->id;
            $title = $event->title;
            $status = $event->status->value;

            if ($event->image_path) {
                $this->uploader->delete(
                    $event->image_disk ?: MediaDisk::public(),
                    $event->image_path
                );
            }

            $event->delete();

            $this->audit->log($actor, 'event.deleted', Event::class, $id, [
                'title' => $title,
                'status' => $status,
            ]);
        });
    }

    private function assertCanModerate(User $actor): void
    {
        if ($actor->platform_role?->canModerateEvents() !== true) {
            throw new DomainException(__('admin.unauthorized'));
        }
    }

    private function assertCategoryExists(int $categoryId): void
    {
        if (! Category::query()->whereKey($categoryId)->exists()) {
            throw new DomainException(__('events.category_unavailable'));
        }
    }

    private function assertCityExists(int $cityId): void
    {
        if (! City::query()->whereKey($cityId)->exists()) {
            throw new DomainException(__('events.city_unavailable'));
        }
    }
}
