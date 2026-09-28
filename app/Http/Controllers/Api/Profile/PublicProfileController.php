<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Services\Friends\FriendshipService;
use App\Services\Profile\PublicProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show(
        Request $request,
        string $publicId,
        PublicProfileService $profiles,
        FriendshipService $friends,
    ): JsonResponse {
        $user = $profiles->findByPublicId($publicId);
        $profile = $profiles->toPublicArray($user);

        $hostedEvents = $user->createdEvents()
            ->with(['category:id,name_en,name_ar', 'city:id,name_en,name_ar'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->latest('starts_at')
            ->limit(6)
            ->get()
            ->map(fn ($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at?->toIso8601String(),
                'city' => $event->city?->localizedName(),
                'category' => $event->category?->localizedName(),
                'members_count' => $event->members_count,
                'max_participants' => $event->max_participants,
                'url' => event_show_url($event),
            ])
            ->values();

        $friendship = null;
        $canMessage = false;
        $viewer = $request->user('sanctum');

        if ($viewer !== null) {
            $state = $friends->stateFor($viewer, $user);
            $friendship = $state;
            $canMessage = $state['can_message'];
        }

        return response()->json([
            'data' => $profile + [
                'hosted_events' => $hostedEvents,
                'friendship' => $friendship,
                'can_message' => $canMessage,
            ],
        ]);
    }
}
