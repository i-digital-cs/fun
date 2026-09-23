<?php

namespace App\Http\Controllers\Api\Events;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Services\Events\ListEventNowPlayingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventNowPlayingController extends Controller
{
    public function __invoke(Request $request, ListEventNowPlayingService $service): JsonResponse
    {
        $user = $request->user();
        $cityId = $request->integer('city_id') ?: $user?->city_id;

        $set = $service->forUser($user, $cityId ?: null);

        return response()->json([
            'data' => [
                'running' => $set['running']
                    ? (new EventResource($set['running']))->resolve()
                    : null,
                'next' => EventResource::collection($set['next'])->resolve(),
                'previous' => EventResource::collection($set['previous'])->resolve(),
            ],
        ]);
    }
}
