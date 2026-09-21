<?php

namespace App\Http\Middleware;

use App\Enums\PlatformRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->platform_role instanceof PlatformRole) {
            abort(403, __('admin.unauthorized'));
        }

        if ($user->isDeactivated()) {
            abort(403, __('auth.deactivated'));
        }

        return $next($request);
    }
}
