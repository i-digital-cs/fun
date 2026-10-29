<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Services\Events\EventDiscoveryService;
use App\Services\Events\ListEventNowPlayingService;
use App\Services\Events\ListUserHomeParticipationsService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use App\Services\Friends\PeopleDiscoveryService;
use App\Services\Weather\OpenMeteoWeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        EventDiscoveryService $eventDiscovery,
        ResolveEventJoinActionService $joinActions,
        ListUserHomeParticipationsService $participations,
        OpenMeteoWeatherService $weatherService,
        ListEventNowPlayingService $nowPlaying,
        ToggleEventFavoriteService $favorites,
        PeopleDiscoveryService $peopleDiscovery,
    ): View {
        $user = $request->user();
        $user->loadMissing('city');

        $when = $request->query('when');
        $filters = [];
        if (is_string($when) && in_array($when, ['today', 'tomorrow', 'weekend'], true)) {
            $filters['when'] = $when;
        }

        $cityId = $request->query('city_id');
        $explicitCityFilter = array_key_exists('city_id', $request->query());
        if ($explicitCityFilter) {
            $filters['city_id'] = ($cityId !== null && $cityId !== '') ? (int) $cityId : null;
        }

        $distanceKm = null;
        if (array_key_exists('distance_km', $request->query()) && $request->query('distance_km') !== null && $request->query('distance_km') !== '') {
            $distanceKm = (int) $request->query('distance_km');
            $filters['distance_km'] = $distanceKm;
        }

        $bundle = $eventDiscovery->homeBundle($user, $filters);
        $events = Collection::make($bundle['nearby']);
        $worthTheTrip = Collection::make($bundle['worth_the_trip']);
        $discoveryMeta = $bundle['meta'];
        $hasNearbyOrigin = ($discoveryMeta['origin_city_id'] ?? null) !== null;
        $nationwideFallback = (bool) ($discoveryMeta['nationwide'] ?? false);
        $discoveryExpanded = (bool) ($discoveryMeta['expanded'] ?? false);

        $allForActions = $events->concat($worthTheTrip)->unique('id')->values();
        $joinActionsById = $joinActions->resolveMany($user, $allForActions);
        $favoritedIds = $favorites->favoritedIdsFor($user, $allForActions);
        $peopleSuggestions = $user->canParticipate()
            ? $peopleDiscovery->suggest($user, 8)
            : [];

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->withCount([
                'events as published_events_count' => fn ($q) => $q
                    ->where('status', EventStatus::Published)
                    ->where('starts_at', '>=', now()->subDay()),
            ])
            ->get();

        $resolvedCityId = array_key_exists('city_id', $filters)
            ? $filters['city_id']
            : $user->city_id;
        $cityName = $resolvedCityId
            ? City::query()->find($resolvedCityId)?->localizedName()
            : $user->city?->localizedName();

        $selectedDistance = $distanceKm
            ?? (int) ($user->distance_km ?? config('location.default_distance_km'));

        $startingSoon = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addHours(48))
            ->orderBy('starts_at')
            ->limit(4)
            ->get();

        $playback = $nowPlaying->forUser($user, $resolvedCityId ? (int) $resolvedCityId : null);

        $homeParticipations = $participations->forUser($user);

        $weatherCity = $resolvedCityId
            ? City::query()->active()->find($resolvedCityId)
            : $user->city;
        $weather = $weatherCity
            ? $weatherService->forCity($weatherCity, app()->getLocale())
            : null;

        $miniMapCity = $weatherCity;
        $miniMapLat = $miniMapCity?->latitude;
        $miniMapLng = $miniMapCity?->longitude;
        $miniMapMode = ($miniMapLat !== null && $miniMapLng !== null && $hasNearbyOrigin)
            ? 'manual'
            : 'nationwide';

        return view('home.dashboard', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'user' => $user,
            'cities' => City::query()->active()->orderBy('name_en')->get(),
            'distances' => config('location.distances_km'),
            'events' => $events,
            'worthTheTrip' => $worthTheTrip,
            'discoveryMeta' => $discoveryMeta,
            'discoveryExpanded' => $discoveryExpanded,
            'peopleSuggestions' => $peopleSuggestions,
            'joinActionsById' => $joinActionsById,
            'favoritedIds' => $favoritedIds,
            'startingSoon' => $startingSoon,
            'nowPlaying' => $playback,
            'cityName' => $cityName,
            'distanceKm' => $selectedDistance,
            'hasNearbyOrigin' => $hasNearbyOrigin,
            'nationwideFallback' => $nationwideFallback,
            'memberships' => $homeParticipations['memberships'],
            'pendingRequests' => $homeParticipations['pendingRequests'],
            'weather' => $weather,
            'selectedDistance' => $selectedDistance,
            'miniMapLat' => $miniMapLat !== null ? (float) $miniMapLat : null,
            'miniMapLng' => $miniMapLng !== null ? (float) $miniMapLng : null,
            'miniMapMode' => $miniMapMode,
            'miniMapCityId' => $miniMapCity?->id,
        ]);
    }
}
