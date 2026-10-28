<?php

namespace App\Http\Controllers\Web\Events;

use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\DiscoverEventsRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventRating;
use App\Services\Events\CreateEventService;
use App\Services\Events\CreatorRatingSummaryService;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use App\Services\Events\TransitionEventStatusService;
use App\Services\Events\UpdateEventService;
use App\Services\Seo\LandingPageCatalog;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(
        DiscoverEventsRequest $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
        ToggleEventFavoriteService $favorites,
        LandingPageCatalog $landings,
    ): View|Response {
        $filters = $request->filters();
        $events = $discover->discover($request->user(), $filters);

        $explicitCityFilter = array_key_exists('city_id', $filters);
        $hasDeviceOrigin = isset($filters['latitude'], $filters['longitude']);
        $hasNearbyOrigin = $explicitCityFilter
            ? $filters['city_id'] !== null
            : ($hasDeviceOrigin || $request->user()?->city_id !== null);

        // Soft nationwide fallback only when using the member's saved city — never when
        // they explicitly picked a city (or All cities) on Discover.
        $nationwideFallback = false;
        if ($hasNearbyOrigin && ! $explicitCityFilter && $events->total() === 0) {
            $fallback = $discover->discover($request->user(), array_merge($filters, ['city_id' => null]));
            if ($fallback->total() > 0) {
                $events = $fallback;
                $nationwideFallback = true;
                $hasNearbyOrigin = false;
            }
        }

        $joinActionsById = $joinActions->resolveMany($request->user(), $events->getCollection());
        $favoritedIds = $request->user()
            ? $favorites->favoritedIdsFor($request->user(), $events->getCollection())
            : [];

        $filterCityId = array_key_exists('city_id', $filters)
            ? $filters['city_id']
            : ($nationwideFallback ? null : $request->user()?->city_id);
        $filterDistance = $filters['distance_km']
            ?? $request->user()?->distance_km
            ?? config('location.default_distance_km');
        $filterCity = $filterCityId
            ? City::query()->find($filterCityId)
            : null;

        $payload = [
            'events' => $events,
            'joinActionsById' => $joinActionsById,
            'favoritedIds' => $favoritedIds,
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->withCount([
                    'events as published_events_count' => fn ($q) => $q
                        ->where('status', EventStatus::Published)
                        ->where('starts_at', '>=', now()->subDay()),
                ])
                ->get(),
            'cities' => City::query()->active()->orderBy('name_en')->get(),
            'distances' => config('location.distances_km'),
            'filters' => [
                'category_id' => $filters['category_id'] ?? null,
                'city_id' => $filterCityId,
                'distance_km' => $filterDistance,
                'q' => $filters['q'] ?? null,
                'latitude' => $filters['latitude'] ?? null,
                'longitude' => $filters['longitude'] ?? null,
            ],
            'filterCity' => $filterCity,
            'hasNearbyOrigin' => $hasNearbyOrigin,
            'hasDeviceOrigin' => $hasDeviceOrigin,
            'nationwideFallback' => $nationwideFallback,
            'explicitCityFilter' => $explicitCityFilter,
            'landingCities' => $landings->eligibleCities(),
            'landingCategories' => $landings->eligibleCategories(),
            'seo' => Seo::forDiscover(
                page: (int) $events->currentPage(),
                hasFilters: $request->hasAny(['q', 'category_id', 'city_id', 'distance_km', 'when']),
            ),
        ];

        if ($request->boolean('fragment') || $request->header('X-Fragment') === 'results') {
            return response()->view('events.partials.results', $payload);
        }

        return view('events.index', $payload);
    }

    public function mine(Request $request): View
    {
        $userId = (int) $request->user()->id;

        $hosting = Event::query()
            ->with(['category', 'city'])
            ->withCount('members')
            ->where('created_by', $userId)
            ->latest('starts_at')
            ->paginate(12, ['*'], 'hosting_page');

        $joined = Event::query()
            ->with(['category', 'city'])
            ->withCount('members')
            ->where('created_by', '!=', $userId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $userId))
            ->latest('starts_at')
            ->paginate(12, ['*'], 'joined_page');

        $pendingRequests = EventJoinRequest::query()
            ->where('user_id', $userId)
            ->where('status', JoinRequestStatus::Pending)
            ->whereHas('event', fn ($q) => $q->where('status', EventStatus::Published))
            ->with(['event.category', 'event.city'])
            ->latest()
            ->get();

        return view('events.mine', [
            'hosting' => $hosting,
            'joined' => $joined,
            'pendingRequests' => $pendingRequests,
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->withCount([
                    'events as published_events_count' => fn ($q) => $q
                        ->where('status', EventStatus::Published)
                        ->where('starts_at', '>=', now()->subDay()),
                ])
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Event::class);

        return view('events.create', [
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'cities' => City::query()->active()->orderBy('name_en')->get(),
        ]);
    }

    public function store(StoreEventRequest $request, CreateEventService $service): RedirectResponse
    {
        $this->authorize('create', Event::class);

        try {
            $event = $service->create(
                $request->user(),
                $request->safe()->except('image'),
                $request->file('image')
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['event' => $exception->getMessage()]);
        }

        return redirect()->route('events.show', $event->showRoute())
            ->with('status', __('events.created'));
    }

    public function show(
        Event $event,
        ResolveEventJoinActionService $joinActionService,
        CreatorRatingSummaryService $ratingSummary,
        ToggleEventFavoriteService $favorites,
        ?string $slug = null,
    ): View|RedirectResponse {
        $this->authorize('view', $event);

        if (blank($event->slug)) {
            $event->forceFill([
                'slug' => Event::uniqueSlug((string) $event->title, (int) $event->id),
            ])->saveQuietly();
        }

        $canonicalSlug = $event->slug;
        if ($slug !== $canonicalSlug) {
            return redirect()->route('events.show', $event->showRoute(), 301);
        }

        $event->load(['category', 'city', 'creator:id,public_id,name,profile_image_path'])->loadCount('members');

        $user = auth()->user();
        $userRating = null;
        $isFavorited = false;

        if ($user !== null) {
            $userRating = EventRating::query()
                ->where('event_id', $event->id)
                ->where('rater_id', $user->id)
                ->first();
            $isFavorited = $favorites->isFavorited($user, $event);
        }

        $ratings = $event->isReadyForRatings()
            ? EventRating::query()
                ->with('rater:id,name')
                ->where('event_id', $event->id)
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $creatorSummary = $event->creator
            ? $ratingSummary->forUser($event->creator)
            : ['average' => null, 'count' => 0];

        return view('events.show', [
            'event' => $event,
            'joinAction' => $joinActionService->resolve($user, $event),
            'canRate' => $user !== null && $user->can('rate', $event),
            'userRating' => $userRating,
            'ratings' => $ratings,
            'creatorSummary' => $creatorSummary,
            'isFavorited' => $isFavorited,
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->withCount([
                    'events as published_events_count' => fn ($q) => $q
                        ->where('status', EventStatus::Published)
                        ->where('starts_at', '>=', now()->subDay()),
                ])
                ->get(),
            'seo' => Seo::forEvent($event),
        ]);
    }

    public function edit(Event $event, string $slug): View
    {
        $this->authorize('update', $event);

        return view('events.edit', [
            'event' => $event,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'cities' => City::query()->active()->orderBy('name_en')->get(),
        ]);
    }

    public function update(
        UpdateEventRequest $request,
        Event $event,
        string $slug,
        UpdateEventService $service,
    ): RedirectResponse {
        $this->authorize('update', $event);

        try {
            $service->update(
                $request->user(),
                $event,
                $request->safe()->except('image'),
                $request->file('image')
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['event' => $exception->getMessage()]);
        }

        return redirect()->route('events.show', $event->showRoute())
            ->with('status', __('events.updated'));
    }

    public function publish(Event $event, string $slug, TransitionEventStatusService $service): RedirectResponse
    {
        $this->authorize('publish', $event);

        try {
            $service->publish(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        $message = filled($event->fresh()->description)
            ? __('events.published')
            : __('events.published_missing_description');

        return back()->with('status', $message);
    }

    public function cancel(Event $event, string $slug, TransitionEventStatusService $service): RedirectResponse
    {
        $this->authorize('cancel', $event);

        try {
            $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.cancelled'));
    }

    public function complete(Event $event, string $slug, TransitionEventStatusService $service): RedirectResponse
    {
        $this->authorize('complete', $event);

        try {
            $service->complete(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.completed'));
    }
}
