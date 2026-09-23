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
    public function create(): View
    {
        return view('auth.login');
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
            $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);
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
