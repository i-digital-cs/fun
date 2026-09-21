<?php

namespace App\Http\Controllers\Api\Events;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventMembershipController extends Controller
{
    public function join(
        Request $request,
        Event $event,
        RequestToJoinEventService $service,
        ResolveEventJoinActionService $joinActionService,
    ): JsonResponse {
        $this->authorize('join', $event);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $joinRequest = $service->request($request->user(), $event, $validated['message'] ?? null);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('events.join_requested'),
            'join_request' => [
                'id' => $joinRequest->id,
                'status' => $joinRequest->status->value,
            ],
            'join_action' => $joinActionService->resolve($request->user(), $event->refresh())->value,
        ], 201);
    }

    public function requests(Event $event, \App\Services\Safety\UserBlockService $blocks): JsonResponse
    {
        $this->authorize('reviewJoinRequests', $event);

        $blockedIds = $blocks->blockedUserIdsFor(request()->user());

        $requests = $event->joinRequests()
            ->with('user:id,name,email')
            ->when($blockedIds !== [], fn ($q) => $q->whereNotIn('user_id', $blockedIds))
            ->latest()
            ->paginate(20);

        return response()->json($requests);
    }

    public function accept(
        Event $event,
        EventJoinRequest $joinRequest,
        ReviewJoinRequestService $service,
    ): JsonResponse {
        $this->authorize('reviewJoinRequests', $event);
        abort_unless($joinRequest->event_id === $event->id, 404);

        try {
            $member = $service->accept(request()->user(), $joinRequest);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('events.join_accepted'),
            'member' => [
                'event_id' => $member->event_id,
                'user_id' => $member->user_id,
                'role' => $member->role->value,
            ],
        ]);
    }

    public function reject(
        Event $event,
        EventJoinRequest $joinRequest,
        ReviewJoinRequestService $service,
    ): JsonResponse {
        $this->authorize('reviewJoinRequests', $event);
        abort_unless($joinRequest->event_id === $event->id, 404);

        try {
            $updated = $service->reject(request()->user(), $joinRequest);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('events.join_rejected'),
            'join_request' => [
                'id' => $updated->id,
                'status' => $updated->status->value,
            ],
        ]);
    }

    public function members(Event $event): JsonResponse
    {
        $this->authorize('view', $event);

        $members = $event->members()->with('user:id,name,email')->get();

        return response()->json([
            'members' => $members->map(fn ($member) => [
                'user_id' => $member->user_id,
                'role' => $member->role->value,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
            ]),
        ]);
    }

    public function promote(
        Event $event,
        User $user,
        PromoteEventMemberService $service,
    ): JsonResponse {
        $this->authorize('manageMembers', $event);

        try {
            $member = $service->promoteToAdmin(request()->user(), $event, $user);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('events.member_promoted'),
            'member' => [
                'user_id' => $member->user_id,
                'role' => $member->role->value,
            ],
        ]);
    }

    public function demote(
        Event $event,
        User $user,
        PromoteEventMemberService $service,
    ): JsonResponse {
        $this->authorize('manageMembers', $event);

        try {
            $member = $service->demoteToMember(request()->user(), $event, $user);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('events.member_demoted'),
            'member' => [
                'user_id' => $member->user_id,
                'role' => $member->role->value,
            ],
        ]);
    }

    public function transfer(
        Request $request,
        Event $event,
        TransferEventOwnershipService $service,
    ): JsonResponse {
        $this->authorize('manageMembers', $event);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $service->transfer(
                $request->user(),
                $event,
                User::query()->findOrFail($validated['user_id'])
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => __('events.ownership_transferred')]);
    }

    public function leave(Event $event, LeaveEventService $service): JsonResponse
    {
        $this->authorize('leave', $event);

        try {
            $service->leave(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => __('events.left_event')]);
    }
}
