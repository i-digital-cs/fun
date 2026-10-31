<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardAnalyticsService;
use App\Support\Reporting\ReportingPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardAnalyticsService $analytics): View
    {
        $period = ReportingPeriod::fromRequest($request);
        $payload = $analytics->build($period, $request->user());

        return view('admin.dashboard', [
            'period' => $payload['period'],
            'generatedAt' => $payload['generated_at'],
            'kpis' => $payload['kpis'],
            'actionCenter' => $payload['action_center'],
            'activityFeed' => $payload['activity_feed'],
            'eventStatus' => $payload['event_status'],
            'eventOps' => $payload['event_ops'],
            'recentEvents' => $payload['recent_events'],
            'social' => $payload['social'],
            'usersBlock' => $payload['users'],
            'geo' => $payload['geo'],
            'health' => $payload['health'],
            'charts' => $payload['charts'],
            'snapshots' => $payload['snapshots'],
            // Legacy keys consumed by AdminPanelTest
            'today' => $payload['today'],
            'yesterday' => $payload['yesterday'],
            'deltas' => $payload['deltas'],
            'stats' => $payload['stats'],
        ]);
    }
}
