<?php

namespace App\Services\Events;

use App\Models\EventRating;
use App\Models\User;

class CreatorRatingSummaryService
{
    /**
     * @return array{average: float|null, count: int}
     */
    public function forUser(User $user): array
    {
        $row = EventRating::query()
            ->where('rated_user_id', $user->id)
            ->selectRaw('COUNT(*) as ratings_count, AVG(stars) as ratings_avg')
            ->first();

        $count = (int) ($row->ratings_count ?? 0);

        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        return [
            'average' => round((float) $row->ratings_avg, 1),
            'count' => $count,
        ];
    }
}
