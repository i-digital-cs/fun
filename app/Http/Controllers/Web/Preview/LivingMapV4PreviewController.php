<?php

namespace App\Http\Controllers\Web\Preview;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Isolated Living Map V4.0 hybrid cinematic prototype — does NOT replace production Home/Discover.
 */
class LivingMapV4PreviewController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        if (! $this->mayPreview($request)) {
            abort(404);
        }

        // Real Port Said centroid for Discovery API origin (Navigate + filters). Scene art is illustrative.
        $demoLat = 31.2653;
        $demoLng = 32.3019;
        $demoKm = 25;

        return view('preview.living-map-v4', [
            'styleUrl' => (string) config('living_map.style_url'),
            'attribution' => (string) config('living_map.attribution'),
            'latitude' => $demoLat,
            'longitude' => $demoLng,
            'distanceKm' => $demoKm,
            'discoveryMode' => 'manual',
            'locationLabel' => app()->getLocale() === 'ar' ? 'بورسعيد' : 'Port Said',
            'cityId' => 11,
            'seo' => [
                'title' => 'Living Map V4 Preview',
                'robots' => 'noindex,nofollow',
                'url' => url()->current(),
            ],
        ]);
    }

    private function mayPreview(Request $request): bool
    {
        if ($request->user() !== null) {
            return true;
        }

        if ($request->hasValidSignature()) {
            return true;
        }

        $token = (string) config('living_map.preview_token');
        if ($token !== '' && hash_equals($token, (string) $request->query('preview_token', ''))) {
            return true;
        }

        return (bool) config('app.debug');
    }
}
