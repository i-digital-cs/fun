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
            // Public Reverb client options only (never secret / app id).
            'broadcast' => $this->broadcastClientConfig(),
        ]);
    }

    /**
     * @return array{enabled: bool, key: ?string, host: ?string, port: ?int, scheme: ?string}|null
     */
    private function broadcastClientConfig(): ?array
    {
        $driver = (string) config('broadcasting.default');
        if ($driver !== 'reverb') {
            return ['enabled' => false, 'key' => null, 'host' => null, 'port' => null, 'scheme' => null];
        }

        $key = config('broadcasting.connections.reverb.key');
        $host = config('broadcasting.connections.reverb.options.host');
        $port = config('broadcasting.connections.reverb.options.port');
        $scheme = config('broadcasting.connections.reverb.options.scheme');

        $enabled = is_string($key) && $key !== ''
            && is_string($host) && $host !== '';

        return [
            'enabled' => $enabled,
            'key' => $enabled ? $key : null,
            'host' => $enabled ? $host : null,
            'port' => $enabled ? (int) ($port ?: 80) : null,
            'scheme' => $enabled ? (string) ($scheme ?: 'https') : null,
        ];
    }
}
