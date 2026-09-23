<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Admin\SiteSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteBrandingController extends Controller
{
    public function __invoke(Request $request, SiteSettingsService $settings): JsonResponse
    {
        $locale = $request->query('locale')
            ?: $request->header('Accept-Language')
            ?: app()->getLocale();

        if (is_string($locale) && str_contains($locale, ',')) {
            $locale = trim(explode(',', $locale)[0]);
        }
        if (is_string($locale) && str_contains($locale, '-')) {
            $locale = strtolower(explode('-', $locale)[0]);
        }
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : 'en';

        return response()->json([
            'display_name' => $settings->displayName(),
            'logo_url' => $settings->logoUrl(),
            'app' => $settings->appUpdatePayload($locale),
        ]);
    }
}
