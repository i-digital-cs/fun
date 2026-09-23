<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Services\Profile\PublicProfileService;
use Illuminate\Http\JsonResponse;

class PublicProfileController extends Controller
{
    public function show(string $publicId, PublicProfileService $profiles): JsonResponse
    {
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
                'url' => route('events.show', $event),
            ])
            ->values();

        return response()->json([
            'data' => $profile + [
                'hosted_events' => $hostedEvents,
            ],
        ]);
    }
}
