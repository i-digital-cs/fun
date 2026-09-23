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
     *     barcode_shadow: string,
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

        $code = $membership->ensureTicketCode();
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
            'code' => $code,
            'role' => $role,
            'pass_label' => __('events.ticket_pass_label'),
            'title' => filled($event->title) ? (string) $event->title : __('events.view_event'),
            'date_label' => $dateLabel,
            'date_iso' => $startsAt?->toIso8601String(),
            'venue_line' => $venueParts !== []
                ? implode(', ', $venueParts)
                : __('events.ticket_venue_tba'),
            'barcode_shadow' => $this->barcodeShadowFor($code),
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

    /**
     * Deterministic barcode pattern unique to this ticket code.
     */
    public function barcodeShadowFor(string $code): string
    {
        $bits = '';
        foreach (str_split(strtoupper($code)) as $char) {
            $bits .= str_pad(decbin(ord($char) % 64), 6, '0', STR_PAD_LEFT);
        }

        $shadows = ['0px 0 0 1px #000'];
        $x = 0;
        $len = strlen($bits);

        for ($i = 0; $i < $len && $x < 78; $i++) {
            $x += 2;
            if ($bits[$i] === '1') {
                $shadows[] = "{$x}px 0 0 1px #000";
                $x += 1;
            } else {
                $x += 2;
            }
        }

        return implode(', ', $shadows);
    }
}
