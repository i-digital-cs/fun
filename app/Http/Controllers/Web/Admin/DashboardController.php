<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\IdentityDocument;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'events' => Event::query()->count(),
                'pending_reports' => Report::query()->where('status', ReportStatus::Pending)->count(),
                'identity_documents' => IdentityDocument::query()->count(),
                'cities' => City::query()->count(),
                'categories' => Category::query()->count(),
            ],
        ]);
    }
}
