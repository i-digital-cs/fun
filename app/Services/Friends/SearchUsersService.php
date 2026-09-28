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
     * Search by public_id. Accepts full ID, digits only (e.g. 001108), or prefix.
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

        $normalized = UserPublicId::normalizeQuery($q);

        if ($normalized !== null) {
            // Digits / full ID → exact (001108 or 1108 → Funity-F001108).
            $builder->where('public_id', $normalized);
        } else {
            // Prefix match on public_id only (not name) for privacy.
            $escaped = addcslashes($q, '%_\\');
            $builder->where('public_id', 'like', $escaped.'%');
        }

        return $builder
            ->orderBy('public_id')
            ->limit(min($limit, 50))
            ->get();
    }
}
