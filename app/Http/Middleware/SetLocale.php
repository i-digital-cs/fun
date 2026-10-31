<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $pathLocale = null;
        $segment = $request->segment(1);
        if (in_array($segment, ['ar', 'en'], true)) {
            $pathLocale = $segment;
            $request->attributes->set('locale_prefix', $segment);
        }

        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;

        // URL prefix wins so cookieless crawlers get the intended language.
        $locale = $pathLocale
            ?? $request->user()?->locale
            ?? $sessionLocale
            ?? $request->getPreferredLanguage(['en', 'ar'])
            ?? config('app.locale');

        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        if ($pathLocale !== null && $request->hasSession()) {
            $request->session()->put('locale', $pathLocale);
        }

        return $next($request);
    }
}
