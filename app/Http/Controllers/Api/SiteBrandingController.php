<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Http\JsonResponse;

class SiteBrandingController extends Controller
{
    public function __invoke(SiteSettingsService $settings): JsonResponse
    {
        return response()->json([
            'display_name' => $settings->displayName(),
            'logo_url' => $settings->logoUrl(),
        ]);
    }
}
