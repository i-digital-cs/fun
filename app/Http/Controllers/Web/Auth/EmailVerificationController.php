<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Exceptions\OtpCooldownException;
use App\Http\Controllers\Controller;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request, EmailAuthCodeService $codes): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->email_verified_at !== null) {
            return $this->postVerifyRedirectResponse($user);
        }

        $otp = $user !== null
            ? $codes->cooldownPayload($user, EmailAuthPurpose::EmailVerify)
            : [
                'retry_after_seconds' => 0,
                'resend_available_at' => now()->toIso8601String(),
                'expires_in_minutes' => $codes->expiresMinutes(),
            ];

        return view('auth.verify-email', [
            'email' => $user?->email ?? session('verification_email'),
            'otp' => $otp,
        ]);
    }

    public function send(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->jsonOrRedirect($request, __('auth.must_login_to_verify'), false);
        }

        if ($user->email_verified_at !== null) {
            $redirect = $this->postVerifyRedirectResponse($user);

            return $request->expectsJson()
                ? response()->json(['redirect' => $redirect->getTargetUrl()])
                : $redirect;
        }

        try {
            $result = $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);
        } catch (OtpCooldownException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'retry_after_seconds' => $exception->retryAfterSeconds,
                    'resend_available_at' => $exception->resendAvailableAt,
                ], 429)->withHeaders([
                    'Retry-After' => (string) $exception->retryAfterSeconds,
                ]);
            }

            return back()->withErrors(['code' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    ...$codes->cooldownPayload($user, EmailAuthPurpose::EmailVerify),
                ], 503);
            }

            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.code_sent'),
                ...$result->toArray(),
            ]);
        }

        return back()->with('status', __('auth.code_sent'));
    }

    public function verify(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->jsonOrRedirect($request, __('auth.must_login_to_verify'), false);
        }

        if ($user->email_verified_at !== null) {
            $redirect = $this->postVerifyRedirectResponse($user);

            return $request->expectsJson()
                ? response()->json(['redirect' => $redirect->getTargetUrl()])
                : $redirect;
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:4'],
        ]);

        try {
            $codes->verify($user, EmailAuthPurpose::EmailVerify, $validated['code']);
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['code' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        $request->session()->forget('verification_email');

        $redirect = $this->postVerifyRedirectResponse($user)
            ->with('status', __('auth.email_verified'));

        return $request->expectsJson()
            ? response()->json(['redirect' => $redirect->getTargetUrl()])
            : $redirect;
    }

    private function postVerifyRedirectResponse($user): RedirectResponse
    {
        if ($user->isDeactivated()) {
            return redirect()->to(route('account.deactivated'));
        }

        if (! $user->isPlatformAdmin() && $user->needsIdentityDocuments()) {
            // Keep session intended for after identity submit (NAV-W3).
            return redirect()->to(route('identity.create'));
        }

        return redirect()->intended(route($user->defaultHomeRoute()));
    }

    private function jsonOrRedirect(Request $request, string $message, bool $ok): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : 422);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
