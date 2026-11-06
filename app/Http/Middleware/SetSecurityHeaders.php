<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Baseline browser hardening for HTML/document responses.
     * CSP ships Report-Only so Vite, maps, Turnstile, and Pusher keep working.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldSkip($request, $response)) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(self), geolocation=(self), payment=(), usb=(), interest-cohort=()',
            false
        );
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);

        if (config('security.hsts.enabled') && $request->isSecure()) {
            $hsts = 'max-age='.(int) config('security.hsts.max_age', 31536000);
            if (config('security.hsts.include_subdomains')) {
                $hsts .= '; includeSubDomains';
            }
            if (config('security.hsts.preload')) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts, false);
        }

        if (config('security.csp_report_only')) {
            $csp = $this->buildCsp($request);
            if ($csp !== '') {
                $response->headers->set('Content-Security-Policy-Report-Only', $csp, false);
            }
        }

        return $response;
    }

    private function shouldSkip(Request $request, Response $response): bool
    {
        if ($request->is('api/*') || $request->is('up') || $request->is('broadcasting/*')) {
            return true;
        }

        $disposition = (string) $response->headers->get('Content-Disposition', '');
        if (str_contains(strtolower($disposition), 'attachment')) {
            return true;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType !== '' && ! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'text/plain')) {
            // Skip binary / JSON API-ish payloads that happen to hit the web stack.
            if (str_contains($contentType, 'application/json') || str_contains($contentType, 'image/') || str_contains($contentType, 'audio/') || str_contains($contentType, 'video/') || str_contains($contentType, 'application/octet-stream')) {
                return true;
            }
        }

        return false;
    }

    private function buildCsp(Request $request): string
    {
        $script = [
            "'self'",
            'https://challenges.cloudflare.com',
            'https://unpkg.com',
            'https://api.mapbox.com',
            'https://cdn.jsdelivr.net',
        ];
        $style = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.bunny.net',
            'https://unpkg.com',
            'https://api.mapbox.com',
        ];
        $font = ["'self'", 'https://fonts.bunny.net', 'data:'];
        $img = ["'self'", 'data:', 'blob:', 'https:'];
        $connect = [
            "'self'",
            'https://*.pusher.com',
            'wss://*.pusher.com',
            'https://sockjs-mt1.pusher.com',
            'https://ws-mt1.pusher.com',
            'https://unpkg.com',
            'https://*.openfreemap.org',
            'https://tile.openstreetmap.org',
            'https://*.tile.openstreetmap.org',
            'https://api.mapbox.com',
            'https://events.mapbox.com',
            'https://*.mapbox.com',
            'https://challenges.cloudflare.com',
        ];
        $frame = ["'self'", 'https://challenges.cloudflare.com'];
        $worker = ["'self'", 'blob:'];
        $media = ["'self'", 'blob:', 'https:'];

        if (app()->environment('local')) {
            $script[] = 'http://localhost:5173';
            $script[] = 'http://127.0.0.1:5173';
            $connect[] = 'http://localhost:5173';
            $connect[] = 'http://127.0.0.1:5173';
            $connect[] = 'ws://localhost:5173';
            $connect[] = 'ws://127.0.0.1:5173';
            $style[] = 'http://localhost:5173';
            $style[] = 'http://127.0.0.1:5173';
        }

        $this->mergeExtras($script, (string) config('security.csp.extra_script_src', ''));
        $this->mergeExtras($connect, (string) config('security.csp.extra_connect_src', ''));
        $this->mergeExtras($img, (string) config('security.csp.extra_img_src', ''));

        $parts = [
            'default-src \'self\'',
            'base-uri \'self\'',
            'object-src \'none\'',
            'frame-ancestors \'self\'',
            'form-action \'self\'',
            'script-src '.$this->join($script),
            'style-src '.$this->join($style),
            'font-src '.$this->join($font),
            'img-src '.$this->join($img),
            'connect-src '.$this->join($connect),
            'frame-src '.$this->join($frame),
            'worker-src '.$this->join($worker),
            'media-src '.$this->join($media),
        ];

        if ($request->isSecure()) {
            $parts[] = 'upgrade-insecure-requests';
        }

        $reportUri = trim((string) config('security.csp.report_uri', ''));
        if ($reportUri !== '') {
            $parts[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $parts);
    }

    /**
     * @param  list<string>  $bucket
     */
    private function mergeExtras(array &$bucket, string $csv): void
    {
        foreach (preg_split('/\s*,\s*/', $csv) ?: [] as $host) {
            $host = trim($host);
            if ($host !== '' && ! in_array($host, $bucket, true)) {
                $bucket[] = $host;
            }
        }
    }

    /**
     * @param  list<string>  $values
     */
    private function join(array $values): string
    {
        return implode(' ', array_values(array_unique($values)));
    }
}
