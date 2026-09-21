<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\User;
use App\Services\Events\LeaveEventService;
use App\Services\Events\PromoteEventMemberService;
use App\Services\Events\RequestToJoinEventService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ReviewJoinRequestService;
use App\Services\Events\TransferEventOwnershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventMembershipController extends Controller
{
    public function join(
        Request $request,
        Event $event,
        RequestToJoinEventService $service,
    ): RedirectResponse {
        $this->authorize('join', $event);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->request($request->user(), $event, $validated['message'] ?? null);
        } catch (DomainException $exception) {
            return back()->withErrors(['join' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.join_requested'));
    }

    public function requests(Event $event, \App\Services\Safety\UserBlockService $blocks): View
    {
        $this->authorize('reviewJoinRequests', $event);

        $blockedIds = $blocks->blockedUserIdsFor(request()->user());

        $requests = $event->joinRequests()
            ->with('user')
            ->when($blockedIds !== [], fn ($q) => $q->whereNotIn('user_id', $blockedIds))
            ->latest()
            ->paginate(20);

        return view('events.requests', compact('event', 'requests'));
    }

    public function accept(
        Event $event,
        EventJoinRequest $joinRequest,
        ReviewJoinRequestService $service,
    ): RedirectResponse {
        $this->authorize('reviewJoinRequests', $event);
        abort_unless($joinRequest->event_id === $event->id, 404);

        try {
            $service->accept(request()->user(), $joinRequest);
        } catch (DomainException $exception) {
            return back()->withErrors(['join' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.join_accepted'));
    }

    public function reject(
        Event $event,
        EventJoinRequest $joinRequest,
        ReviewJoinRequestService $service,
    ): RedirectResponse {
        $this->authorize('reviewJoinRequests', $event);
        abort_unless($joinRequest->event_id === $event->id, 404);

        try {
            $service->reject(request()->user(), $joinRequest);
        } catch (DomainException $exception) {
            return back()->withErrors(['join' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.join_rejected'));
    }

    public function members(Event $event, ResolveEventJoinActionService $joinActionService): View
    {
        $this->authorize('view', $event);

        $event->load(['members.user']);

        return view('events.members', [
            'event' => $event,
            'joinAction' => $joinActionService->resolve(auth()->user(), $event),
        ]);
    }

    public function promote(
        Event $event,
        User $user,
        PromoteEventMemberService $service,
    ): RedirectResponse {
        $this->authorize('manageMembers', $event);

        try {
            $service->promoteToAdmin(request()->user(), $event, $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['members' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.member_promoted'));
    }

    public function demote(
        Event $event,
        User $user,
        PromoteEventMemberService $service,
    ): RedirectResponse {
        $this->authorize('manageMembers', $event);

        try {
            $service->demoteToMember(request()->user(), $event, $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['members' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.member_demoted'));
    }

    public function transfer(
        Request $request,
        Event $event,
        TransferEventOwnershipService $service,
    ): RedirectResponse {
        $this->authorize('manageMembers', $event);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newOwner = User::query()->findOrFail($validated['user_id']);

        try {
            $service->transfer($request->user(), $event, $newOwner);
        } catch (DomainException $exception) {
            return back()->withErrors(['members' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.ownership_transferred'));
    }

    public function leave(Event $event, LeaveEventService $service): RedirectResponse
    {
        $this->authorize('leave', $event);

        try {
            $service->leave(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['members' => $exception->getMessage()]);
        }

        return redirect()->route('events.show', $event)
            ->with('status', __('events.left_event'));
    }
}
