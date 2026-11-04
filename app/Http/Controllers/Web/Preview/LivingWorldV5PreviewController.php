<?php

namespace App\Http\Controllers\Web\Preview;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Isolated Living World V5 geographic cinematic feasibility prototype.
 * Does NOT replace production Home / Discover / V3.5 / V4.
 */
class LivingWorldV5PreviewController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        if (! $this->mayPreview($request)) {
            abort(404);
        }

        $city = strtolower((string) $request->query('city', 'cairo'));
        if (! in_array($city, ['cairo', 'port-said', 'alexandria'], true)) {
            $city = 'cairo';
        }

        return view('preview.living-world-v5', [
            'styleUrl' => (string) config('living_map.style_url'),
            'attribution' => (string) config('living_map.attribution'),
            'initialCity' => $city,
            'seo' => [
                'title' => 'Living World V5 Feasibility',
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
