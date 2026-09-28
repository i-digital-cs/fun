<?php

namespace App\Services\Friends;

use App\Enums\AccountStatus;
use App\Models\User;
use App\Services\Safety\UserBlockService;
use App\Support\UserPublicId;
use Illuminate\Support\Collection;

class SearchUsersService
{
    public const MIN_QUERY_LENGTH = 3;

    public function __construct(
        private readonly UserBlockService $blocks,
    ) {
    }

    /**
     * Search by public_id (exact or prefix). Discovery fields only.
     *
     * @return Collection<int, User>
     */
    public function search(User $viewer, string $query, int $limit = 20): Collection
    {
        $q = trim($query);

        if (mb_strlen($q) < self::MIN_QUERY_LENGTH) {
            return collect();
        }

        $blockedIds = $this->blocks->blockedUserIdsFor($viewer);
        $blockedIds[] = (int) $viewer->id;

        $builder = User::query()
            ->select(['id', 'public_id', 'name', 'profile_image_path', 'city_id', 'status'])
            ->with('city:id,name_en,name_ar')
            ->where('status', AccountStatus::Active)
            ->whereNotNull('public_id')
            ->whereNotIn('id', $blockedIds);

        if (UserPublicId::isValid($q)) {
            $builder->where('public_id', $q);
        } else {
            // Prefix match on public_id only (not name) for privacy.
            $builder->where('public_id', 'like', $q.'%');
        }

        return $builder
            ->orderBy('public_id')
            ->limit(min($limit, 50))
            ->get();
    }
}
