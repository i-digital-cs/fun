<?php

namespace App\Http\Controllers\Web\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginUserService;
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

    public function store(LoginRequest $request, LoginUserService $loginUserService): RedirectResponse
    {
        try {
            $user = $loginUserService->attempt($request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors(['email' => $exception->getMessage()])->onlyInput('email');
        }

        if ($user->isDeactivated()) {
            return redirect()->route('account.deactivated');
        }

        if (! $user->isPlatformAdmin() && $user->needsIdentityDocuments()) {
            return redirect()->route('identity.create');
        }

        return redirect()->intended(route($user->defaultHomeRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
