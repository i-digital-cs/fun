<?php

namespace App\Services\Events\Chat;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventChatRead;
use App\Models\EventMember;
use App\Models\EventMessage;
use App\Models\User;
use Illuminate\Support\Collection;

class ListUserChatsService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function list(User $user): Collection
    {
        $eventIds = EventMember::query()
            ->where('user_id', $user->id)
            ->pluck('event_id');

        if ($eventIds->isEmpty()) {
            return collect();
        }

        $events = Event::query()
            ->whereIn('id', $eventIds)
            ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
            ->with(['city:id,name_en,name_ar'])
            ->get()
            ->keyBy('id');

        if ($events->isEmpty()) {
            return collect();
        }

        $activeIds = $events->keys();

        $lastMessageIds = EventMessage::query()
            ->selectRaw('event_id, MAX(id) as max_id')
            ->whereIn('event_id', $activeIds)
            ->groupBy('event_id')
            ->pluck('max_id', 'event_id');

        $lastMessages = $lastMessageIds->isEmpty()
            ? collect()
            : EventMessage::query()
                ->whereIn('id', $lastMessageIds->values())
                ->with('user:id,name,profile_image_path')
                ->get()
                ->keyBy('event_id');

        $reads = EventChatRead::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $activeIds)
            ->get()
            ->keyBy('event_id');

        $unreadCounts = EventMessage::query()
            ->selectRaw('event_id, COUNT(*) as unread_count')
            ->whereIn('event_id', $activeIds)
            ->where('user_id', '!=', $user->id)
            ->where(function ($query) use ($reads, $activeIds): void {
                foreach ($activeIds as $eventId) {
                    $lastRead = (int) ($reads->get($eventId)?->last_read_message_id ?? 0);
                    $query->orWhere(function ($inner) use ($eventId, $lastRead): void {
                        $inner->where('event_id', $eventId)
                            ->where('id', '>', $lastRead);
                    });
                }
            })
            ->groupBy('event_id')
            ->pluck('unread_count', 'event_id');

        return $activeIds
            ->map(function ($eventId) use ($events, $lastMessages, $unreadCounts, $user) {
                /** @var Event $event */
                $event = $events->get($eventId);
                $last = $lastMessages->get($eventId);
                $unread = (int) ($unreadCounts->get($eventId) ?? 0);

                return [
                    'event_id' => $event->id,
                    'title' => $event->title,
                    'city' => $event->city?->localizedName(),
                    'chat_url' => route('events.chat', $event),
                    'unread_count' => $unread,
                    'last_message' => $last === null ? null : [
                        'id' => $last->id,
                        'body' => $last->body,
                        'created_at' => $last->created_at?->toIso8601String(),
                        'is_mine' => (int) $last->user_id === (int) $user->id,
                        'user' => [
                            'id' => $last->user?->id,
                            'name' => $last->user?->name,
                            'initials' => user_initials($last->user?->name),
                            'profile_image_path' => $last->user?->profile_image_path,
                        ],
                    ],
                ];
            })
            ->sortByDesc(function (array $row) {
                return $row['last_message']['created_at'] ?? '';
            })
            ->values();
    }

    public function unreadTotal(User $user): int
    {
        $eventIds = EventMember::query()
            ->where('user_id', $user->id)
            ->pluck('event_id');

        if ($eventIds->isEmpty()) {
            return 0;
        }

        $activeIds = Event::query()
            ->whereIn('id', $eventIds)
            ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
            ->pluck('id');

        if ($activeIds->isEmpty()) {
            return 0;
        }

        $reads = EventChatRead::query()
            ->where('user_id', $user->id)
            ->whereIn('event_id', $activeIds)
            ->pluck('last_read_message_id', 'event_id');

        return (int) EventMessage::query()
            ->whereIn('event_id', $activeIds)
            ->where('user_id', '!=', $user->id)
            ->where(function ($query) use ($activeIds, $reads): void {
                foreach ($activeIds as $eventId) {
                    $lastRead = (int) ($reads[$eventId] ?? 0);
                    $query->orWhere(function ($inner) use ($eventId, $lastRead): void {
                        $inner->where('event_id', $eventId)
                            ->where('id', '>', $lastRead);
                    });
                }
            })
            ->count();
    }
}
