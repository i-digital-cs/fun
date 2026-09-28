<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Services\Safety\UserBlockService;
use App\Support\GeoDistance;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DiscoverNearbyEventsService
{
    public function __construct(private readonly UserBlockService $blocks) {}

    /**
     * @param  array{category_id?: int|null, q?: string|null, per_page?: int}  $filters
     */
    public function discover(?User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 12);

        $origin = $this->resolveOrigin($user, $filters);

        $query = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members');

        $hasTextSearch = ! empty($filters['q']) && trim((string) $filters['q']) !== '';

        if ($hasTextSearch) {
            // Name search: include published + completed (recent enough to be useful).
            $query->whereIn('status', [EventStatus::Published, EventStatus::Completed])
                ->where(function (Builder $builder) {
                    $builder->where('starts_at', '>=', now()->subDays(90))
                        ->orWhere(function (Builder $ongoing) {
                            $ongoing->where('status', EventStatus::Published)
                                ->whereNotNull('ends_at')
                                ->where('ends_at', '>=', now());
                        });
                });
        } else {
            // Browse: live/upcoming published only.
            $query->where('status', EventStatus::Published)
                ->where('starts_at', '>=', now()->subDay());
        }

        if ($user !== null) {
            $blockedIds = $this->blocks->blockedUserIdsFor($user);

            if ($blockedIds !== []) {
                $query->whereNotIn('created_by', $blockedIds);
            }
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['when'])) {
            $this->applyWhenFilter($query, (string) $filters['when']);
        }

        if ($hasTextSearch) {
            $term = trim((string) $filters['q']);
            $variants = $this->searchTermVariants($term);
            $query->where(function (Builder $builder) use ($variants) {
                foreach ($variants as $variant) {
                    $like = '%'.$variant.'%';
                    $builder->orWhere(function (Builder $inner) use ($like) {
                        $inner->where('title', 'like', $like)
                            ->orWhere('venue', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
                }
            });
        }

        if ($origin !== null) {
            // Text search is nationwide unless the user explicitly picked a city filter.
            $explicitCity = array_key_exists('city_id', $filters) && $filters['city_id'] !== null;
            $skipGeoForTextSearch = $hasTextSearch && ! $explicitCity;

            if (! $skipGeoForTextSearch) {
                $cityIds = $this->nearbyCityIds(
                    $origin['latitude'],
                    $origin['longitude'],
                    $origin['distance_km']
                );

                $query->whereIn('city_id', $cityIds->all());
            } else {
                $origin = null;
            }
        }

        /** @var LengthAwarePaginator $events */
        $events = $query->orderBy('starts_at')->paginate($perPage)->withQueryString();

        if ($origin !== null) {
            $events->getCollection()->transform(function (Event $event) use ($origin) {
                if ($event->city) {
                    $event->setAttribute(
                        'distance_km',
                        round(GeoDistance::kilometers(
                            $origin['latitude'],
                            $origin['longitude'],
                            (float) $event->city->latitude,
                            (float) $event->city->longitude
                        ), 1)
                    );
                }

                return $event;
            });
        }

        return $events;
    }

    /**
     * @param  array{city_id?: int|null, distance_km?: int|null}  $filters
     * @return array{latitude: float, longitude: float, distance_km: int, city: City}|null
     */
    private function resolveOrigin(?User $user, array $filters): ?array
    {
        $allowedDistances = config('location.distances_km');
        $distance = (int) ($filters['distance_km']
            ?? $user?->distance_km
            ?? config('location.default_distance_km'));

        if (! in_array($distance, $allowedDistances, true)) {
            throw ValidationException::withMessages([
                'distance_km' => [__('location.invalid_distance')],
            ]);
        }

        $cityId = array_key_exists('city_id', $filters)
            ? $filters['city_id']
            : $user?->city_id;

        if ($cityId === null) {
            return null;
        }

        $city = City::query()->active()->find($cityId);

        if ($city === null) {
            throw ValidationException::withMessages([
                'city_id' => [__('location.city_unavailable')],
            ]);
        }

        return [
            'latitude' => (float) $city->latitude,
            'longitude' => (float) $city->longitude,
            'distance_km' => $distance,
            'city' => $city,
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function nearbyCityIds(float $latitude, float $longitude, int $maxKm): Collection
    {
        $version = (int) cache()->get('nearby_cities_version', 0);
        $cacheKey = sprintf('nearby_cities:v%d:%.5f:%.5f:%d', $version, $latitude, $longitude, $maxKm);

        return cache()->remember($cacheKey, now()->addMinutes(10), function () use ($latitude, $longitude, $maxKm) {
            $ids = City::query()
                ->active()
                ->get(['id', 'latitude', 'longitude'])
                ->filter(fn (City $city) => GeoDistance::isWithinKm(
                    $latitude,
                    $longitude,
                    (float) $city->latitude,
                    (float) $city->longitude,
                    $maxKm
                ))
                ->pluck('id')
                ->values();

            // Always keep the origin city itself (0 km), even if coords were missing/cached oddly.
            $originId = City::query()
                ->active()
                ->where('latitude', $latitude)
                ->where('longitude', $longitude)
                ->value('id');

            if ($originId !== null && ! $ids->contains($originId)) {
                $ids->prepend((int) $originId);
            }

            return $ids->unique()->values();
        });
    }

    private function applyWhenFilter(Builder $query, string $when): void
    {
        $tz = config('app.timezone');
        $now = now($tz);

        match ($when) {
            'today' => $query->whereBetween('starts_at', [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay(),
            ]),
            'tomorrow' => $query->whereBetween('starts_at', [
                $now->copy()->addDay()->startOfDay(),
                $now->copy()->addDay()->endOfDay(),
            ]),
            'weekend' => $query->whereBetween('starts_at', $this->upcomingWeekendBounds($now)),
            default => null,
        };
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function upcomingWeekendBounds(CarbonInterface $now): array
    {
        if ($now->isSaturday()) {
            $saturday = $now->copy()->startOfDay();
        } elseif ($now->isSunday()) {
            $saturday = $now->copy()->subDay()->startOfDay();
        } else {
            $saturday = $now->copy()->next('Saturday')->startOfDay();
        }

        return [$saturday, $saturday->copy()->addDay()->endOfDay()];
    }

    /**
     * Expand common EN/AR place/team aliases so "zamalek" finds "الزمالك".
     *
     * @return list<string>
     */
    private function searchTermVariants(string $term): array
    {
        $term = trim($term);
        $variants = [$term];
        $lower = mb_strtolower($term);

        $aliases = [
            'zamalek' => ['الزمالك', 'زمالك'],
            'الزمالك' => ['zamalek', 'زمالك'],
            'زمالك' => ['zamalek', 'الزمالك'],
            'cairo' => ['القاهرة', 'قاهرة'],
            'القاهرة' => ['cairo'],
            'giza' => ['الجيزة', 'جيزة'],
            'الجيزة' => ['giza'],
        ];

        foreach ($aliases as $key => $extras) {
            if ($lower === mb_strtolower($key) || str_contains($lower, mb_strtolower($key))) {
                foreach ($extras as $extra) {
                    $variants[] = $extra;
                }
            }
        }

        return array_values(array_unique(array_filter($variants, fn ($v) => $v !== '')));
    }
}
