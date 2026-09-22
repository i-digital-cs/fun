<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ListUserHomeParticipationsService;
use App\Services\Events\ResolveEventJoinActionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
        ListUserHomeParticipationsService $participations,
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
        $filters = ['per_page' => 8];
        if (is_string($when) && in_array($when, ['today', 'tomorrow', 'weekend'], true)) {
            $filters['when'] = $when;
        }

        $events = $discover->discover($user, $filters);
        $joinActionsById = $joinActions->resolveMany($user, $events->getCollection());

        $hasNearbyOrigin = $user?->city_id !== null;

        $memberships = Collection::make();
        $pendingRequests = Collection::make();

        if ($user !== null && $user->canParticipate()) {
            $homeParticipations = $participations->forUser($user);
            $memberships = $homeParticipations['memberships'];
            $pendingRequests = $homeParticipations['pendingRequests'];
        }

        return view('home.index', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'publishedTotal' => (int) $categories->sum('published_events_count'),
            'user' => $user,
            'when' => is_string($when) ? $when : null,
            'events' => $events,
            'joinActionsById' => $joinActionsById,
            'hasNearbyOrigin' => $hasNearbyOrigin,
            'memberships' => $memberships,
            'pendingRequests' => $pendingRequests,
        ]);
    }
}
