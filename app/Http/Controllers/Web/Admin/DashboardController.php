<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\AccountStatus;
use App\Enums\EventStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\IdentityDocument;
use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $tz = config('app.timezone');
        $today = now($tz)->startOfDay();

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

        $dayLabels = [];
        $eventsPerDay = [];
        $usersPerDay = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $key = $day->toDateString();
            $dayLabels[] = $day->translatedFormat('M j');
            $eventsPerDay[$key] = 0;
            $usersPerDay[$key] = 0;
        }

        Event::query()
            ->where('created_at', '>=', $today->copy()->subDays(13))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->get()
            ->each(function ($row) use (&$eventsPerDay) {
                $day = Carbon::parse($row->day)->toDateString();
                if (array_key_exists($day, $eventsPerDay)) {
                    $eventsPerDay[$day] = (int) $row->aggregate;
                }
            });

        User::query()
            ->where('created_at', '>=', $today->copy()->subDays(13))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('count(*) as aggregate'))
            ->groupBy('day')
            ->get()
            ->each(function ($row) use (&$usersPerDay) {
                $day = Carbon::parse($row->day)->toDateString();
                if (array_key_exists($day, $usersPerDay)) {
                    $usersPerDay[$day] = (int) $row->aggregate;
                }
            });

        $statusLabels = [
            EventStatus::Draft->value => __('events.status_draft'),
            EventStatus::Published->value => __('events.status_published'),
            EventStatus::Cancelled->value => __('events.status_cancelled'),
            EventStatus::Completed->value => __('events.status_completed'),
        ];

        return view('admin.dashboard', [
            'stats' => [
                'active_users' => User::query()->where('status', AccountStatus::Active)->count(),
                'pending_users' => User::query()->where('status', AccountStatus::PendingDocuments)->count(),
                'published_events' => Event::query()->where('status', EventStatus::Published)->count(),
                'pending_reports' => Report::query()->where('status', ReportStatus::Pending)->count(),
                'identity_documents' => IdentityDocument::query()->count(),
                'cities' => City::query()->where('is_active', true)->count(),
            ],
            'charts' => [
                'days' => $dayLabels,
                'events_per_day' => array_values($eventsPerDay),
                'users_per_day' => array_values($usersPerDay),
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
            ],
        ]);
    }
}
