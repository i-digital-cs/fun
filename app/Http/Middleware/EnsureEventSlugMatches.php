<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEventSlugMatches
{
    /**
     * 301 to the canonical /events/{id}/{slug}/… URL when the slug segment is wrong.
     * Accepts legacy `slug` or localized `slug_ar` as valid inbound segments.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $event = $request->route('event');
        $slug = $request->route('slug');

        if (! $event instanceof Event || ! is_string($slug) || $slug === '') {
            return $next($request);
        }

        if (blank($event->slug)) {
            $event->forceFill([
                'slug' => Event::uniqueSlug((string) $event->title, (int) $event->id),
            ])->saveQuietly();
        }

        $accepted = [$event->slug];
        if (filled($event->slug_ar)) {
            $accepted[] = $event->slug_ar;
        }

        if (in_array($slug, $accepted, true)) {
            return $next($request);
        }

        $route = $request->route();
        if ($route === null || $route->getName() === null) {
            return $next($request);
        }

        $locale = app()->getLocale();
        $canonicalSlug = $event->slugForLocale($locale);
        $parameters = $route->parameters();
        $parameters['event'] = $event;
        $parameters['slug'] = $canonicalSlug;

        return redirect()->route($route->getName(), $parameters, 301);
    }
}
