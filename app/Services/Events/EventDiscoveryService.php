<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\City;
use App\Models\Event;
use App\Models\User;
use App\Services\Location\ResolveDiscoveryOriginService;
use App\Services\Safety\UserBlockService;
use App\Support\GeoDistance;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Option B home discovery: Nearby (+ radius expansion) + Worth the Trip.
 *
 * Single source of truth for Web + Flutter homepage sections.
 * Distances are straight-line km from the discovery city center (not driving).
 */
class EventDiscoveryService
{
    public function __construct(
        private readonly UserBlockService $blocks,
        private readonly ResolveDiscoveryOriginService $origins,
    ) {}

    /**
     * @param  array{city_id?: int|null, distance_km?: int|null, category_id?: int|null, when?: string|null}  $filters
     * @return array{
     *     meta: array{
     *         origin_city_id: int|null,
     *         origin_type: string,
     *         requested_radius_km: int|null,
     *         effective_radius_km: int|null,
     *         expanded: bool,
     *         nationwide: bool,
     *         distance_label: string
     *     },
     *     nearby: list<Event>,
     *     worth_the_trip: list<Event>
     * }
     */
    public function homeBundle(?User $user, array $filters = [], int $nearbyLimit = 8, int $worthLimit = 6): array
    {
        $origin = $this->origins->resolve($user, $filters);
        $requested = $origin['distance_km'] ?? null;
        $effective = $requested;
        $expanded = false;
        $nationwide = $requested === 0;

        $nearbyEvents = collect();

        if ($origin === null) {
            $nearbyEvents = $this->eligibleEvents($user, $filters)
                ->orderBy('starts_at')
                ->limit($nearbyLimit)
                ->get();
            $nationwide = true;
        } else {
            $nearbyEvents = $this->eventsWithinRadius($user, $filters, $origin, $requested);
            if ($nearbyEvents->isEmpty() && $requested !== 0) {
                foreach ($this->expansionLadderAfter((int) $requested) as $km) {
                    $candidate = $this->eventsWithinRadius($user, $filters, $origin, $km);
                    if ($candidate->isNotEmpty()) {
                        $nearbyEvents = $candidate->take($nearbyLimit);
                        $effective = $km;
                        $expanded = true;
                        $nationwide = $km === 0;
                        break;
                    }
                }
            } else {
                $nearbyEvents = $nearbyEvents->take($nearbyLimit);
            }
        }

        $excludeIds = $nearbyEvents->pluck('id')->all();
        $worth = $this->worthTheTrip($user, $filters, $origin, $excludeIds, $worthLimit);

        $this->attachDistances($nearbyEvents, $origin);
        $this->attachDistances($worth, $origin);

        $originType = $origin === null
            ? 'none'
            : (string) ($origin['origin_type'] ?? 'city');

        return [
            'meta' => [
                'origin_city_id' => isset($origin['city']) && $origin['city'] instanceof City
                    ? $origin['city']->id
                    : null,
                'origin_type' => $originType,
                'requested_radius_km' => $requested,
                'effective_radius_km' => $effective,
                'expanded' => $expanded,
                'nationwide' => $nationwide,
                'distance_label' => 'straight_line_km',
                'distance_basis' => $originType === 'device'
                    ? 'device_to_event_or_city_center'
                    : 'city_center',
            ],
            'nearby' => $nearbyEvents->values()->all(),
            'worth_the_trip' => $worth->values()->all(),
        ];
    }

    /**
     * @return list<int>
     */
    private function expansionLadderAfter(int $requestedKm): array
    {
        $ladder = config('location.expansion_ladder_km', [25, 50, 100, 0]);

        return array_values(array_filter(
            $ladder,
            fn (int $km) => $km === 0 || $km > $requestedKm
        ));
    }

    /**
     * @param  array{latitude: float, longitude: float, distance_km: int, city: City}  $origin
     * @return Collection<int, Event>
     */
    private function eventsWithinRadius(?User $user, array $filters, array $origin, int $radiusKm): Collection
    {
        $query = $this->eligibleEvents($user, $filters);

        if ($radiusKm !== 0) {
            $cityIds = $this->cityIdsWithin(
                $origin['latitude'],
                $origin['longitude'],
                $radiusKm
            );
            $query->whereIn('city_id', $cityIds);
        }

        return $query->orderBy('starts_at')->limit(48)->get();
    }

