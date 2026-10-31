<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prefer explicit /ar or /en URL prefixes over session/Accept-Language
 * so cookieless crawlers receive the intended language HTML.
 */
class SetLocaleFromPath
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $segment = $request->segment(1);

        if (in_array($segment, ['ar', 'en'], true)) {
            app()->setLocale($segment);
            if ($request->hasSession()) {
                $request->session()->put('locale', $segment);
            }
            $request->attributes->set('locale_prefix', $segment);
        }

        return $next($request);
    }
}
