<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Collection;

class ListEventNowPlayingService
{
    /**
     * @return array{
     *     running: Event|null,
     *     next: Collection<int, Event>,
     *     previous: Collection<int, Event>
     * }
     */
    public function forUser(?User $user, ?int $cityId = null, int $nextLimit = 2, int $previousLimit = 2): array
    {
        $cityId = $cityId ?? $user?->city_id;
        $now = now();

        $running = $this->baseQuery($cityId)
            ->where('starts_at', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->where(function ($ongoing) use ($now) {
                    $ongoing->whereNotNull('ends_at')->where('ends_at', '>=', $now);
                })->orWhere(function ($openEnded) use ($now) {
                    $openEnded->whereNull('ends_at')
                        ->where('starts_at', '>=', $now->copy()->subHours(6));
                });
            })
            ->orderByDesc('starts_at')
            ->first();

        if ($running === null && $cityId !== null) {
            $running = $this->baseQuery(null)
                ->where('starts_at', '<=', $now)
                ->where(function ($q) use ($now) {
                    $q->where(function ($ongoing) use ($now) {
                        $ongoing->whereNotNull('ends_at')->where('ends_at', '>=', $now);
                    })->orWhere(function ($openEnded) use ($now) {
                        $openEnded->whereNull('ends_at')
                            ->where('starts_at', '>=', $now->copy()->subHours(6));
                    });
                })
                ->orderByDesc('starts_at')
                ->first();
        }

        $next = $this->baseQuery($cityId)
            ->where('starts_at', '>', $now)
            ->orderBy('starts_at')
            ->limit($nextLimit)
            ->get();

        if ($next->isEmpty() && $cityId !== null) {
            $next = $this->baseQuery(null)
                ->where('starts_at', '>', $now)
                ->orderBy('starts_at')
                ->limit($nextLimit)
                ->get();
        }

        $previous = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
            ->where(function ($q) use ($now) {
                $q->where(function ($ended) use ($now) {
                    $ended->whereNotNull('ends_at')->where('ends_at', '<', $now);
                })->orWhere(function ($stale) use ($now) {
                    $stale->whereNull('ends_at')
                        ->where('starts_at', '<', $now->copy()->subHours(6));
                });
            })
            ->orderByDesc('starts_at')
            ->limit($previousLimit)
            ->get();

        if ($previous->isEmpty() && $cityId !== null) {
            $previous = Event::query()
                ->with(['category', 'city', 'creator'])
                ->withCount('members')
                ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
                ->where(function ($q) use ($now) {
                    $q->where(function ($ended) use ($now) {
                        $ended->whereNotNull('ends_at')->where('ends_at', '<', $now);
                    })->orWhere(function ($stale) use ($now) {
                        $stale->whereNull('ends_at')
                            ->where('starts_at', '<', $now->copy()->subHours(6));
                    });
                })
                ->orderByDesc('starts_at')
                ->limit($previousLimit)
                ->get();
        }

        return [
            'running' => $running,
            'next' => $next,
            'previous' => $previous,
        ];
    }

    private function baseQuery(?int $cityId)
    {
        return Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->when($cityId, fn ($q) => $q->where('city_id', $cityId));
    }
}
