<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectPublicPrefix
{
    /**
     * When the vhost document root is the project folder, /public/... is a
     * duplicate entry point that pollutes generated URLs. Send browsers to the
     * clean path instead.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $uri = $request->getRequestUri();
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if (! preg_match('#^/public(/|$)#i', $path)) {
            return $next($request);
        }

        $cleanPath = preg_replace('#^/public#i', '', $path) ?: '/';
        if ($cleanPath === '') {
            $cleanPath = '/';
        }

        $query = parse_url($uri, PHP_URL_QUERY);
        $target = $cleanPath.($query ? '?'.$query : '');

        return redirect()->to($target, 301);
    }
}
