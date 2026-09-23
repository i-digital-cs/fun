<?php

namespace App\Services\Profile;

use App\Enums\EventStatus;
use App\Models\User;
use App\Services\Events\CreatorRatingSummaryService;
use App\Support\UserPublicId;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PublicProfileService
{
    public function __construct(
        private readonly CreatorRatingSummaryService $ratings,
    ) {
    }

    public function findByPublicId(string $publicId): User
    {
        if (! UserPublicId::isValid($publicId)) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        $user = User::query()
            ->with('city')
            ->where('public_id', $publicId)
            ->first();

        if ($user === null || $user->isDeactivated()) {
            throw (new ModelNotFoundException)->setModel(User::class);
        }

        return $user;
    }

    /**
     * Safe public payload — never include email / private prefs.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(User $user): array
    {
        $rating = $this->ratings->forUser($user);

        $hostedCount = $user->createdEvents()
            ->where('status', EventStatus::Published)
            ->count();

        $joinedCount = $user->eventMemberships()->count();

        $roleLabel = $hostedCount > 0
            ? __('profile.role_host')
            : __('profile.role_member');

        return [
            'public_id' => $user->public_id,
            'name' => $user->name,
            'initials' => user_initials($user->name),
            'profile_image_path' => $user->profile_image_path,
            'role_label' => $roleLabel,
            'city' => $user->city ? [
                'id' => $user->city->id,
                'name' => $user->city->localizedName(),
            ] : null,
            'stats' => [
                'hosted_events' => $hostedCount,
                'joined_events' => $joinedCount,
                'rating_average' => $rating['average'],
                'rating_count' => $rating['count'],
            ],
            'profile_url' => route('users.public.show', $user->public_id),
        ];
    }
}
