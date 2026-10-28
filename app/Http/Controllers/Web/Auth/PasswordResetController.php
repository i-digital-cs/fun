<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', strtolower($validated['email']))->first();

        // Always look successful to avoid email enumeration.
        // Social-only accounts (null password) can also receive a code to set one.
        if ($user !== null) {
            $codes->issueAndSend($user, EmailAuthPurpose::PasswordReset);
            $request->session()->put('password_reset_email', $user->email);
        }

        $url = route('password.verify');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('auth.reset_code_sent'),
                'redirect' => $url,
            ]);
        }

        return redirect()->to($url)->with('status', __('auth.reset_code_sent'));
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-reset-code', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function verify(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! is_string($email) || $email === '') {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.reset_session_expired')], 422)
                : redirect()->route('password.request')->withErrors(['email' => __('auth.reset_session_expired')]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:4'],
        ]);

        $user = User::query()->where('email', strtolower($email))->first();

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.invalid_code')], 422)
                : back()->withErrors(['code' => __('auth.invalid_code')]);
        }

        try {
            $codes->verify($user, EmailAuthPurpose::PasswordReset, $validated['code']);
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['code' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        $request->session()->put('password_reset_user_id', $user->id);
        $request->session()->put('password_reset_until', now()->addMinutes(15)->timestamp);
        $request->session()->forget('password_reset_email');

        $url = route('password.reset');

        return $request->expectsJson()
            ? response()->json(['redirect' => $url])
            : redirect()->to($url);
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $this->resetSessionValid($request)) {
            return redirect()->route('password.request')->withErrors(['email' => __('auth.reset_session_expired')]);
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request): RedirectResponse|JsonResponse
    {
        if (! $this->resetSessionValid($request)) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.reset_session_expired')], 422)
                : redirect()->route('password.request')->withErrors(['email' => __('auth.reset_session_expired')]);
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->find($request->session()->get('password_reset_user_id'));

        if ($user === null) {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.reset_session_expired')], 422)
                : redirect()->route('password.request');
        }

        $user->forceFill([
            'password' => $validated['password'],
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $request->session()->forget(['password_reset_user_id', 'password_reset_until', 'password_reset_email']);

        Auth::login($user);
        $request->session()->regenerate();

        $url = $user->needsIdentityDocuments()
            ? route('identity.create')
            : route($user->defaultHomeRoute());

        return $request->expectsJson()
            ? response()->json(['redirect' => $url])
            : redirect()->to($url)->with('status', __('auth.password_reset'));
    }

    public function resend(Request $request, EmailAuthCodeService $codes): RedirectResponse|JsonResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! is_string($email) || $email === '') {
            return $request->expectsJson()
                ? response()->json(['message' => __('auth.reset_session_expired')], 422)
                : redirect()->route('password.request');
        }

        $user = User::query()->where('email', strtolower($email))->first();

        if ($user !== null) {
            $codes->issueAndSend($user, EmailAuthPurpose::PasswordReset);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => __('auth.reset_code_sent')]);
        }

        return back()->with('status', __('auth.reset_code_sent'));
    }

    private function resetSessionValid(Request $request): bool
    {
        $userId = $request->session()->get('password_reset_user_id');
        $until = $request->session()->get('password_reset_until');

        return is_numeric($userId) && is_numeric($until) && (int) $until >= now()->timestamp;
    }
}
