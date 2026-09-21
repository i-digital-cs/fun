<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class SiteSettingsController extends Controller
{
    public function show(SiteSettingsService $service): JsonResponse
    {
        abort_unless(request()->user()?->isSuperAdmin(), 403);

        $settings = $service->current();

        return response()->json([
            'data' => [
                'display_name' => $settings->display_name,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(
        UpdateSiteSettingsRequest $request,
        SiteSettingsService $service,
    ): JsonResponse {
        try {
            $settings = $service->updateDisplayName(
                $request->user(),
                $request->validated('display_name')
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.settings_saved'),
            'data' => [
                'display_name' => $settings->display_name,
                'updated_at' => $settings->updated_at?->toIso8601String(),
            ],
        ]);
    }
}
