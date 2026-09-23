<?php

namespace App\Http\Controllers\Web\Auth;

use App\Enums\EmailAuthPurpose;
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
        $user = $registerUserService->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->filled('locale')) {
            $request->session()->put('locale', $request->string('locale')->toString());
            $user->forceFill(['locale' => $request->string('locale')->toString()])->save();
        }

        $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);
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
