<?php

namespace App\Http\Controllers\Web\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingOverviewService;
use Illuminate\View\View;

class MarketingOverviewController extends Controller
{
    public function __invoke(MarketingOverviewService $overview): View
    {
        return view('admin.marketing.overview', [
            'overview' => $overview->build(),
        ]);
    }
}
