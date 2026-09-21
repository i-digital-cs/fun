<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if ($user->status === AccountStatus::Deactivated) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.deactivated')], 403);
            }

            return redirect()->route('account.deactivated');
        }

        if ($user->status === AccountStatus::PendingDocuments) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('auth.pending_documents')], 403);
            }

            return redirect()->route('identity.create');
        }

        return $next($request);
    }
}
