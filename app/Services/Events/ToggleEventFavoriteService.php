<?php

namespace App\Services\Events;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventFavorite;
use App\Models\User;
use Illuminate\Support\Collection;

class ToggleEventFavoriteService
{
    public function toggle(User $user, Event $event): bool
    {
        if (! $user->canParticipate()) {
            throw new DomainException(__('events.favorite_requires_active'));
        }

        $existing = EventFavorite::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        EventFavorite::query()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        return true;
    }

    public function isFavorited(User $user, Event $event): bool
    {
        return EventFavorite::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->exists();
    }

    /**
     * @param  Collection<int, Event>|iterable<Event>  $events
     * @return array<int, true> keyed by event id
     */
    public function favoritedIdsFor(User $user, iterable $events): array
    {
        $ids = collect($events)->pluck('id')->filter()->unique()->values()->all();
        if ($ids === []) {
            return [];
        }

        return EventFavorite::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $ids)
            ->pluck('event_id')
            ->mapWithKeys(fn ($id) => [(int) $id => true])
            ->all();
    }
}
