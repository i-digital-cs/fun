<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\RegisterUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterUserService $registerUserService): RedirectResponse
    {
        $user = $registerUserService->register($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        if ($request->filled('locale')) {
            $request->session()->put('locale', $request->string('locale')->toString());
            $user->forceFill(['locale' => $request->string('locale')->toString()])->save();
        }

        return redirect()->route('identity.create')
            ->with('status', __('auth.registered'));
    }
}
