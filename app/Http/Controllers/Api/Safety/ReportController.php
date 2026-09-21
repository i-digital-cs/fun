<?php

namespace App\Http\Controllers\Api\Safety;

use App\Enums\ReportableType;
use App\Enums\ReportReason;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Safety\StoreReportRequest;
use App\Http\Resources\ReportResource;
use App\Services\Safety\CreateReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, CreateReportService $service): JsonResponse
    {
        try {
            $report = $service->create(
                $request->user(),
                ReportableType::from($request->validated('reportable_type')),
                (int) $request->validated('reportable_id'),
                ReportReason::from($request->validated('reason')),
                $request->validated('description'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return (new ReportResource($report->load('reporter:id,name')))
            ->response()
            ->setStatusCode(201);
    }
}
