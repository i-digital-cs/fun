<?php

namespace App\Http\Controllers\Web\Profile;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\Category;
use App\Services\Profile\UpdateProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->get(),
            'appName' => site_display_name(),
        ]);
    }

    public function update(
        UpdateProfileRequest $request,
        UpdateProfileService $service,
    ): RedirectResponse {
        try {
            $result = $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            $field = $exception->getMessage() === __('profile.current_password_invalid')
                ? 'current_password'
                : 'email';

            return back()->withInput()->withErrors([$field => $exception->getMessage()]);
        }

        if ($request->filled('locale')) {
            $request->session()->put('locale', $request->string('locale')->toString());
        }

        if ($result['email_changed']) {
            $request->session()->put('verification_email', $result['user']->email);

            return redirect()
                ->route('verification.notice')
                ->with('status', __('profile.email_changed_verify'));
        }

        return back()->with('status', __('profile.saved'));
    }
}
