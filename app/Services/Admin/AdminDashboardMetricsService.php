<?php

namespace App\Services\Admin;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\ReportStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\DirectMessage;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMessage;
use App\Models\Friendship;
use App\Models\IdentityDocument;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminDashboardMetricsService
{
    /**
     * @return list<string>
     */
    public function metricKeys(): array
    {
        return [
            'active_members',
            'registrations',
            'events_created',
            'join_requests',
            'joins_accepted',
            'event_messages',
            'direct_messages',
            'friend_requests',
            'friend_accepts',
        ];
    }

    public function isValidMetric(string $metric): bool
    {
        return in_array($metric, $this->metricKeys(), true);
    }

    /**
     * @return array{day: string, start: Carbon, end: Carbon, label: string}
     */
    public function resolveDayWindow(string $day = 'today', ?CarbonInterface $now = null): array
    {
        $tz = (string) config('app.timezone');
        $now = ($now ?? now($tz))->copy()->timezone($tz);
        $startOfToday = $now->copy()->startOfDay();

        return match ($day) {
            'yesterday' => [
                'day' => 'yesterday',
                'start' => $startOfToday->copy()->subDay(),
                'end' => $startOfToday->copy(),
                'label' => __('admin.activity_day_yesterday'),
            ],
            default => [
                'day' => 'today',
                'start' => $startOfToday->copy(),
                'end' => $startOfToday->copy()->addDay(),
                'label' => __('admin.activity_day_today'),
            ],
        };
    }

    /**
     * @return array{
     *     today: array<string, int>,
     *     yesterday: array<string, int>,
     *     deltas: array<string, array{value: int, percent: int|null}>,
     *     stats: array<string, int>,
     *     charts: array<string, mixed>
     * }
     */
    public function build(?CarbonInterface $now = null): array
    {
        $tz = (string) config('app.timezone');
        $now = ($now ?? now($tz))->copy()->timezone($tz);
        $startOfToday = $now->copy()->startOfDay();
        $startOfTomorrow = $startOfToday->copy()->addDay();
        $startOfYesterday = $startOfToday->copy()->subDay();

        $today = $this->periodCounts($startOfToday, $startOfTomorrow);
        $yesterday = $this->periodCounts($startOfYesterday, $startOfToday);

        return [
            'today' => $today,
            'yesterday' => $yesterday,
            'deltas' => $this->deltas($today, $yesterday),
            'stats' => $this->lifetimeStats(),
            'charts' => $this->charts($startOfToday),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function periodCounts(CarbonInterface $start, CarbonInterface $end): array
    {
        return [
            'active_members' => $this->activeMembersBetween($start, $end),
            'registrations' => User::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'events_created' => Event::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'join_requests' => EventJoinRequest::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'joins_accepted' => $this->acceptedJoinRequestsQuery($start, $end)->count(),
            'event_messages' => EventMessage::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'direct_messages' => DirectMessage::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'friend_requests' => Friendship::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->count(),
            'friend_accepts' => Friendship::query()
                ->where('status', FriendshipStatus::Accepted)
                ->where('updated_at', '>=', $start)
                ->where('updated_at', '<', $end)
                ->count(),
        ];
    }

    public function paginateMetric(string $metric, CarbonInterface $start, CarbonInterface $end, int $perPage = 40): LengthAwarePaginator
    {
        if (! $this->isValidMetric($metric)) {
            throw new InvalidArgumentException("Unknown activity metric [{$metric}].");
        }

        return match ($metric) {
            'active_members' => $this->paginateActiveMembers($start, $end, $perPage),
            'registrations' => User::query()
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'events_created' => Event::query()
                ->with(['creator:id,name,email', 'city'])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'join_requests' => EventJoinRequest::query()
                ->with(['user:id,name,email', 'event:id,title,slug,status'])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'joins_accepted' => $this->acceptedJoinRequestsQuery($start, $end)
                ->with(['user:id,name,email', 'event:id,title,slug,status', 'reviewer:id,name'])
                ->orderByDesc(DB::raw('COALESCE(reviewed_at, updated_at)'))
                ->paginate($perPage)
                ->withQueryString(),
            'event_messages' => EventMessage::query()
                ->with(['user:id,name,email', 'event:id,title,slug,status'])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'direct_messages' => DirectMessage::query()
                ->with([
                    'user:id,name,email',
                    'conversation.userLow:id,name,email',
                    'conversation.userHigh:id,name,email',
                ])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'friend_requests' => Friendship::query()
                ->with(['requester:id,name,email', 'addressee:id,name,email'])
                ->where('created_at', '>=', $start)
                ->where('created_at', '<', $end)
                ->latest('created_at')
                ->paginate($perPage)
                ->withQueryString(),
            'friend_accepts' => Friendship::query()
                ->with(['requester:id,name,email', 'addressee:id,name,email'])
                ->where('status', FriendshipStatus::Accepted)
                ->where('updated_at', '>=', $start)
                ->where('updated_at', '<', $end)
                ->latest('updated_at')
                ->paginate($perPage)
                ->withQueryString(),
        };
    }

    /**
     * @return Builder<EventJoinRequest>
     */
    private function acceptedJoinRequestsQuery(CarbonInterface $start, CarbonInterface $end): Builder
    {
        return EventJoinRequest::query()
            ->where('status', JoinRequestStatus::Accepted)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($inner) use ($start, $end) {
                    $inner->whereNotNull('reviewed_at')
                        ->where('reviewed_at', '>=', $start)
                        ->where('reviewed_at', '<', $end);
                })->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNull('reviewed_at')
                        ->where('updated_at', '>=', $start)
                        ->where('updated_at', '<', $end);
                });
            });
    }

    private function paginateActiveMembers(CarbonInterface $start, CarbonInterface $end, int $perPage): LengthAwarePaginator
    {
        $sessions = DB::table('sessions')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $start->getTimestamp())
            ->where('last_activity', '<', $end->getTimestamp())
            ->groupBy('user_id');

        return User::query()
            ->joinSub($sessions, 'active_sessions', 'users.id', '=', 'active_sessions.user_id')
            ->select('users.*', 'active_sessions.last_activity as session_last_activity')
            ->orderByDesc('active_sessions.last_activity')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function activeMembersBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', $start->getTimestamp())
            ->where('last_activity', '<', $end->getTimestamp())
            ->distinct()
            ->count('user_id');
    }

    /**
     * @param  array<string, int>  $today
     * @param  array<string, int>  $yesterday
     * @return array<string, array{value: int, percent: int|null}>
     */
    private function deltas(array $today, array $yesterday): array
    {
        $out = [];
        foreach ($today as $key => $value) {
            $prev = (int) ($yesterday[$key] ?? 0);
            $diff = $value - $prev;
            $percent = $prev > 0
                ? (int) round(($diff / $prev) * 100)
                : ($value > 0 ? 100 : null);
            $out[$key] = [
                'value' => $diff,
                'percent' => $percent,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, int>
     */
    private function lifetimeStats(): array
    {
        return [
            'active_users' => User::query()->where('status', AccountStatus::Active)->count(),
            'pending_users' => User::query()->where('status', AccountStatus::PendingDocuments)->count(),
            'published_events' => Event::query()->where('status', EventStatus::Published)->count(),
            'pending_reports' => Report::query()->where('status', ReportStatus::Pending)->count(),
            'identity_documents' => IdentityDocument::query()->count(),
            'cities' => City::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function charts(CarbonInterface $startOfToday): array
    {
        $dayLabels = [];
        $eventsPerDay = [];
        $usersPerDay = [];
        $joinRequestsPerDay = [];
        $joinsAcceptedPerDay = [];
        $eventMessagesPerDay = [];
        $directMessagesPerDay = [];

        for ($i = 13; $i >= 0; $i--) {
            $day = $startOfToday->copy()->subDays($i);
            $key = $day->toDateString();
            $dayLabels[] = $day->translatedFormat('M j');
            $eventsPerDay[$key] = 0;
            $usersPerDay[$key] = 0;
            $joinRequestsPerDay[$key] = 0;
            $joinsAcceptedPerDay[$key] = 0;
            $eventMessagesPerDay[$key] = 0;
            $directMessagesPerDay[$key] = 0;
        }

        $from = $startOfToday->copy()->subDays(13);

        $this->fillCreatedAtDayCounts(Event::query()->where('created_at', '>=', $from), $eventsPerDay);
        $this->fillCreatedAtDayCounts(User::query()->where('created_at', '>=', $from), $usersPerDay);
        $this->fillCreatedAtDayCounts(EventJoinRequest::query()->where('created_at', '>=', $from), $joinRequestsPerDay);
        $this->fillCreatedAtDayCounts(EventMessage::query()->where('created_at', '>=', $from), $eventMessagesPerDay);
        $this->fillCreatedAtDayCounts(DirectMessage::query()->where('created_at', '>=', $from), $directMessagesPerDay);

        EventJoinRequest::query()
            ->where('status', JoinRequestStatus::Accepted)
            ->where(function ($q) use ($from) {
                $q->where(function ($inner) use ($from) {
                    $inner->whereNotNull('reviewed_at')->where('reviewed_at', '>=', $from);
                })->orWhere(function ($inner) use ($from) {
                    $inner->whereNull('reviewed_at')->where('updated_at', '>=', $from);
                });
            })
            ->select(DB::raw('DATE(COALESCE(reviewed_at, updated_at)) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->get()
            ->each(function ($row) use (&$joinsAcceptedPerDay) {
                $day = Carbon::parse($row->day)->toDateString();
                if (array_key_exists($day, $joinsAcceptedPerDay)) {
                    $joinsAcceptedPerDay[$day] = (int) $row->aggregate;
                }
            });

        $eventsByStatus = Event::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $status = $row->status instanceof EventStatus ? $row->status->value : (string) $row->status;

                return [$status => (int) $row->aggregate];
            });

        $usersByStatus = User::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $status = $row->status instanceof AccountStatus ? $row->status->value : (string) $row->status;

                return [$status => (int) $row->aggregate];
            });

        $eventsByCategory = Category::query()
            ->withCount(['events as events_count'])
            ->orderByDesc('events_count')
            ->limit(8)
            ->get()
            ->map(fn (Category $category) => [
                'label' => $category->localizedName(),
                'count' => (int) $category->events_count,
            ]);

        $statusLabels = [
            EventStatus::Draft->value => __('events.status_draft'),
            EventStatus::Published->value => __('events.status_published'),
            EventStatus::Cancelled->value => __('events.status_cancelled'),
            EventStatus::Completed->value => __('events.status_completed'),
        ];

        return [
            'days' => $dayLabels,
            'events_per_day' => array_values($eventsPerDay),
            'users_per_day' => array_values($usersPerDay),
            'join_requests_per_day' => array_values($joinRequestsPerDay),
            'joins_accepted_per_day' => array_values($joinsAcceptedPerDay),
            'event_messages_per_day' => array_values($eventMessagesPerDay),
            'direct_messages_per_day' => array_values($directMessagesPerDay),
            'event_status_labels' => array_values($statusLabels),
            'event_status_values' => array_map(
                fn (string $status) => (int) ($eventsByStatus[$status] ?? 0),
                array_keys($statusLabels)
            ),
            'category_labels' => $eventsByCategory->pluck('label')->all(),
            'category_values' => $eventsByCategory->pluck('count')->all(),
            'user_status_labels' => [
                __('admin.status_active'),
                __('admin.status_pending'),
                __('admin.status_deactivated'),
            ],
            'user_status_values' => [
                (int) ($usersByStatus[AccountStatus::Active->value] ?? 0),
                (int) ($usersByStatus[AccountStatus::PendingDocuments->value] ?? 0),
                (int) ($usersByStatus[AccountStatus::Deactivated->value] ?? 0),
            ],
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, int>  $bucket
     */
    private function fillCreatedAtDayCounts($query, array &$bucket): void
    {
        $query
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->get()
            ->each(function ($row) use (&$bucket) {
                $day = Carbon::parse($row->day)->toDateString();
                if (array_key_exists($day, $bucket)) {
                    $bucket[$day] = (int) $row->aggregate;
                }
            });
    }
}
