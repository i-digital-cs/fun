<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\AccountStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeactivateUserRequest;
use App\Http\Requests\Admin\UpdateUserAdminRequest;
use App\Models\User;
use App\Services\Admin\DeactivateUserService;
use App\Services\Admin\UpdateUserAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', AccountStatus::Active->value);
        $allowed = [
            AccountStatus::Active->value,
            AccountStatus::PendingDocuments->value,
            AccountStatus::Deactivated->value,
            'all',
        ];
        if (! in_array($status, $allowed, true)) {
            $status = AccountStatus::Active->value;
        }

        $users = User::query()
            ->with(['city', 'createdEvents' => fn ($q) => $q
                ->with(['category', 'city'])
                ->latest('starts_at')
                ->limit(12)])
            ->withCount([
                'createdEvents as created_events_count',
                'eventMemberships as memberships_count',
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('q')).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'active' => User::query()->where('status', AccountStatus::Active)->count(),
            'pending_documents' => User::query()->where('status', AccountStatus::PendingDocuments)->count(),
            'deactivated' => User::query()->where('status', AccountStatus::Deactivated)->count(),
            'all' => User::query()->count(),
        ];

        return view('admin.users.index', [
            'users' => $users,
            'status' => $status,
            'counts' => $counts,
            'q' => $request->query('q'),
        ]);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', [
            'user' => $user->load('city'),
            'statuses' => AccountStatus::cases(),
        ]);
    }

    public function update(
        UpdateUserAdminRequest $request,
        User $user,
        UpdateUserAdminService $service,
    ): RedirectResponse {
        $this->authorize('update', $user);

        try {
            $service->update($request->user(), $user, $request->validated());
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['admin' => $exception->getMessage()]);
        }

        return redirect()->route('admin.users.index', ['status' => $user->fresh()->status->value])
            ->with('status', __('admin.user_updated'));
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
