<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Events\DiscoverNearbyEventsService;
use App\Services\Events\ResolveEventJoinActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestController extends Controller
{
    public function __invoke(
        Request $request,
        DiscoverNearbyEventsService $discover,
        ResolveEventJoinActionService $joinActions,
    ): JsonResponse {
        $q = trim($request->string('q')->toString());
        $type = $request->string('type')->toString() ?: 'events';

        if (mb_strlen($q) < 2) {
            return response()->json(['data' => [], 'meta' => ['q' => $q, 'type' => $type]]);
        }

        if ($type === 'users') {
            if (! $request->user()?->isPlatformAdmin()) {
                abort(403);
            }

            $users = User::query()
                ->with('city')
                ->where(function ($inner) use ($q) {
                    $term = '%'.$q.'%';
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                })
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'title' => $user->name,
                    'subtitle' => $user->email,
                    'url' => route('admin.users.index', ['q' => $user->email]),
                    'meta' => $user->city?->localizedName(),
                ]);

            return response()->json([
                'data' => $users,
                'meta' => [
                    'q' => $q,
                    'type' => 'users',
                    'see_all_url' => route('admin.users.index', ['q' => $q]),
                ],
            ]);
        }

        // Nationwide text search so joined/owned events outside radius still appear.
        $events = $discover->discover($request->user(), [
            'q' => $q,
            'per_page' => 8,
            'city_id' => null,
        ]);

        $actions = $joinActions->resolveMany($request->user(), $events->getCollection());

        $data = $events->getCollection()->map(function ($event) use ($actions) {
            $action = $actions[$event->id] ?? null;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'subtitle' => collect([
                    $event->city?->localizedName(),
                    $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A'),
                ])->filter()->implode(' · '),
                'url' => event_show_url($event),
                'meta' => $event->category?->localizedName(),
                'join_action' => $action?->value,
                'badge' => match ($action?->value) {
                    'owned' => __('events.your_own'),
                    'joined' => __('events.joined'),
                    'request_pending' => __('events.request_pending'),
                    default => null,
                },
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'q' => $q,
                'type' => 'events',
                'see_all_url' => route('events.index', ['q' => $q, 'city_id' => '']),
                'total' => $events->total(),
            ],
        ]);
    }
}
