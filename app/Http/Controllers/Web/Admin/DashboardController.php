<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminDashboardMetricsService $metrics): View
    {
        $payload = $metrics->build();

        return view('admin.dashboard', [
            'today' => $payload['today'],
            'yesterday' => $payload['yesterday'],
            'deltas' => $payload['deltas'],
            'stats' => $payload['stats'],
            'charts' => $payload['charts'],
        ]);
    }
}
