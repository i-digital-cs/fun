<?php

namespace App\Services\Events;

use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventMember;
use App\Models\User;

class BuildEventTicketService
{
    /**
     * @return array{
     *     code: string,
     *     role: string,
     *     pass_label: string,
     *     title: string,
     *     date_label: string,
     *     date_iso: string|null,
     *     venue_line: string,
     *     member: array{id: int, name: string},
     *     event: array{id: int, title: string, status: string}
     * }
     */
    public function forMember(User $user, Event $event): array
    {
        if (! $event->isMember($user)) {
            throw new DomainException(__('events.ticket_members_only'));
        }

        $membership = EventMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $event->loadMissing(['city']);

        $startsAt = $event->starts_at?->timezone(config('app.timezone'));
        $locale = app()->getLocale();

        $dateLabel = $startsAt === null
            ? __('events.ticket_date_tba')
            : ($locale === 'ar'
                ? $startsAt->translatedFormat('j F Y')
                : $startsAt->format('F j').$startsAt->format('S').' '.$startsAt->format('Y'));

        $venueParts = array_values(array_filter([
            filled($event->venue) ? (string) $event->venue : null,
            $event->city?->localizedName(),
        ]));

        $role = $membership->role->value;

        return [
            'code' => $this->codeFor($event->id, $user->id),
            'role' => $role,
            'pass_label' => __('events.ticket_pass_label'),
            'title' => filled($event->title) ? (string) $event->title : __('events.view_event'),
            'date_label' => $dateLabel,
            'date_iso' => $startsAt?->toIso8601String(),
            'venue_line' => $venueParts !== []
                ? implode(', ', $venueParts)
                : __('events.ticket_venue_tba'),
            'member' => [
                'id' => $user->id,
                'name' => (string) $user->name,
            ],
            'event' => [
                'id' => $event->id,
                'title' => filled($event->title) ? (string) $event->title : __('events.view_event'),
                'status' => $event->status->value,
            ],
        ];
    }

    public function codeFor(int $eventId, int $userId): string
    {
        $hash = hash_hmac('sha256', $eventId.':'.$userId, (string) config('app.key'));

        return strtoupper(substr($hash, 0, 8));
    }
}
