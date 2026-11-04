<?php

namespace App\Http\Controllers\Web\Admin\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Marketing\MarketingOverviewService;
use Illuminate\View\View;

class MarketingIntegrationController extends Controller
{
    public function index(MarketingOverviewService $overview): View
    {
        $data = $overview->build();

        return view('admin.marketing.integrations', [
            'integrations' => $data['integrations'],
            'phase' => $data['phase'],
        ]);
    }
}
