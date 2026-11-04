<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\RegistrationBlockedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\EmailAuthCodeService;
use App\Services\Auth\RegisterUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        RegisterRequest $request,
        RegisterUserService $registerUserService,
        EmailAuthCodeService $codes,
    ): RedirectResponse|JsonResponse {
        try {
            $user = $registerUserService->register($request->validated(), $request);
        } catch (RegistrationBlockedException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], $exception->status);
            }

            return back()
                ->withInput($request->except(['password', 'password_confirmation', 'cf-turnstile-response', 'turnstile_token']))
                ->withErrors(['email' => $exception->getMessage()]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->filled('locale')) {
            $request->session()->put('locale', $request->string('locale')->toString());
            $user->forceFill(['locale' => $request->string('locale')->toString()])->save();
        }

        try {
            $codes->issueAndSendIfAllowed(
                $user,
                EmailAuthPurpose::EmailVerify,
                mailContext: 'register',
            );
        } catch (\App\Exceptions\DomainException) {
            // Account exists; user can resend from the verify screen.
        }
        $request->session()->put('verification_email', $user->email);

        $url = route('verification.notice');

        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => $url,
                'message' => __('auth.registered_verify'),
            ]);
        }

        return redirect()->to($url)->with('status', __('auth.registered_verify'));
    }
}
