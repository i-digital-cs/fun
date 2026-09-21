<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Safety\ReviewReportRequest;
use App\Models\Report;
use App\Services\Safety\ReviewReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $reports = Report::query()
            ->with(['reporter:id,name,email'])
            ->when(
                $status !== '' && ReportStatus::tryFrom($status),
                fn ($q) => $q->where('status', $status)
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'statuses' => ReportStatus::cases(),
            'currentStatus' => $status,
        ]);
    }

    public function update(
        ReviewReportRequest $request,
        Report $report,
        ReviewReportService $service,
    ): RedirectResponse {
        try {
            $service->review(
                $request->user(),
                $report,
                ReportStatus::from($request->validated('status')),
                $request->validated('moderation_action'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['report' => $exception->getMessage()]);
        }

        return back()->with('status', __('safety.report_updated'));
    }
}
