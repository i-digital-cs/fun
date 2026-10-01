<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function show(Request $request, string $metric, AdminDashboardMetricsService $metrics): View
    {
        abort_unless($metrics->isValidMetric($metric), 404);

        $day = $request->string('day')->toString() === 'yesterday' ? 'yesterday' : 'today';
        $window = $metrics->resolveDayWindow($day);
        $counts = $metrics->periodCounts($window['start'], $window['end']);
        $rows = $metrics->paginateMetric($metric, $window['start'], $window['end']);

        return view('admin.activity.show', [
            'metric' => $metric,
            'metricKeys' => $metrics->metricKeys(),
            'day' => $window['day'],
            'dayLabel' => $window['label'],
            'counts' => $counts,
            'rows' => $rows,
            'total' => (int) ($counts[$metric] ?? 0),
        ]);
    }
}
