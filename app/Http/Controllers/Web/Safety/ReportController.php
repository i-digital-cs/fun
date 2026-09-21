<?php

namespace App\Http\Controllers\Web\Safety;

use App\Enums\ReportableType;
use App\Enums\ReportReason;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Safety\StoreReportRequest;
use App\Services\Safety\CreateReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(): View
    {
        return view('safety.report', [
            'types' => ReportableType::cases(),
            'reasons' => ReportReason::cases(),
            'prefill' => [
                'reportable_type' => request('reportable_type'),
                'reportable_id' => request('reportable_id'),
            ],
        ]);
    }

    public function store(StoreReportRequest $request, CreateReportService $service): RedirectResponse
    {
        try {
            $service->create(
                $request->user(),
                ReportableType::from($request->validated('reportable_type')),
                (int) $request->validated('reportable_id'),
                ReportReason::from($request->validated('reason')),
                $request->validated('description'),
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['report' => $exception->getMessage()]);
        }

        return back()->with('status', __('safety.report_submitted'));
    }
}
