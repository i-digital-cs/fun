<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\Events\ListUserHomeParticipationsService;
use App\Services\Events\ResolveEventJoinActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeParticipationsController extends Controller
{
    public function __invoke(
        Request $request,
        ListUserHomeParticipationsService $participations,
        ResolveEventJoinActionService $joinActions,
    ): JsonResponse {
        $user = $request->user();
        $lists = $participations->forUser($user);

        $events = $lists['memberships']->pluck('event')
            ->merge($lists['pendingRequests']->pluck('event'))
            ->filter()
            ->unique('id')
            ->values();

        $actions = $joinActions->resolveMany($user, $events);

        $mapEvent = function (?Event $event) use ($actions): ?array {
            if (! $event instanceof Event) {
                return null;
            }
            $event->setAttribute('join_action', $actions[$event->id]->value ?? null);
            $event->loadMissing(['category', 'city', 'creator:id,public_id,name,profile_image_path']);
            if (! isset($event->members_count)) {
                $event->loadCount('members');
            }

            return (new EventResource($event))->resolve();
        };

        return response()->json([
            'memberships' => $lists['memberships']->map(fn ($member) => [
                'role' => $member->role?->value ?? $member->role,
                'event' => $mapEvent($member->event),
            ])->values(),
            'pending_requests' => $lists['pendingRequests']->map(fn ($req) => [
                'id' => $req->id,
                'status' => $req->status->value,
                'event' => $mapEvent($req->event),
            ])->values(),
        ]);
    }
}
