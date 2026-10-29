<?php

namespace App\Http\Controllers\Web\Preview;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Isolated Living Map V3 prototype — does not replace production maps.
 */
class LivingMapV3PreviewController extends Controller
{
    public function __invoke(Request $request): View|Response
    {
        if (! $this->mayPreview($request)) {
            abort(404);
        }

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'slug']);

        return view('preview.living-map-v3', [
            'categories' => $categories,
            'styleUrl' => (string) config('living_map.style_url'),
            'attribution' => (string) config('living_map.attribution'),
            'seo' => [
                'title' => 'Living Map V3 Preview',
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

        // Owner / reviewer shareable link (Laravel signed URL).
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
