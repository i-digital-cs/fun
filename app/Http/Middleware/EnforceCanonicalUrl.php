<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCanonicalUrl
{
    /**
     * One-hop canonicalization for GET/HEAD:
     * strip an accidental /public prefix, drop a trailing slash, and send the
     * configured host (and its www variant) to APP_URL's scheme and host.
     *
     * Other hosts (local tests, alternate vhosts) are left unchanged unless the
     * path itself is a /public or trailing-slash duplicate, in which case the
     * redirect target is still APP_URL.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = '/'.ltrim($request->getPathInfo(), '/');

        if (str_starts_with($path, '/.well-known/')) {
            return $next($request);
        }

        $target = $this->targetFor($request, $path);

        if ($target === null) {
            return $next($request);
        }

        return redirect()->away($target, 301);
    }

    private function targetFor(Request $request, string $path): ?string
    {
        $canonical = $this->canonicalOrigin();
        if ($canonical === null) {
            return null;
        }

        $cleanPath = $path;
        if (preg_match('#^/public(/|$)#i', $cleanPath)) {
            $cleanPath = preg_replace('#^/public#i', '', $cleanPath) ?: '/';
            if ($cleanPath === '') {
                $cleanPath = '/';
            }
        }

        if ($cleanPath !== '/' && str_ends_with($cleanPath, '/')) {
            $cleanPath = rtrim($cleanPath, '/') ?: '/';
        }

        $host = $request->getHost();
        $onCanonicalHost = strcasecmp($host, $canonical['host']) === 0
            || strcasecmp($host, 'www.'.$canonical['host']) === 0;

        $pathChanged = $cleanPath !== $path;
        $schemeChanged = $request->getScheme() !== $canonical['scheme'];
        $hostChanged = strcasecmp($host, $canonical['host']) !== 0;

        if (! $pathChanged && ! ($onCanonicalHost && ($schemeChanged || $hostChanged))) {
            return null;
        }

        $query = $request->getQueryString();
        $target = $canonical['scheme'].'://'.$canonical['host'].$cleanPath.($query !== null && $query !== '' ? '?'.$query : '');

        $current = $request->getSchemeAndHttpHost().$request->getRequestUri();

        return $target === $current ? null : $target;
    }

    /**
     * @return array{scheme: string, host: string}|null
     */
    private function canonicalOrigin(): ?array
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $parts = parse_url($appUrl);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (! is_string($scheme) || ! is_string($host) || $scheme === '' || $host === '') {
            return null;
        }

        return ['scheme' => strtolower($scheme), 'host' => $host];
    }
}
