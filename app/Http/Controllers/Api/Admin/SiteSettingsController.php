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
            'data' => $this->payload($service, $settings),
        ]);
    }

    public function update(
        UpdateSiteSettingsRequest $request,
        SiteSettingsService $service,
    ): JsonResponse {
        try {
            $settings = $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.settings_saved'),
            'data' => $this->payload($service, $settings),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SiteSettingsService $service, $settings): array
    {
        return [
            'display_name' => $settings->display_name,
            'logo_url' => $service->logoUrl(),
            'android_latest_version' => $settings->android_latest_version,
            'android_min_version' => $settings->android_min_version,
            'ios_latest_version' => $settings->ios_latest_version,
            'ios_min_version' => $settings->ios_min_version,
            'force_update' => (bool) $settings->force_update,
            'android_store_url' => $settings->android_store_url,
            'ios_store_url' => $settings->ios_store_url,
            'update_message_en' => $settings->update_message_en,
            'update_message_ar' => $settings->update_message_ar,
            'updated_at' => $settings->updated_at?->toIso8601String(),
        ];
    }
}
