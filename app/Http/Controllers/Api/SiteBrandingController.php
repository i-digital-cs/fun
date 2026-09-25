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
            // Public client options only (never secret / app id).
            'broadcast' => $this->broadcastClientConfig(),
        ]);
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: ?string,
     *     key: ?string,
     *     cluster: ?string,
     *     host: ?string,
     *     port: ?int,
     *     scheme: ?string
     * }
     */
    private function broadcastClientConfig(): array
    {
        $driver = (string) config('broadcasting.default');

        if ($driver === 'pusher') {
            $key = config('broadcasting.connections.pusher.key');
            $cluster = config('broadcasting.connections.pusher.options.cluster');
            $enabled = is_string($key) && $key !== '';

            return [
                'enabled' => $enabled,
                'driver' => 'pusher',
                'key' => $enabled ? $key : null,
                'cluster' => $enabled ? (is_string($cluster) && $cluster !== '' ? $cluster : 'mt1') : null,
                'host' => null,
                'port' => null,
                'scheme' => $enabled ? 'https' : null,
            ];
        }

        if ($driver === 'reverb') {
            $key = config('broadcasting.connections.reverb.key');
            $host = config('broadcasting.connections.reverb.options.host');
            $port = config('broadcasting.connections.reverb.options.port');
            $scheme = config('broadcasting.connections.reverb.options.scheme');

            $enabled = is_string($key) && $key !== ''
                && is_string($host) && $host !== '';

            return [
                'enabled' => $enabled,
                'driver' => 'reverb',
                'key' => $enabled ? $key : null,
                'cluster' => null,
                'host' => $enabled ? $host : null,
                'port' => $enabled ? (int) ($port ?: 80) : null,
                'scheme' => $enabled ? (string) ($scheme ?: 'https') : null,
            ];
        }

        return [
            'enabled' => false,
            'driver' => null,
            'key' => null,
            'cluster' => null,
            'host' => null,
            'port' => null,
            'scheme' => null,
        ];
    }
}
