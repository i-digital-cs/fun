<?php

namespace App\Http\Controllers\Web\Preview;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Isolated Living World procedural 3D feasibility POC — Cairo only.
 * Does NOT replace production Home / V3.5 / V4 / V5.
 */
class LivingWorldProceduralPreviewController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        if (! $this->mayPreview($request)) {
            abort(404);
        }

        return view('preview.living-world-procedural', [
            'styleUrl' => (string) config('living_map.style_url'),
            'attribution' => (string) config('living_map.attribution'),
            'seo' => [
                'title' => 'Living World Procedural 3D POC',
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
