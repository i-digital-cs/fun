<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\DiscoverEventsRequest;
use App\Http\Requests\Events\StoreEventRequest;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Services\Events\CreateEventService;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\TransitionEventStatusService;
use App\Services\Events\UpdateEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(
        DiscoverEventsRequest $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
    ): View {
        $filters = $request->filters();
        $events = $discover->discover($request->user(), $filters);
        $joinActionsById = $joinActions->resolveMany($request->user(), $events->getCollection());

        $hasNearbyOrigin = array_key_exists('city_id', $filters)
            ? $filters['city_id'] !== null
            : $request->user()?->city_id !== null;

        return view('events.index', [
            'events' => $events,
            'joinActionsById' => $joinActionsById,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'cities' => City::query()->active()->orderBy('name_en')->get(),
            'distances' => config('location.distances_km'),
            'filters' => [
                'category_id' => $filters['category_id'] ?? null,
                'city_id' => array_key_exists('city_id', $filters)
                    ? $filters['city_id']
                    : $request->user()?->city_id,
                'distance_km' => $filters['distance_km']
                    ?? $request->user()?->distance_km
                    ?? config('location.default_distance_km'),
                'q' => $filters['q'] ?? null,
            ],
            'hasNearbyOrigin' => $hasNearbyOrigin,
        ]);
    }

    public function mine(Request $request): View
    {
        $events = Event::query()
            ->with(['category', 'city'])
            ->withCount('members')
            ->whereHas('members', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest('starts_at')
            ->paginate(12);

        return view('events.mine', compact('events'));
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

        return redirect()->route('events.show', $event)
            ->with('status', __('events.created'));
    }

    public function show(Event $event, ResolveEventJoinActionService $joinActionService): View
    {
        $this->authorize('view', $event);

        $event->load(['category', 'city', 'members.user'])->loadCount('members');

        return view('events.show', [
            'event' => $event,
            'joinAction' => $joinActionService->resolve(auth()->user(), $event),
        ]);
    }

    public function edit(Event $event): View
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

        return redirect()->route('events.show', $event)
            ->with('status', __('events.updated'));
    }

    public function publish(Event $event, TransitionEventStatusService $service): RedirectResponse
    {
        $this->authorize('publish', $event);

        try {
            $service->publish(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.published'));
    }

    public function cancel(Event $event, TransitionEventStatusService $service): RedirectResponse
    {
        $this->authorize('cancel', $event);

        try {
            $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('events.cancelled'));
    }

    public function complete(Event $event, TransitionEventStatusService $service): RedirectResponse
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
