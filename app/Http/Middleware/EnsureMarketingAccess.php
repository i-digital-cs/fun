<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMarketingAccess
{
    /**
     * SuperAdmin-only marketing module gate (Phase 1).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->platform_role?->canViewMarketing() !== true) {
            abort(403, __('marketing.unauthorized'));
        }

        if ($user->isDeactivated()) {
            abort(403, __('auth.deactivated'));
        }

        if (! config('marketing.enabled', true)) {
            abort(403, __('marketing.module_disabled'));
        }

        return $next($request);
    }
}
