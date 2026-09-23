<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->email_verified_at === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('auth.email_not_verified'),
                    'redirect' => route('verification.notice'),
                ], 409);
            }

            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
