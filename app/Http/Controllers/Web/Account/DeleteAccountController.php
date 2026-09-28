<?php

namespace App\Http\Controllers\Web\Account;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DeleteAccountController extends Controller
{
    public function edit(Request $request, AccountDeletionService $service): View
    {
        return view('account.delete', [
            'user' => $request->user(),
            'graceDays' => $service->graceDays(),
        ]);
    }

    public function destroy(Request $request, AccountDeletionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string'],
            'provider_reauth' => ['nullable', 'in:confirmed'],
        ]);

        try {
            $service->requestDeletion(
                $request->user(),
                $validated['password'] ?? null,
                $validated['provider_reauth'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['password' => $e->getMessage()]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('legal.account-deletion')
            ->with('status', __('account.deletion_requested', ['days' => $service->graceDays()]));
    }

    public function cancel(Request $request, AccountDeletionService $service): RedirectResponse
    {
        try {
            $service->cancelDeletion($request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['deletion' => $e->getMessage()]);
        }

        return redirect()
            ->route('settings.account.delete')
            ->with('status', __('account.deletion_cancelled'));
    }
}
