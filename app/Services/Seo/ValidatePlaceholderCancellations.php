<?php

namespace App\Services\Seo;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

/**
 * Read-only check before any future cancel of events 501, 502, and 602.
 * Does not change status, members, messages, or event 503.
 */
class ValidatePlaceholderCancellations
{
    /** @var list<int> */
    public const IDS = [501, 502, 602];

    /** @var list<string> */
    private const ALLOWED_DOMAINS = ['example.com', 'example.org'];

    /**
     * @return array{ok: bool, lines: list<string>}
     */
    public function __invoke(): array
    {
        $lines = [];
        $ok = true;

        foreach (self::IDS as $id) {
            $eventOk = $this->eventIsSafe($id, $lines);
            $ok = $eventOk && $ok;
        }

        $completed = Event::query()->find(503);
        if ($completed === null || $completed->status !== EventStatus::Completed) {
            $lines[] = 'FAIL 503 must stay completed and was not in a cancellable state.';
            $ok = false;
        } else {
            $lines[] = 'OK 503 remains completed and was not modified.';
        }

        $financial = EventFinancialLinks::unexpected([...self::IDS, 503]);
        if ($financial !== []) {
            foreach ($financial as $hit) {
                $lines[] = 'FAIL '.$hit;
            }
            $ok = false;
        } else {
            $lines[] = 'OK no payment, order, or charge rows for 501, 502, 503, or 602.';
        }

        return ['ok' => $ok, 'lines' => $lines];
    }

    /**
     * @param  list<string>  $lines
     */
    private function eventIsSafe(int $id, array &$lines): bool
    {
        $event = Event::query()->find($id);
        if ($event === null) {
            $lines[] = "FAIL {$id} does not exist.";

            return false;
        }

        if ($event->status !== EventStatus::Published || ! $event->status->canTransitionTo(EventStatus::Cancelled)) {
            $lines[] = "FAIL {$id} status is {$event->status->value} and cannot be cancelled by the existing rules.";

            return false;
        }

        $domains = $this->relatedDomains($id);
        $unexpected = array_values(array_filter(
            $domains,
            fn (string $domain) => ! in_array($domain, self::ALLOWED_DOMAINS, true)
        ));

        if ($unexpected !== []) {
            $lines[] = "FAIL {$id} has a related account outside @example.com and @example.org.";

            return false;
        }

        $notifications = $this->notificationCount($id);
        $reports = DB::table('reports')
            ->where('reportable_type', 'event')
            ->where('reportable_id', $id)
            ->count();

        if ($notifications > 0 || $reports > 0) {
            $lines[] = "FAIL {$id} has operational dependencies (notifications {$notifications}, reports {$reports}).";

            return false;
        }

        $lines[] = "OK {$id} is published, cancellable, and has no real-user or financial dependency.";

        return true;
    }

    /**
     * @return list<string>
     */
    private function relatedDomains(int $eventId): array
    {
        $userIds = collect()
            ->merge(DB::table('event_members')->where('event_id', $eventId)->pluck('user_id'))
            ->merge(DB::table('event_messages')->where('event_id', $eventId)->pluck('user_id'))
            ->merge(DB::table('event_join_requests')->where('event_id', $eventId)->pluck('user_id'))
            ->merge(DB::table('event_favorites')->where('event_id', $eventId)->pluck('user_id'))
            ->merge(DB::table('event_ratings')->where('event_id', $eventId)->pluck('rater_id'))
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        return DB::table('users')->whereIn('id', $userIds)->pluck('email')
            ->map(function ($email) {
                $email = strtolower(trim((string) $email));
                $at = strrpos($email, '@');

                return $at === false ? '' : substr($email, $at + 1);
            })
            ->unique()
            ->values()
            ->all();
    }

    private function notificationCount(int $eventId): int
    {
        return DB::table('notifications')
            ->where('data', 'like', '%"event_id":'.$eventId.'%')
            ->orWhere('data', 'like', '%"event_id": '.$eventId.'%')
            ->get(['data'])
            ->filter(function ($row) use ($eventId) {
                $data = json_decode((string) $row->data, true);

                return is_array($data) && (int) ($data['event_id'] ?? 0) === $eventId;
            })
            ->count();
    }
}
