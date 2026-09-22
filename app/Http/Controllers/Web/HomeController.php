<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
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

        $events = $discover->discover($user, ['per_page' => 8]);
        $joinActionsById = $joinActions->resolveMany($user, $events->getCollection());

        $hasNearbyOrigin = $user?->city_id !== null;

        return view('home.index', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'publishedTotal' => (int) $categories->sum('published_events_count'),
            'user' => $user,
            'events' => $events,
            'joinActionsById' => $joinActionsById,
            'hasNearbyOrigin' => $hasNearbyOrigin,
        ]);
    }
}
