<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeactivateUserRequest;
use App\Models\User;
use App\Services\Admin\DeactivateUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserModerationController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function deactivate(
        DeactivateUserRequest $request,
        User $user,
        DeactivateUserService $service,
    ): RedirectResponse {
        $this->authorize('deactivate', $user);

        try {
            $service->deactivate($request->user(), $user, $request->validated('reason'));
        } catch (DomainException $exception) {
            return back()->withErrors(['admin' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.deactivated'));
    }

    public function reactivate(
        Request $request,
        User $user,
        DeactivateUserService $service,
    ): RedirectResponse {
        $this->authorize('reactivate', $user);

        try {
            $service->reactivate($request->user(), $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['admin' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.reactivated'));
    }
}
