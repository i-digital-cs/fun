<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(GoogleAuthService $googleAuthService): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => __('auth.google_failed')]);
        }

        $user = $googleAuthService->findOrCreateFromGoogle($googleUser, session('locale'));
        $googleAuthService->login($user);

        if ($user->isDeactivated()) {
            return redirect()->route('account.deactivated');
        }

        if ($user->needsIdentityDocuments()) {
            return redirect()->route('identity.create');
        }

        return redirect()->intended(route('dashboard'));
    }
}