    /**
     * Worth the Trip: eligible events outside the requested nearby radius.
     *
     * Ranking uses real crow-flies km only (no popularity scores).
     *
     * @param  array{latitude: float, longitude: float, distance_km: int, city: City}|null  $origin
     * @param  list<int>  $excludeIds
     * @return Collection<int, Event>
     */
    private function worthTheTrip(
        ?User $user,
        array $filters,
        ?array $origin,
        array $excludeIds,
        int $limit,
    ): Collection {
        if ($origin === null || (int) $origin['distance_km'] === 0) {
            return collect();
        }

        $radiusKm = (int) $origin['distance_km'];
        $nearCityIds = $this->cityIdsWithin(
            $origin['latitude'],
            $origin['longitude'],
            $radiusKm
        );

        $events = $this->eligibleEvents($user, $filters)
            ->when($excludeIds !== [], fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->when($nearCityIds !== [], fn ($q) => $q->whereNotIn('city_id', $nearCityIds))
            ->orderBy('starts_at')
            ->limit(40)
            ->get();

        return $events
            ->map(function (Event $event) use ($origin) {
                $event->setAttribute(
                    'distance_km',
                    $this->distanceForEvent($event, $origin)
                );

                return $event;
            })
            ->sortBy(fn (Event $e) => (float) ($e->distance_km ?? PHP_FLOAT_MAX))
            ->take($limit)
            ->values();
    }

    /**
     * @param  array{category_id?: int|null, when?: string|null}  $filters
     */
    private function eligibleEvents(?User $user, array $filters): \Illuminate\Database\Eloquent\Builder
    {
        $query = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>=', now()->subDay());

        if ($user !== null) {
            $blockedIds = $this->blocks->blockedUserIdsFor($user);
            if ($blockedIds !== []) {
                $query->whereNotIn('created_by', $blockedIds);
            }
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private function cityIdsWithin(float $lat, float $lng, int $maxKm): array
    {
        $version = (int) cache()->get('nearby_cities_version', 0);
        $cacheKey = sprintf('nearby_cities:v%d:%.5f:%.5f:%d', $version, $lat, $lng, $maxKm);

        /** @var Collection<int, int> $ids */
        $ids = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($lat, $lng, $maxKm) {
            return City::query()
                ->active()
                ->get(['id', 'latitude', 'longitude'])
                ->filter(fn (City $city) => GeoDistance::isWithinKm(
                    $lat,
                    $lng,
                    (float) $city->latitude,
                    (float) $city->longitude,
                    $maxKm
                ))
                ->pluck('id')
                ->values();
        });

        return $ids->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  Collection<int, Event>  $events
     * @param  array{latitude: float, longitude: float, distance_km: int, city: City}|null  $origin
     */
    private function attachDistances(Collection $events, ?array $origin): void
    {
        if ($origin === null) {
            return;
        }

        $events->each(function (Event $event) use ($origin) {
            if ($event->distance_km === null) {
                $event->setAttribute('distance_km', $this->distanceForEvent($event, $origin));
            }
        });
    }

    /**
     * Prefer event pin when present; else city center. Never invent coordinates.
     *
     * @param  array{latitude: float, longitude: float, distance_km: int, city: City}  $origin
     */
    private function distanceForEvent(Event $event, array $origin): ?float
    {
        $lat = null;
        $lng = null;

        if ($event->latitude !== null && $event->longitude !== null) {
            $lat = (float) $event->latitude;
            $lng = (float) $event->longitude;
        } elseif ($event->city) {
            $lat = (float) $event->city->latitude;
            $lng = (float) $event->city->longitude;
        }

        if ($lat === null || $lng === null) {
            return null;
        }

        return round(GeoDistance::kilometers(
            $origin['latitude'],
            $origin['longitude'],
            $lat,
            $lng
        ), 1);
    }
}
