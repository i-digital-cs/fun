<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReportStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Safety\ReviewReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\Safety\ReviewReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportModerationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->string('status')->toString();

        $reports = Report::query()
            ->with(['reporter:id,name'])
            ->when(
                $status !== '' && ReportStatus::tryFrom($status),
                fn ($q) => $q->where('status', $status)
            )
            ->latest()
            ->paginate(20);

        return ReportResource::collection($reports);
    }

    public function update(
        ReviewReportRequest $request,
        Report $report,
        ReviewReportService $service,
    ): JsonResponse {
        try {
            $updated = $service->review(
                $request->user(),
                $report,
                ReportStatus::from($request->validated('status')),
                $request->validated('moderation_action'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('safety.report_updated'),
            'data' => (new ReportResource($updated->load('reporter:id,name')))->resolve(),
        ]);
    }
}
