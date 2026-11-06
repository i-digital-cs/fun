<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\EmailAuthCodeService;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request): View
    {
        // Optional same-origin returnTo → session intended (guest CTAs / Quick View).
        if (! $request->session()->has('url.intended')) {
            $safe = $this->safeIntendedUrl($request->query('returnTo'));
            if ($safe !== null) {
                $request->session()->put('url.intended', $safe);
            }
        }

        return view('auth.login');
    }

    /**
     * Accept only relative app paths or absolute URLs on APP_URL host.
     */
    private function safeIntendedUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate) || $candidate === '') {
            return null;
        }

        $candidate = trim($candidate);

        if (str_starts_with($candidate, '/') && ! str_starts_with($candidate, '//')) {
            if (str_contains($candidate, "\n") || str_contains($candidate, "\r")) {
                return null;
            }

            return url($candidate);
        }

        if (! str_starts_with($candidate, 'https://') && ! str_starts_with($candidate, 'http://')) {
            return null;
        }

        $app = parse_url((string) config('app.url'));
        $target = parse_url($candidate);
        if (! is_array($app) || ! is_array($target)) {
            return null;
        }

        $appHost = strtolower((string) ($app['host'] ?? ''));
        $targetHost = strtolower((string) ($target['host'] ?? ''));
        if ($appHost === '' || $targetHost === '' || $appHost !== $targetHost) {
            return null;
        }

        return $candidate;
    }

    public function store(
        LoginRequest $request,
        LoginUserService $loginUserService,
        EmailAuthCodeService $codes,
    ): RedirectResponse|JsonResponse {
        try {
            $user = $loginUserService->attempt($request->validated());
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['email' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['email' => $exception->getMessage()])->onlyInput('email');
        }

        if ($user->isDeactivated()) {
            $url = route('account.deactivated');

            return $request->expectsJson()
                ? response()->json(['redirect' => $url])
                : redirect()->to($url);
        }

        if ($user->email_verified_at === null) {
            try {
                $codes->issueAndSendIfAllowed(
                    $user,
                    EmailAuthPurpose::EmailVerify,
                    mailContext: 'login',
                );
            } catch (\App\Exceptions\DomainException) {
                // Existing code still valid; verify screen supports resend.
            }
            $request->session()->put('verification_email', $user->email);
            $url = route('verification.notice');

            return $request->expectsJson()
                ? response()->json([
                    'redirect' => $url,
                    'message' => __('auth.email_not_verified'),
                ])
                : redirect()->to($url)->with('status', __('auth.email_not_verified'));
        }

        if (! $user->isPlatformAdmin() && $user->needsIdentityDocuments()) {
            $url = route('identity.create');

            return $request->expectsJson()
                ? response()->json(['redirect' => $url])
                : redirect()->to($url);
        }

        $redirect = redirect()->intended(route($user->defaultHomeRoute()));

        return $request->expectsJson()
            ? response()->json(['redirect' => $redirect->getTargetUrl()])
            : $redirect;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
