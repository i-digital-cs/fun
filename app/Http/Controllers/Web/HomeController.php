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
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
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
        $user = auth()->user();

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

        if ($user !== null) {
            $user->loadMissing('city');
        }

        $when = $request->query('when');
        $filters = [];
        if (is_string($when) && in_array($when, ['today', 'tomorrow', 'weekend'], true)) {
            $filters['when'] = $when;
        }

        // Ephemeral browser GPS (secure context + user gesture). Never stored.
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = (float) $request->query('latitude');
            $lng = (float) $request->query('longitude');
            if ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) {
                $filters['latitude'] = $lat;
                $filters['longitude'] = $lng;
            }
        }

        $bundle = $eventDiscovery->homeBundle($user, $filters);
        $events = collect($bundle['nearby']);
        $worthTheTrip = collect($bundle['worth_the_trip']);
        $discoveryMeta = $bundle['meta'];
        $hasNearbyOrigin = ($discoveryMeta['origin_city_id'] ?? null) !== null;
        $nationwideFallback = (bool) ($discoveryMeta['nationwide'] ?? false);
        $discoveryExpanded = (bool) ($discoveryMeta['expanded'] ?? false);

        $allForActions = $events->concat($worthTheTrip)->unique('id')->values();
        $joinActionsById = $joinActions->resolveMany($user, $allForActions);
        $favoritedIds = $user !== null
            ? $favorites->favoritedIdsFor($user, $allForActions)
            : [];

        $peopleSuggestions = $user !== null && $user->canParticipate()
            ? $peopleDiscovery->suggest($user, 8)
            : [];

        $cityName = $user?->city?->localizedName();

        $memberships = Collection::make();
        $pendingRequests = Collection::make();

        if ($user !== null && $user->canParticipate()) {
            $homeParticipations = $participations->forUser($user);
            $memberships = $homeParticipations['memberships'];
            $pendingRequests = $homeParticipations['pendingRequests'];
        }

        $weekStart = now()->startOfWeek();
        $weekEnd = now()->endOfWeek();

        $weekEvents = Event::query()
            ->where('status', EventStatus::Published)
            ->whereBetween('starts_at', [$weekStart, $weekEnd])
            ->withCount('members')
            ->get();

        $todayEvents = Event::query()
            ->where('status', EventStatus::Published)
            ->whereDate('starts_at', today())
            ->withCount('members')
            ->get();

        $stats = [
            'events' => $weekEvents->count(),
            'going' => (int) $weekEvents->sum('members_count'),
            'left' => (int) $todayEvents->sum(fn (Event $event) => $event->remainingCapacity()),
        ];

        $highlight = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->whereDate('starts_at', today())
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->first();

        $weatherCity = $user?->city
            ?? City::query()->active()->orderBy('name_en')->first();
        $weather = $weatherCity
            ? $weatherService->forCity($weatherCity, app()->getLocale())
            : null;

        $playback = $nowPlaying->forUser($user, $user?->city_id);

        return view('home.index', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'publishedTotal' => (int) $categories->sum('published_events_count'),
            'user' => $user,
            'cityName' => $cityName,
            'when' => is_string($when) ? $when : null,
            'events' => $events,
            'worthTheTrip' => $worthTheTrip,
            'discoveryMeta' => $discoveryMeta,
            'discoveryExpanded' => $discoveryExpanded,
            'peopleSuggestions' => $peopleSuggestions,
            'joinActionsById' => $joinActionsById,
            'favoritedIds' => $favoritedIds,
            'hasNearbyOrigin' => $hasNearbyOrigin,
            'nationwideFallback' => $nationwideFallback,
            'memberships' => $memberships,
            'pendingRequests' => $pendingRequests,
            'stats' => $stats,
            'highlight' => $highlight,
            'weather' => $weather,
            'nowPlaying' => $playback,
            'seo' => Seo::forHome($highlight?->coverUrl('detail')),
        ]);
    }
}
