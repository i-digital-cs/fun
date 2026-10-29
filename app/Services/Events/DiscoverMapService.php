<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\Location\NearbyDiscoveryGeography;
use App\Services\Safety\UserBlockService;
use App\Support\GeoDistance;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Map-first geographic discovery: bounded bbox queries + grid clustering.
 * Single source of truth for Flutter + Web map payloads.
 */
class DiscoverMapService
{
    /** Egypt approximate bounds — reject absurd worldwide queries. */
    public const EGYPT_MIN_LAT = 21.5;

    public const EGYPT_MAX_LAT = 32.0;

    public const EGYPT_MIN_LNG = 24.5;

    public const EGYPT_MAX_LNG = 37.0;

    public const MAX_MARKERS = 250;

    public function __construct(
        private readonly UserBlockService $blocks,
        private readonly NearbyDiscoveryGeography $geography,
    ) {}

    /**
     * @param  array{
     *     north: float,
     *     south: float,
     *     east: float,
     *     west: float,
     *     zoom?: int,
     *     category_id?: int|null,
     *     q?: string|null,
     *     when?: string|null,
     *     include_approximate?: bool,
     *     limit?: int,
     *     latitude?: float|null,
     *     longitude?: float|null,
     *     distance_km?: int|null,
     *     discovery_mode?: string|null
     * }  $filters
     * @return array{
     *     markers: list<array<string, mixed>>,
     *     clusters: list<array<string, mixed>>,
     *     city_aggregates: list<array<string, mixed>>,
     *     meta: array<string, mixed>
     * }
     */
    public function mapPayload(?User $user, array $filters): array
    {
        $resolved = $this->validatedBounds($filters);
        $bounds = [
            'north' => $resolved['north'],
            'south' => $resolved['south'],
            'east' => $resolved['east'],
            'west' => $resolved['west'],
        ];
        $zoom = (int) ($filters['zoom'] ?? 6);
        $zoom = max(4, min(16, $zoom));
        $includeApproximate = (bool) ($filters['include_approximate'] ?? false);
        $limit = max(1, min(self::MAX_MARKERS, (int) ($filters['limit'] ?? self::MAX_MARKERS)));

        $originMode = isset($filters['discovery_mode'])
            ? strtolower(trim((string) $filters['discovery_mode']))
            : null;
        $originLat = isset($filters['latitude']) && is_numeric($filters['latitude'])
            ? (float) $filters['latitude']
            : null;
        $originLng = isset($filters['longitude']) && is_numeric($filters['longitude'])
            ? (float) $filters['longitude']
            : null;
        $distanceKm = isset($filters['distance_km']) && is_numeric($filters['distance_km'])
            ? (int) $filters['distance_km']
            : null;

        $originAware = in_array($originMode, ['device', 'manual'], true)
            && $originLat !== null
            && $originLng !== null
            && $distanceKm !== null
            && $distanceKm > 0;

        $selectedRadiusKm = $distanceKm;
        $effectiveRadiusKm = $distanceKm;
        $isSuggestion = false;

        $radiiToTry = $originAware ? [$distanceKm] : [null];
        if ($originAware && ($filters['progressive'] ?? true) !== false) {
            foreach ($this->geography->progressiveRadii($distanceKm) as $tryKm) {
                $radiiToTry[] = $tryKm;
            }
        }

        $exactPoints = collect();
        $cityAggregates = [];
        $queryBounds = $bounds;
        $nearbyCityIds = null;

        foreach ($radiiToTry as $tryKm) {
            $activeOriginAware = $originAware && $tryKm !== null;
            $queryBounds = $bounds;
            $nearbyCityIds = null;
            $filterKm = $tryKm;

            if ($activeOriginAware) {
                // Cover selected/suggestion radius, not only the Leaflet viewport.
                $coverKm = $tryKm > 0 ? $tryKm : 400;
                $radiusBounds = $this->geography->boundsForRadius($originLat, $originLng, $coverKm);
                $queryBounds = [
                    'north' => max($bounds['north'], $radiusBounds['north']),
                    'south' => min($bounds['south'], $radiusBounds['south']),
                    'east' => max($bounds['east'], $radiusBounds['east']),
                    'west' => min($bounds['west'], $radiusBounds['west']),
                ];
                if ($tryKm > 0) {
                    $cityKm = max(1, $this->geography->citySearchRadiusKm($tryKm));
                    $nearbyCityIds = $this->geography
                        ->nearbyCityIds($originLat, $originLng, $cityKm)
                        ->all();
                } else {
                    $nearbyCityIds = null; // nationwide suggestion
                }
            }

            $exactPoints = $this->collectMapPoints($user, $filters, $queryBounds, false);
            if ($activeOriginAware && $tryKm > 0) {
                $exactPoints = $exactPoints
                    ->filter(function (array $p) use ($originLat, $originLng, $tryKm, $nearbyCityIds) {
                        if (GeoDistance::isWithinKm(
                            $originLat,
                            $originLng,
                            (float) $p['latitude'],
                            (float) $p['longitude'],
                            $tryKm
                        )) {
                            return true;
                        }
                        $cityId = isset($p['city_id']) ? (int) $p['city_id'] : null;

                        return $cityId !== null && in_array($cityId, $nearbyCityIds ?? [], true);
                    })
                    ->values();
            } elseif ($activeOriginAware && $tryKm === 0) {
                // Nationwide suggestion: keep exact points in expanded query bounds.
                $exactPoints = $exactPoints->values();
            }

            $exactPoints = $exactPoints->take($limit)->values();
            $cityAggregates = $this->collectCityAggregates($user, $filters, $queryBounds, $nearbyCityIds);

            if (! $originAware || $exactPoints->isNotEmpty() || $cityAggregates !== []) {
                if ($originAware && $filterKm !== null && $filterKm !== $selectedRadiusKm) {
                    $isSuggestion = true;
                    $effectiveRadiusKm = $filterKm;
                }
                break;
            }
        }

        // At nationwide / regional zoom, grid-cluster nearby city bubbles so Delta cities do not stack.
        if ($zoom < 10 && ! $originAware) {
            $cityAggregates = $this->clusterCityAggregates($cityAggregates, $zoom);
        }

        // Legacy opt-in: also emit approximate per-event markers at city centroid.
        $legacyApproximate = collect();
        if ($includeApproximate) {
            $legacyApproximate = $this->collectMapPoints($user, $filters, $bounds, true)
                ->filter(fn (array $p) => ($p['location_precision'] ?? '') === 'approximate')
                ->take(max(0, $limit - $exactPoints->count()))
                ->values();
        }

        $points = $exactPoints->concat($legacyApproximate)->values();

        $clusterAtZoom = $zoom < 11;
        $clusters = [];
        $markers = [];

        if ($clusterAtZoom) {
            // Cluster exact pins only — city aggregates use their own clustering above.
            $clusters = $this->gridCluster($exactPoints, $zoom);
            if ($includeApproximate) {
                foreach ($legacyApproximate as $approx) {
                    $markers[] = $this->markerPayload($approx);
                }
            }
        } else {
            $markers = $points->map(fn (array $p) => $this->markerPayload($p))->all();
        }

        return [
            'markers' => $markers,
            'clusters' => $clusters,
            'city_aggregates' => $cityAggregates,
            'meta' => [
                'zoom' => $zoom,
                'clustered' => $clusterAtZoom,
                'point_count' => $exactPoints->count(),
                'city_aggregate_count' => count($cityAggregates),
                'include_approximate' => $includeApproximate,
                'bounds' => $bounds,
                'query_bounds' => $queryBounds,
                'bounds_clamped' => (bool) ($resolved['clamped'] ?? false),
                'nationwide_mode' => (bool) ($resolved['nationwide_mode'] ?? false) && ! $originAware,
                'origin_aware' => $originAware,
                'origin' => $originAware
                    ? [
                        'latitude' => $originLat,
                        'longitude' => $originLng,
                        'distance_km' => $selectedRadiusKm,
                        'effective_radius_km' => $effectiveRadiusKm,
                        'is_suggestion' => $isSuggestion,
                        'discovery_mode' => $originMode,
                    ]
                    : null,
                'selected_radius_km' => $selectedRadiusKm,
                'effective_radius_km' => $effectiveRadiusKm,
                'is_suggestion' => $isSuggestion,
                'max_markers' => self::MAX_MARKERS,
            ],
        ];
    }

