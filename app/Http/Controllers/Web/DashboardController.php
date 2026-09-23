<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ListEventNowPlayingService;
use App\Services\Events\ListUserHomeParticipationsService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use App\Services\Weather\OpenMeteoWeatherService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
        ListUserHomeParticipationsService $participations,
        OpenMeteoWeatherService $weatherService,
        ListEventNowPlayingService $nowPlaying,
        ToggleEventFavoriteService $favorites,
    ): View {
        $user = $request->user();
        $user->loadMissing('city');

        $when = $request->query('when');
        $filters = ['per_page' => 8];
        if (is_string($when) && in_array($when, ['today', 'tomorrow', 'weekend'], true)) {
            $filters['when'] = $when;
        }

        $cityId = $request->query('city_id');
        $explicitCityFilter = array_key_exists('city_id', $request->query());
        if ($explicitCityFilter) {
            $filters['city_id'] = ($cityId !== null && $cityId !== '') ? (int) $cityId : null;
        }

        $distanceKm = $request->integer('distance_km') ?: null;
        if ($distanceKm) {
            $filters['distance_km'] = $distanceKm;
        }

        $events = $discover->discover($user, $filters);
        $hasNearbyOrigin = $explicitCityFilter
            ? ($filters['city_id'] ?? null) !== null
            : $user->city_id !== null;
        $nationwideFallback = false;

        if ($hasNearbyOrigin && ! $explicitCityFilter && $events->total() === 0) {
            $fallback = $discover->discover($user, array_merge($filters, ['city_id' => null]));
            if ($fallback->total() > 0) {
                $events = $fallback;
                $nationwideFallback = true;
            }
        }

        $joinActionsById = $joinActions->resolveMany($user, $events->getCollection());
        $favoritedIds = $favorites->favoritedIdsFor($user, $events->getCollection());

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

        return view('home.dashboard', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'user' => $user,
            'cities' => City::query()->active()->orderBy('name_en')->get(),
            'distances' => config('location.distances_km'),
            'events' => $events,
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
        ]);
    }
}
