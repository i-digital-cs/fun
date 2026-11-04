<?php

namespace App\Http\Controllers\Web\Preview;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Isolated Mapbox Standard 3D provider trial — preview only.
 * Does NOT replace production Home / V3.5 / V4 / V5 / procedural POC.
 */
class MapboxStandardTrialPreviewController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        if (! $this->mayPreview($request)) {
            abort(404);
        }

        $token = trim((string) config('services.mapbox.public_token', ''));
        $tokenOk = $token !== '' && str_starts_with($token, 'pk.');
        // Never pass secret tokens to the browser.
        if ($token !== '' && str_starts_with($token, 'sk.')) {
            $tokenOk = false;
            $token = '';
        }

        $city = strtolower((string) $request->query('city', 'cairo'));
        if (! in_array($city, ['cairo', 'port-said', 'alexandria'], true)) {
            $city = 'cairo';
        }

        return view('preview.mapbox-standard-trial', [
            'mapboxToken' => $tokenOk ? $token : '',
            'tokenConfigured' => $tokenOk,
            'initialCity' => $city,
            'seo' => [
                'title' => 'Mapbox Standard 3D Trial',
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
