<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\Location\NearbyDiscoveryGeography;
use App\Services\Location\ResolveDiscoveryOriginService;
use App\Services\Safety\UserBlockService;
use App\Support\GeoDistance;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class DiscoverNearbyEventsService
{
    /**
     * Meta from the last discover() call (suggestion radius, etc.).
     *
     * @var array{
     *     selected_radius_km: int|null,
     *     effective_radius_km: int|null,
     *     is_suggestion: bool,
     *     suggestion_message_key: string|null,
     *     origin_type: string|null
     * }|null
     */
    public ?array $lastMeta = null;

    public function __construct(
        private readonly UserBlockService $blocks,
        private readonly ResolveDiscoveryOriginService $origins,
        private readonly NearbyDiscoveryGeography $geography,
    ) {}

    /**
     * @param  array{category_id?: int|null, q?: string|null, per_page?: int, progressive?: bool}  $filters
     */
    public function discover(?User $user, array $filters = []): LengthAwarePaginator
    {
        $this->lastMeta = [
            'selected_radius_km' => null,
            'effective_radius_km' => null,
            'is_suggestion' => false,
            'suggestion_message_key' => null,
            'origin_type' => null,
        ];

        $origin = $this->origins->resolve($user, $filters);
        $selectedKm = $origin !== null ? (int) $origin['distance_km'] : null;
        $this->lastMeta['selected_radius_km'] = $selectedKm;
        $this->lastMeta['effective_radius_km'] = $selectedKm;
        $this->lastMeta['origin_type'] = $origin['origin_type'] ?? null;

        $events = $this->runQuery($user, $filters, $origin);

        // Progressive nearest-event expansion is for GPS/place origins only.
        // Explicit city picks keep a hard empty state (do not leak other cities).
        $originType = $origin['origin_type'] ?? null;
        $allowProgressive = ($filters['progressive'] ?? true) === true
            && empty($filters['q'])
            && $origin !== null
            && in_array($originType, ['device', 'place'], true)
            && $selectedKm !== null
            && $selectedKm > 0
            && $events->total() === 0;

        if ($allowProgressive) {
            foreach ($this->geography->progressiveRadii($selectedKm) as $tryKm) {
                $tryOrigin = array_merge($origin, ['distance_km' => $tryKm]);
                $candidate = $this->runQuery($user, $filters, $tryOrigin);
                if ($candidate->total() > 0) {
                    $this->lastMeta['effective_radius_km'] = $tryKm;
                    $this->lastMeta['is_suggestion'] = true;
                    $this->lastMeta['suggestion_message_key'] = 'events.nearby_suggestions_banner';

                    return $candidate;
                }
            }
        }

        return $events;
    }

    /**
     * @param  array{latitude: float, longitude: float, distance_km: int, city: mixed, origin_type?: string}|null  $origin
     */
    private function runQuery(?User $user, array $filters, ?array $origin): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 12);

        $query = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members');

        $hasTextSearch = ! empty($filters['q']) && trim((string) $filters['q']) !== '';

        if ($hasTextSearch) {
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

        $activeOrigin = $origin;
        if ($activeOrigin !== null) {
            $explicitCity = array_key_exists('city_id', $filters) && $filters['city_id'] !== null;
            $skipGeoForTextSearch = $hasTextSearch && ! $explicitCity;
            $allDistances = (int) $activeOrigin['distance_km'] === 0;

            if (! $skipGeoForTextSearch && ! $allDistances) {
                $selectedKm = (int) $activeOrigin['distance_km'];
                // City-only venues: uncertainty-buffered radius. A city centroid
                // inside the chip radius is not proof the venue is inside it.
                // citySearchRadiusKm === 0 means no city-only cities qualify
                // (must NOT call nearbyCityIds(0), which means nationwide).
                // Floor at 1 km so the origin city still qualifies when uncertainty
                // zeroes the buffered radius (e.g. 10 km chip − 10 km uncertainty).
                $cityKm = max(1, $this->geography->citySearchRadiusKm($selectedKm));
                $cityIds = $this->geography->nearbyCityIds(
                    $activeOrigin['latitude'],
                    $activeOrigin['longitude'],
                    $cityKm
                );
                $box = $this->geography->boundsForRadius(
                    $activeOrigin['latitude'],
                    $activeOrigin['longitude'],
                    $selectedKm,
                    1.0
                );
                // City-only via uncertainty-buffered cities OR exact pins in radius bbox.
                $query->where(function (Builder $builder) use ($cityIds, $box) {
                    if ($cityIds->isNotEmpty()) {
                        $builder->whereIn('city_id', $cityIds->all());
                    }
                    $builder->orWhere(function (Builder $exact) use ($box) {
                        $exact->whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->whereBetween('latitude', [$box['south'], $box['north']])
                            ->whereBetween('longitude', [$box['west'], $box['east']]);
                    });
                });
            } elseif ($skipGeoForTextSearch) {
                $activeOrigin = null;
            }
        }

        /** @var LengthAwarePaginator $events */
        $events = $query->orderBy('starts_at')->paginate($perPage)->withQueryString();

        if ($activeOrigin !== null) {
            $events->getCollection()->transform(function (Event $event) use ($activeOrigin) {
                $this->attachDistance($event, $activeOrigin);

                return $event;
            });
        }

        return $events;
    }

    /**
     * @param  array{latitude: float, longitude: float, distance_km: int, city: mixed}  $origin
     */
    private function attachDistance(Event $event, array $origin): void
    {
        if ($event->hasPlacePin()) {
            $km = GeoDistance::kilometers(
                $origin['latitude'],
                $origin['longitude'],
                (float) $event->latitude,
                (float) $event->longitude
            );
            $event->setAttribute('distance_km', round($km, 1));
            $event->setAttribute('distance_precision', 'exact');

            return;
        }

        if ($event->city && $event->city->latitude !== null && $event->city->longitude !== null) {
            $km = GeoDistance::kilometers(
                $origin['latitude'],
                $origin['longitude'],
                (float) $event->city->latitude,
                (float) $event->city->longitude
            );
            $event->setAttribute('distance_km', round($km, 1));
            $event->setAttribute('distance_precision', 'city_level');

            return;
        }

        $event->setAttribute('distance_km', null);
        $event->setAttribute('distance_precision', 'none');
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