    /**
     * Validate map bounds with zoom-aware safety.
     *
     * Low zoom (≤7): oversized viewports are clamped to the Egypt envelope
     * (nationwide aggregation) instead of HTTP 422 — fixes initial Discover load.
     * Higher zoom: oversized boxes are still rejected to avoid unbounded dumps.
     *
     * @param  array<string, mixed>  $filters
     * @return array{north: float, south: float, east: float, west: float, clamped: bool, nationwide_mode: bool}
     */
    public function validatedBounds(array $filters): array
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (! isset($filters[$key]) || ! is_numeric($filters[$key])) {
                throw ValidationException::withMessages([
                    $key => ['Map bounds are required.'],
                ]);
            }
        }

        $north = (float) $filters['north'];
        $south = (float) $filters['south'];
        $east = (float) $filters['east'];
        $west = (float) $filters['west'];

        if ($north <= $south || $east <= $west) {
            throw ValidationException::withMessages([
                'north' => ['Invalid bounding box.'],
            ]);
        }

        if ($north < self::EGYPT_MIN_LAT - 2 || $south > self::EGYPT_MAX_LAT + 2
            || $east < self::EGYPT_MIN_LNG - 2 || $west > self::EGYPT_MAX_LNG + 2) {
            throw ValidationException::withMessages([
                'north' => ['Bounds are outside the supported discovery region.'],
            ]);
        }

        $zoom = (int) ($filters['zoom'] ?? 6);
        $maxLatSpan = $zoom <= 7 ? 12.0 : 8.0;
        $maxLngSpan = $zoom <= 7 ? 14.0 : 10.0;
        $latSpan = $north - $south;
        $lngSpan = $east - $west;
        $clamped = false;
        $nationwideMode = false;

        if ($latSpan > $maxLatSpan || $lngSpan > $maxLngSpan) {
            if ($zoom <= 7) {
                // Bounded nationwide query: Egypt envelope only (aggregates + clusters).
                $north = self::EGYPT_MAX_LAT;
                $south = self::EGYPT_MIN_LAT;
                $east = self::EGYPT_MAX_LNG;
                $west = self::EGYPT_MIN_LNG;
                $clamped = true;
                $nationwideMode = true;
            } else {
                throw ValidationException::withMessages([
                    'north' => ['Bounding box is too large. Zoom in or use Search this area on a smaller region.'],
                ]);
            }
        }

        return [
            'north' => $north,
            'south' => $south,
            'east' => $east,
            'west' => $west,
            'clamped' => $clamped,
            'nationwide_mode' => $nationwideMode,
        ];
    }

    /**
     * @param  array{north: float, south: float, east: float, west: float}  $bounds
     * @return Collection<int, array{
     *     id: int,
     *     latitude: float,
     *     longitude: float,
     *     title: string,
     *     starts_at: ?string,
     *     category_id: ?int,
     *     category_name: ?string,
     *     image_url: ?string,
     *     members_count: int,
     *     location_precision: string,
     *     venue_label: string
     * }>
     */
    private function collectMapPoints(
        ?User $user,
        array $filters,
        array $bounds,
        bool $includeApproximate,
    ): Collection {
        $query = Event::query()
            ->with(['category', 'city'])
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

        if (! empty($filters['when'])) {
            $this->applyWhenFilter($query, (string) $filters['when']);
        }

        if (! empty($filters['q']) && trim((string) $filters['q']) !== '') {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('venue', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        /** @var Collection<int, Event> $events */
        $events = $query->orderBy('starts_at')->limit(800)->get();

        $points = collect();

        foreach ($events as $event) {
            if ($event->latitude !== null && $event->longitude !== null) {
                $lat = (float) $event->latitude;
                $lng = (float) $event->longitude;
                $precision = 'exact';
            } elseif ($includeApproximate && $event->city?->latitude !== null && $event->city?->longitude !== null) {
                $lat = (float) $event->city->latitude;
                $lng = (float) $event->city->longitude;
                $precision = 'approximate';
            } else {
                continue;
            }

            if ($lat < $bounds['south'] || $lat > $bounds['north']
                || $lng < $bounds['west'] || $lng > $bounds['east']) {
                continue;
            }

            $points->push([
                'id' => (int) $event->id,
                'city_id' => $event->city_id ? (int) $event->city_id : null,
                'latitude' => $lat,
                'longitude' => $lng,
                'title' => (string) $event->title,
                'starts_at' => optional($event->starts_at)?->toIso8601String(),
                'category_id' => $event->category_id ? (int) $event->category_id : null,
                'category_name' => $event->category?->localizedName(),
                'image_url' => $event->imageUrl('card'),
                'members_count' => (int) ($event->members_count ?? 0),
                'location_precision' => $precision,
                'venue_label' => $event->locationLabel(),
            ]);
        }

        return $points;
    }

    /**
     * City-only published events → aggregate markers at city centroid.
     * Explicitly classified as approximate city-level — never as venue pins.
     *
     * @param  array{north: float, south: float, east: float, west: float}  $bounds
     * @return list<array<string, mixed>>
     */
    /**
     * @param  list<int>|null  $restrictCityIds  When set (origin+radius mode), only these cities.
     */
    private function collectCityAggregates(?User $user, array $filters, array $bounds, ?array $restrictCityIds = null): array
    {
        $query = Event::query()
            ->with('city')
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>=', now()->subDay())
            ->whereNull('latitude')
            ->whereNull('longitude')
            ->whereNotNull('city_id');

        if ($restrictCityIds !== null) {
            if ($restrictCityIds === []) {
                return [];
            }
            $query->whereIn('city_id', $restrictCityIds);
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

        if (! empty($filters['q']) && trim((string) $filters['q']) !== '') {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('venue', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $events = $query->orderBy('starts_at')->limit(800)->get();
        $buckets = [];

        foreach ($events as $event) {
            $city = $event->city;
            if ($city === null || $city->latitude === null || $city->longitude === null) {
                continue;
            }
            $lat = (float) $city->latitude;
            $lng = (float) $city->longitude;
            if ($restrictCityIds === null) {
                if ($lat < $bounds['south'] || $lat > $bounds['north']
                    || $lng < $bounds['west'] || $lng > $bounds['east']) {
                    continue;
                }
            }

            $cityId = (int) $city->id;
            if (! isset($buckets[$cityId])) {
                $buckets[$cityId] = [
                    'type' => 'city_aggregate',
                    'id' => 'city_'.$cityId,
                    'city_id' => $cityId,
                    'city_name' => $city->localizedName(),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'count' => 0,
                    'location_precision' => 'city_level',
                    'label' => null,
                    'sample_event_ids' => [],
                ];
            }
            $buckets[$cityId]['count']++;
            if (count($buckets[$cityId]['sample_event_ids']) < 8) {
                $buckets[$cityId]['sample_event_ids'][] = (int) $event->id;
            }
        }

        foreach ($buckets as &$bucket) {
            $bucket['label'] = $bucket['city_name'].' · '.$bucket['count'].' city-level';
        }
        unset($bucket);

        return array_values($buckets);
    }

    /**
     * Group nearby city aggregates at low zoom. Count = sum of city event counts (no double-count).
     *
     * @param  list<array<string, mixed>>  $aggregates
     * @return list<array<string, mixed>>
     */
    private function clusterCityAggregates(array $aggregates, int $zoom): array
    {
        $cell = match (true) {
            $zoom <= 5 => 0.85,
            $zoom <= 7 => 0.40,
            $zoom <= 9 => 0.18,
            default => 0.10,
        };

        $buckets = [];
        foreach ($aggregates as $agg) {
            $lat = (float) $agg['latitude'];
            $lng = (float) $agg['longitude'];
            $latKey = (int) floor($lat / $cell);
            $lngKey = (int) floor($lng / $cell);
            $key = $latKey.':'.$lngKey;
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'lat_sum' => 0.0,
                    'lng_sum' => 0.0,
                    'count' => 0,
                    'cities' => [],
                    'n' => 0,
                ];
            }
            $buckets[$key]['lat_sum'] += $lat;
            $buckets[$key]['lng_sum'] += $lng;
            $buckets[$key]['count'] += (int) $agg['count'];
            $buckets[$key]['n']++;
            $buckets[$key]['cities'][] = [
                'city_id' => (int) $agg['city_id'],
                'city_name' => $agg['city_name'],
                'count' => (int) $agg['count'],
                'latitude' => $lat,
                'longitude' => $lng,
            ];
        }

        $out = [];
        foreach ($buckets as $key => $bucket) {
            if ($bucket['n'] === 1) {
                $city = $bucket['cities'][0];
                $out[] = [
                    'type' => 'city_aggregate',
                    'id' => 'city_'.$city['city_id'],
                    'city_id' => $city['city_id'],
                    'city_name' => $city['city_name'],
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                    'count' => $city['count'],
                    'location_precision' => 'city_level',
                    'label' => $city['city_name'].' · '.$city['count'].' city-level',
                ];
                continue;
            }

            $out[] = [
                'type' => 'city_cluster',
                'id' => 'cityc_'.$key,
                'city_id' => null,
                'city_name' => null,
                'latitude' => $bucket['lat_sum'] / $bucket['n'],
                'longitude' => $bucket['lng_sum'] / $bucket['n'],
                'count' => $bucket['count'],
                'city_count' => $bucket['n'],
                'cities' => $bucket['cities'],
                'location_precision' => 'city_level',
                'expansion_zoom' => min(12, $zoom + 2),
                'label' => $bucket['n'].' cities · '.$bucket['count'].' city-level events',
            ];
        }

        return $out;
    }

    /**
     * Upcoming published events for a city (exact + city-only). Used by aggregate drill-down.
     *
     * @param  array{category_id?: int|null, q?: string|null, when?: string|null}  $filters
     * @return array{city: array<string, mixed>, events: list<array<string, mixed>>}
     */
    public function cityEvents(?User $user, int $cityId, array $filters = []): array
    {
        $query = Event::query()
            ->with(['city', 'category'])
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>=', now()->subDay())
            ->where('city_id', $cityId);

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

        if (! empty($filters['q']) && trim((string) $filters['q']) !== '') {
            $term = '%'.trim((string) $filters['q']).'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('title', 'like', $term)
                    ->orWhere('venue', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $events = $query->orderBy('starts_at')->limit(40)->get();
        $city = $events->first()?->city;

        return [
            'city' => [
                'id' => $cityId,
                'name' => $city?->localizedName() ?? '',
                'latitude' => $city?->latitude !== null ? (float) $city->latitude : null,
                'longitude' => $city?->longitude !== null ? (float) $city->longitude : null,
            ],
            'events' => $events->map(function (Event $event) {
                $exact = $event->hasPlacePin();

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'venue_label' => $event->locationLabel(),
                    'has_exact_coordinates' => $exact,
                    'location_precision' => $exact ? 'exact' : 'city_level',
                    'latitude' => $exact ? (float) $event->latitude : null,
                    'longitude' => $exact ? (float) $event->longitude : null,
                    'city_id' => $event->city_id,
                    'url' => '/events/'.$event->id,
                ];
            })->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $points
     * @return list<array<string, mixed>>
     */
    private function gridCluster(Collection $points, int $zoom): array
    {
        // Coarser cells at lower zoom.
        $cell = match (true) {
            $zoom <= 5 => 1.2,
            $zoom <= 7 => 0.55,
            $zoom <= 9 => 0.25,
            default => 0.12,
        };

        $buckets = [];
        foreach ($points as $point) {
            $latKey = (int) floor($point['latitude'] / $cell);
            $lngKey = (int) floor($point['longitude'] / $cell);
            $key = $latKey.':'.$lngKey;
            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'lat_sum' => 0.0,
                    'lng_sum' => 0.0,
                    'count' => 0,
                    'ids' => [],
                ];
            }
            $buckets[$key]['lat_sum'] += $point['latitude'];
            $buckets[$key]['lng_sum'] += $point['longitude'];
            $buckets[$key]['count']++;
            if (count($buckets[$key]['ids']) < 8) {
                $buckets[$key]['ids'][] = $point['id'];
            }
        }

        $clusters = [];
        foreach ($buckets as $key => $bucket) {
            if ($bucket['count'] === 1) {
                $id = $bucket['ids'][0];
                $point = $points->firstWhere('id', $id);
                if ($point) {
                    $clusters[] = array_merge($this->markerPayload($point), [
                        'type' => 'marker',
                        'count' => 1,
                    ]);
                }
                continue;
            }

            $clusters[] = [
                'type' => 'cluster',
                'id' => 'c_'.$key,
                'latitude' => $bucket['lat_sum'] / $bucket['count'],
                'longitude' => $bucket['lng_sum'] / $bucket['count'],
                'count' => $bucket['count'],
                'sample_event_ids' => $bucket['ids'],
                'expansion_zoom' => min(16, $zoom + 2),
            ];
        }

        return $clusters;
    }

    /**
     * @param  array<string, mixed>  $point
     * @return array<string, mixed>
     */
    private function markerPayload(array $point): array
    {
        return [
            'type' => 'marker',
            'id' => $point['id'],
            'latitude' => $point['latitude'],
            'longitude' => $point['longitude'],
            'title' => $point['title'],
            'starts_at' => $point['starts_at'],
            'category_id' => $point['category_id'],
            'category_name' => $point['category_name'],
            'image_url' => $point['image_url'],
            'members_count' => $point['members_count'],
            'location_precision' => $point['location_precision'],
            'venue_label' => $point['venue_label'],
            'count' => 1,
        ];
    }

    private function applyWhenFilter(Builder $query, string $when): void
    {
        $tz = config('app.timezone');
        $now = now($tz);

        match ($when) {
            'today' => $query->whereDate('starts_at', $now->toDateString()),
            'tomorrow' => $query->whereDate('starts_at', $now->copy()->addDay()->toDateString()),
            'weekend' => $query->whereBetween('starts_at', [
                $now->copy()->next(CarbonInterface::FRIDAY)->startOfDay(),
                $now->copy()->next(CarbonInterface::FRIDAY)->addDays(2)->endOfDay(),
            ]),
            default => null,
        };
    }
}
