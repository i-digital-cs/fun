<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user?->email_verified_at !== null) {
            return redirect()->intended($this->postVerifyRedirect($user));
        }

        return view('auth.verify-email', [
            'email' => $user?->email ?? session('verification_email'),
        ]);
    }

    public function send(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return $this->jsonOrRedirect($request, __('auth.must_login_to_verify'), false);
        }

        if ($user->email_verified_at !== null) {
            $url = $this->postVerifyRedirect($user);

            return $request->expectsJson()
                ? response()->json(['redirect' => $url])
                : redirect()->to($url);
        }

        $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.code_sent')]);
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
            $url = $this->postVerifyRedirect($user);

            return $request->expectsJson()
                ? response()->json(['redirect' => $url])
                : redirect()->to($url);
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

        $url = $this->postVerifyRedirect($user);

        return $request->expectsJson()
            ? response()->json(['redirect' => $url])
            : redirect()->to($url)->with('status', __('auth.email_verified'));
    }

    private function postVerifyRedirect($user): string
    {
        if ($user->isDeactivated()) {
            return route('account.deactivated');
        }

        if (! $user->isPlatformAdmin() && $user->needsIdentityDocuments()) {
            return route('identity.create');
        }

        return route($user->defaultHomeRoute());
    }

    private function jsonOrRedirect(Request $request, string $message, bool $ok): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $ok ? 200 : 422);
        }

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
