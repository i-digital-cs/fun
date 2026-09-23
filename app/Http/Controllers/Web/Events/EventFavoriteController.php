<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventFavorite;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Events\ToggleEventFavoriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventFavoriteController extends Controller
{
    public function index(Request $request, ResolveEventJoinActionService $joinActions): View
    {
        $user = $request->user();

        $events = Event::query()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->whereIn('id', EventFavorite::query()
                ->where('user_id', $user->id)
                ->select('event_id'))
            ->latest('starts_at')
            ->paginate(12);

        $joinActionsById = $joinActions->resolveMany($user, $events->getCollection());
        $favoritedIds = $events->getCollection()->mapWithKeys(fn (Event $e) => [$e->id => true])->all();

        return view('events.favorites', [
            'events' => $events,
            'joinActionsById' => $joinActionsById,
            'favoritedIds' => $favoritedIds,
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name_en')
                ->withCount([
                    'events as published_events_count' => fn ($q) => $q
                        ->where('status', \App\Enums\EventStatus::Published)
                        ->where('starts_at', '>=', now()->subDay()),
                ])
                ->get(),
        ]);
    }

    public function toggle(
        Request $request,
        Event $event,
        ToggleEventFavoriteService $service,
    ): RedirectResponse {
        $this->authorize('view', $event);

        try {
            $favorited = $service->toggle($request->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['favorite' => $exception->getMessage()]);
        }

        return back()->with('status', $favorited
            ? __('events.favorited')
            : __('events.unfavorited'));
    }
}
