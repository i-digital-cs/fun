<?php

namespace App\Services\Admin\Dashboard;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Enums\JoinRequestStatus;
use App\Enums\ReportableType;
use App\Enums\ReportStatus;
use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\Friendship;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\AdminDashboardMetricsService;
use App\Support\Reporting\ReportingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsService
{
    private const CACHE_TTL_SECONDS = 60;

    public function __construct(
        private readonly AdminDashboardMetricsService $metrics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(ReportingPeriod $period, ?User $viewer = null): array
    {
        $locale = app()->getLocale();
        $roleFlag = $viewer?->isSuperAdmin() ? 'sa' : 'mod';
        $cacheKey = "admin.dashboard.v2.{$period->cacheKey()}.{$locale}.{$roleFlag}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($period, $viewer) {
            return $this->assemble($period, $viewer);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function assemble(ReportingPeriod $period, ?User $viewer): array
    {
        $current = $this->metrics->periodCounts($period->start, $period->endExclusive);
        $previous = $this->metrics->periodCounts($period->previousStart, $period->previousEndExclusive);

        $participantsCurrent = $this->countBetween(EventMember::query(), 'created_at', $period->start, $period->endExclusive);
        $participantsPrevious = $this->countBetween(EventMember::query(), 'created_at', $period->previousStart, $period->previousEndExclusive);

        $creatorsCurrent = (int) Event::query()
            ->where('created_at', '>=', $period->start)
            ->where('created_at', '<', $period->endExclusive)
            ->distinct()
            ->count('created_by');

        $joinersCurrent = (int) EventMember::query()
            ->where('created_at', '>=', $period->start)
            ->where('created_at', '<', $period->endExclusive)
            ->distinct()
            ->count('user_id');

        $snapshots = $this->snapshots();
        $eventStatus = $this->eventStatusBreakdown();
        $charts = $this->chartsForPeriod($period);
        $actionCenter = $this->actionCenter($snapshots);
        $activity = $this->recentActivity($viewer);
        $recentEvents = $this->recentEvents();
        $geo = $this->geographicInsights($period);
        $socialLifetime = $this->socialLifetime();
        $health = $this->systemHealth();

        $kpis = [
            $this->kpi(
                key: 'total_users',
                value: $snapshots['total_users'],
                previous: null,
                kind: 'lifetime',
                href: route('admin.users.index'),
                hint: 'kpi_hint_total_users',
            ),
            $this->kpi(
                key: 'new_users',
                value: $current['registrations'],
                previous: $previous['registrations'],
                kind: 'period',
                href: route('admin.activity.show', ['metric' => 'registrations', 'day' => 'today']),
                hint: 'kpi_hint_new_users',
            ),
            $this->kpi(
                key: 'session_active',
                value: $current['active_members'],
                previous: $previous['active_members'],
                kind: 'period',
                href: route('admin.activity.show', ['metric' => 'active_members', 'day' => 'today']),
                hint: 'kpi_hint_session_active',
            ),
            $this->kpi(
                key: 'published_events',
                value: $snapshots['published_events'],
                previous: null,
                kind: 'lifetime',
                href: route('admin.events.index', ['status' => EventStatus::Published->value]),
                hint: 'kpi_hint_published_events',
            ),
            $this->kpi(
                key: 'events_created',
                value: $current['events_created'],
                previous: $previous['events_created'],
                kind: 'period',
                href: route('admin.activity.show', ['metric' => 'events_created', 'day' => 'today']),
                hint: 'kpi_hint_events_created',
            ),
            $this->kpi(
                key: 'participants',
                value: $participantsCurrent,
                previous: $participantsPrevious,
                kind: 'period',
                href: route('admin.events.index'),
                hint: 'kpi_hint_participants',
            ),
            $this->kpi(
                key: 'active_accounts',
                value: $snapshots['active_users'],
                previous: null,
                kind: 'lifetime',
                href: route('admin.users.index', ['status' => AccountStatus::Active->value]),
                hint: 'kpi_hint_active_accounts',
            ),
            $this->kpi(
                key: 'pending_reports',
                value: $snapshots['pending_reports'],
                previous: null,
                kind: 'lifetime',
                href: route('admin.reports.index'),
                hint: 'kpi_hint_pending_reports',
            ),
        ];

        // Backward-compatible keys for existing tests / activity links.
        $todayWindow = $this->metrics->resolveDayWindow('today');
        $yesterdayWindow = $this->metrics->resolveDayWindow('yesterday');
        $today = $this->metrics->periodCounts($todayWindow['start'], $todayWindow['end']);
        $yesterday = $this->metrics->periodCounts($yesterdayWindow['start'], $yesterdayWindow['end']);

        return [
            'period' => $period->meta(),
            'generated_at' => now(config('app.timezone')),
            'kpis' => $kpis,
            'action_center' => $actionCenter,
            'activity_feed' => $activity,
            'event_status' => $eventStatus,
            'event_ops' => [
                'created_period' => $current['events_created'],
                'join_requests_period' => $current['join_requests'],
                'joins_accepted_period' => $current['joins_accepted'],
                'participants_period' => $participantsCurrent,
                'creators_period' => $creatorsCurrent,
                'joiners_period' => $joinersCurrent,
            ],
            'recent_events' => $recentEvents,
            'social' => [
                'period' => [
                    'friend_requests' => $current['friend_requests'],
                    'friend_accepts' => $current['friend_accepts'],
                    'event_messages' => $current['event_messages'],
                    'direct_messages' => $current['direct_messages'],
                    'deltas' => [
                        'friend_requests' => $this->delta($current['friend_requests'], $previous['friend_requests']),
                        'friend_accepts' => $this->delta($current['friend_accepts'], $previous['friend_accepts']),
                        'event_messages' => $this->delta($current['event_messages'], $previous['event_messages']),
                        'direct_messages' => $this->delta($current['direct_messages'], $previous['direct_messages']),
                    ],
                ],
                'lifetime' => $socialLifetime,
            ],
            'users' => [
                'by_status' => [
                    'active' => $snapshots['active_users'],
                    'pending' => $snapshots['pending_users'],
                    'deactivated' => $snapshots['deactivated_users'],
                ],
                'creators_period' => $creatorsCurrent,
                'joiners_period' => $joinersCurrent,
            ],
            'geo' => $geo,
            'health' => $health,
            'charts' => $charts,
            'snapshots' => $snapshots,
            // Legacy keys
            'today' => $today,
            'yesterday' => $yesterday,
            'deltas' => $this->legacyDeltas($today, $yesterday),
            'stats' => [
                'active_users' => $snapshots['active_users'],
                'pending_users' => $snapshots['pending_users'],
                'published_events' => $snapshots['published_events'],
                'pending_reports' => $snapshots['pending_reports'],
                'identity_documents' => $snapshots['identity_documents'],
                'cities' => $snapshots['cities'],
            ],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function snapshots(): array
    {
        return [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('status', AccountStatus::Active)->count(),
            'pending_users' => User::query()->where('status', AccountStatus::PendingDocuments)->count(),
            'deactivated_users' => User::query()->where('status', AccountStatus::Deactivated)->count(),
            'published_events' => Event::query()->where('status', EventStatus::Published)->count(),
            'draft_events' => Event::query()->where('status', EventStatus::Draft)->count(),
            'completed_events' => Event::query()->where('status', EventStatus::Completed)->count(),
            'cancelled_events' => Event::query()->where('status', EventStatus::Cancelled)->count(),
            'total_events' => Event::query()->count(),
            'pending_reports' => Report::query()->where('status', ReportStatus::Pending)->count(),
            'identity_documents' => \App\Models\IdentityDocument::query()->count(),
            'cities' => City::query()->where('is_active', true)->count(),
            'categories' => Category::query()->where('is_active', true)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function eventStatusBreakdown(): array
    {
        $rows = Event::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($row) {
                $status = $row->status instanceof EventStatus
                    ? $row->status->value
                    : (string) $row->status;

                return [$status => (int) $row->aggregate];
            });

        $out = [];
        foreach (EventStatus::cases() as $status) {
            $out[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $snapshots
     * @return list<array<string, mixed>>
     */
    private function actionCenter(array $snapshots): array
    {
        $items = [];

        $pendingReports = $snapshots['pending_reports'];
        $items[] = [
            'key' => 'pending_reports',
            'count' => $pendingReports,
            'priority' => $pendingReports > 0 ? 'high' : 'ok',
            'href' => route('admin.reports.index'),
            'label' => __('admin.action_pending_reports'),
            'description' => __('admin.action_pending_reports_desc'),
        ];

        $pendingUsers = $snapshots['pending_users'];
        $items[] = [
            'key' => 'pending_identity',
            'count' => $pendingUsers,
            'priority' => $pendingUsers > 10 ? 'medium' : ($pendingUsers > 0 ? 'low' : 'ok'),
            'href' => route('admin.users.index', ['status' => AccountStatus::PendingDocuments->value]),
            'label' => __('admin.action_pending_identity'),
            'description' => __('admin.action_pending_identity_desc'),
        ];

        $failedJobs = $this->failedJobsCount();
        $items[] = [
            'key' => 'failed_jobs',
            'count' => $failedJobs,
            'priority' => $failedJobs > 0 ? 'high' : 'ok',
            'href' => null,
            'label' => __('admin.action_failed_jobs'),
            'description' => __('admin.action_failed_jobs_desc'),
        ];

        // Drafts are informational — not an approval queue.
        $drafts = $snapshots['draft_events'];
        if ($drafts > 0) {
            $items[] = [
                'key' => 'draft_events',
                'count' => $drafts,
                'priority' => 'info',
                'href' => route('admin.events.index', ['status' => EventStatus::Draft->value]),
                'label' => __('admin.action_draft_events'),
                'description' => __('admin.action_draft_events_desc'),
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentActivity(?User $viewer): array
    {
        $feed = [];

        User::query()
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'name', 'public_id', 'created_at', 'status'])
            ->each(function (User $user) use (&$feed) {
                $feed[] = [
                    'type' => 'user_registered',
                    'at' => $user->created_at,
                    'title' => $user->name,
                    'meta' => $user->public_id,
                    'status' => $user->status instanceof AccountStatus ? $user->status->value : (string) $user->status,
                    'href' => route('admin.users.edit', $user),
                ];
            });

        Event::query()
            ->with(['creator:id,name', 'city'])
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'title', 'slug', 'status', 'created_by', 'city_id', 'created_at'])
            ->each(function (Event $event) use (&$feed) {
                $feed[] = [
                    'type' => 'event_created',
                    'at' => $event->created_at,
                    'title' => $event->title,
                    'meta' => $event->creator?->name,
                    'status' => $event->status instanceof EventStatus ? $event->status->value : (string) $event->status,
                    'href' => route('admin.events.edit', $event),
                ];
            });

        Report::query()
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'reason', 'status', 'created_at', 'reportable_type'])
            ->each(function (Report $report) use (&$feed) {
                $type = $report->reportable_type instanceof ReportableType
                    ? $report->reportable_type->value
                    : (string) $report->reportable_type;
                $feed[] = [
                    'type' => 'report_submitted',
                    'at' => $report->created_at,
                    'title' => __('admin.activity_report_title', [
                        'reason' => $report->reason instanceof \BackedEnum ? $report->reason->value : (string) $report->reason,
                    ]),
                    'meta' => $type,
                    'status' => $report->status instanceof ReportStatus ? $report->status->value : (string) $report->status,
                    'href' => route('admin.reports.index'),
                ];
            });

        Friendship::query()
            ->with(['requester:id,name', 'addressee:id,name'])
            ->where('status', FriendshipStatus::Accepted)
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->each(function (Friendship $friendship) use (&$feed) {
                $feed[] = [
                    'type' => 'friend_accepted',
                    'at' => $friendship->updated_at,
                    'title' => trim(($friendship->requester?->name ?? '?').' ↔ '.($friendship->addressee?->name ?? '?')),
                    'meta' => null,
                    'status' => FriendshipStatus::Accepted->value,
                    'href' => route('admin.users.index'),
                ];
            });

        if ($viewer?->isSuperAdmin()) {
            AdminAuditLog::query()
                ->with('actor:id,name')
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->each(function (AdminAuditLog $log) use (&$feed) {
                    $feed[] = [
                        'type' => 'admin_audit',
                        'at' => $log->created_at,
                        'title' => $log->action,
                        'meta' => $log->actor?->name,
                        'status' => 'audit',
                        'href' => route('admin.audit.index'),
                    ];
                });
        }

        usort($feed, function (array $a, array $b) {
            return ($b['at']?->getTimestamp() ?? 0) <=> ($a['at']?->getTimestamp() ?? 0);
        });

        return array_slice($feed, 0, 12);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentEvents(): array
    {
        return Event::query()
            ->with(['creator:id,name', 'city', 'category'])
            ->withCount('members')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'organizer' => $event->creator?->name,
                'city' => $event->city?->localizedName(),
                'starts_at' => $event->starts_at,
                'status' => $event->status instanceof EventStatus ? $event->status->value : (string) $event->status,
                'members_count' => (int) $event->members_count,
                'href' => route('admin.events.edit', $event),
            ])
            ->all();
    }

    /**
     * @return array{cities_by_events: list<array{label: string, count: int}>, cities_by_participants: list<array{label: string, count: int}>}
     */
    private function geographicInsights(ReportingPeriod $period): array
    {
        $byEvents = Event::query()
            ->select('city_id', DB::raw('count(*) as aggregate'))
            ->where('created_at', '>=', $period->start)
            ->where('created_at', '<', $period->endExclusive)
            ->whereNotNull('city_id')
            ->groupBy('city_id')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();

        $cityIds = $byEvents->pluck('city_id')->all();
        $cities = City::query()->whereIn('id', $cityIds)->get()->keyBy('id');

        $citiesByEvents = $byEvents->map(fn ($row) => [
            'label' => $cities->get($row->city_id)?->localizedName() ?? '#'.$row->city_id,
            'count' => (int) $row->aggregate,
        ])->values()->all();

        $byParticipants = EventMember::query()
            ->join('events', 'events.id', '=', 'event_members.event_id')
            ->select('events.city_id', DB::raw('count(*) as aggregate'))
            ->where('event_members.created_at', '>=', $period->start)
            ->where('event_members.created_at', '<', $period->endExclusive)
            ->whereNotNull('events.city_id')
            ->groupBy('events.city_id')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();

        $pCityIds = $byParticipants->pluck('city_id')->all();
        $pCities = City::query()->whereIn('id', $pCityIds)->get()->keyBy('id');

        $citiesByParticipants = $byParticipants->map(fn ($row) => [
            'label' => $pCities->get($row->city_id)?->localizedName() ?? '#'.$row->city_id,
            'count' => (int) $row->aggregate,
        ])->values()->all();

        return [
            'cities_by_events' => $citiesByEvents,
            'cities_by_participants' => $citiesByParticipants,
        ];
    }

    /**
     * @return array{accepted_friendships: int, pending_friendships: int}
     */
    private function socialLifetime(): array
    {
        return [
            'accepted_friendships' => Friendship::query()->where('status', FriendshipStatus::Accepted)->count(),
            'pending_friendships' => Friendship::query()->where('status', FriendshipStatus::Pending)->count(),
        ];
    }

    /**
     * @return array{failed_jobs: int, monitoring_note: string}
     */
    private function systemHealth(): array
    {
        return [
            'failed_jobs' => $this->failedJobsCount(),
            'monitoring_note' => __('admin.health_monitoring_gap'),
        ];
    }

    private function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function chartsForPeriod(ReportingPeriod $period): array
    {
        $days = $period->dayCount();
        $labels = [];
        $eventsPerDay = [];
        $usersPerDay = [];
        $participantsPerDay = [];
        $joinRequestsPerDay = [];
        $joinsAcceptedPerDay = [];
        $eventMessagesPerDay = [];
        $directMessagesPerDay = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $period->endExclusive->copy()->subDays($i + 1)->startOfDay();
            $key = $day->toDateString();
            $labels[] = $day->translatedFormat('M j');
            $eventsPerDay[$key] = 0;
            $usersPerDay[$key] = 0;
            $participantsPerDay[$key] = 0;
            $joinRequestsPerDay[$key] = 0;
            $joinsAcceptedPerDay[$key] = 0;
            $eventMessagesPerDay[$key] = 0;
            $directMessagesPerDay[$key] = 0;
        }

        $this->fillDayCounts(
            Event::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $eventsPerDay
        );
        $this->fillDayCounts(
            User::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $usersPerDay
        );
        $this->fillDayCounts(
            EventMember::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $participantsPerDay
        );
        $this->fillDayCounts(
            EventJoinRequest::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $joinRequestsPerDay
        );
        $this->fillDayCounts(
            \App\Models\EventMessage::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $eventMessagesPerDay
        );
        $this->fillDayCounts(
            \App\Models\DirectMessage::query()->where('created_at', '>=', $period->start)->where('created_at', '<', $period->endExclusive),
            $directMessagesPerDay
        );

        EventJoinRequest::query()
            ->where('status', JoinRequestStatus::Accepted)
            ->where(function ($q) use ($period) {
                $q->where(function ($inner) use ($period) {
                    $inner->whereNotNull('reviewed_at')
                        ->where('reviewed_at', '>=', $period->start)
                        ->where('reviewed_at', '<', $period->endExclusive);
                })->orWhere(function ($inner) use ($period) {
                    $inner->whereNull('reviewed_at')
                        ->where('updated_at', '>=', $period->start)
                        ->where('updated_at', '<', $period->endExclusive);
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

        $eventsByStatus = $this->eventStatusBreakdown();
        $statusLabels = [
            EventStatus::Draft->value => __('events.status_draft'),
            EventStatus::Published->value => __('events.status_published'),
            EventStatus::Cancelled->value => __('events.status_cancelled'),
            EventStatus::Completed->value => __('events.status_completed'),
        ];

        $eventsByCategory = Category::query()
            ->withCount(['events as events_count'])
            ->orderByDesc('events_count')
            ->limit(8)
            ->get()
            ->map(fn (Category $category) => [
                'label' => $category->localizedName(),
                'count' => (int) $category->events_count,
            ]);

        $userStatusCounts = User::query()
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $geoEvents = Event::query()
            ->select('city_id', DB::raw('count(*) as aggregate'))
            ->where('created_at', '>=', $period->start)
            ->where('created_at', '<', $period->endExclusive)
            ->whereNotNull('city_id')
            ->groupBy('city_id')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();
        $geoCityMap = City::query()->whereIn('id', $geoEvents->pluck('city_id'))->get()->keyBy('id');
        $geoLabels = $geoEvents->map(
            fn ($row) => $geoCityMap->get($row->city_id)?->localizedName() ?? '#'.$row->city_id
        )->values()->all();
        $geoValues = $geoEvents->map(fn ($row) => (int) $row->aggregate)->values()->all();

        return [
            'days' => $labels,
            'events_per_day' => array_values($eventsPerDay),
            'users_per_day' => array_values($usersPerDay),
            'participants_per_day' => array_values($participantsPerDay),
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
                (int) ($userStatusCounts[AccountStatus::Active->value] ?? 0),
                (int) ($userStatusCounts[AccountStatus::PendingDocuments->value] ?? 0),
                (int) ($userStatusCounts[AccountStatus::Deactivated->value] ?? 0),
            ],
            'geo_event_labels' => $geoLabels,
            'geo_event_values' => $geoValues,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, int>  $bucket
     */
    private function fillDayCounts($query, array &$bucket): void
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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    private function countBetween($query, string $column, Carbon $start, Carbon $end): int
    {
        return (int) $query->where($column, '>=', $start)->where($column, '<', $end)->count();
    }

    /**
     * @return array{key: string, value: int, previous: int|null, delta: int|null, percent: int|null, kind: string, href: string, hint: string, label: string}
     */
    private function kpi(string $key, int $value, ?int $previous, string $kind, string $href, string $hint): array
    {
        $delta = $previous === null ? null : $this->delta($value, $previous);

        return [
            'key' => $key,
            'label' => __('admin.kpi_'.$key),
            'value' => $value,
            'previous' => $previous,
            'delta' => $delta['value'] ?? null,
            'percent' => $delta['percent'] ?? null,
            'kind' => $kind,
            'href' => $href,
            'hint' => __('admin.'.$hint),
        ];
    }

    /**
     * @return array{value: int, percent: int|null}
     */
    private function delta(int $current, int $previous): array
    {
        $diff = $current - $previous;
        $percent = $previous > 0
            ? (int) round(($diff / $previous) * 100)
            : ($current > 0 ? null : null);

        return [
            'value' => $diff,
            'percent' => $percent,
        ];
    }

    /**
     * @param  array<string, int>  $today
     * @param  array<string, int>  $yesterday
     * @return array<string, array{value: int, percent: int|null}>
     */
    private function legacyDeltas(array $today, array $yesterday): array
    {
        $out = [];
        foreach ($today as $key => $value) {
            $out[$key] = $this->delta((int) $value, (int) ($yesterday[$key] ?? 0));
        }

        return $out;
    }
}
