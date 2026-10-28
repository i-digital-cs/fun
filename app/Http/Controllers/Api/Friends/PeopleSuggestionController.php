<?php

namespace App\Http\Controllers\Api\Friends;

use App\Http\Controllers\Controller;
use App\Services\Friends\PeopleDiscoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PeopleSuggestionController extends Controller
{
    public function __invoke(Request $request, PeopleDiscoveryService $discovery): JsonResponse
    {
        $user = $request->user();
        $limit = min(24, max(1, (int) $request->query('limit', 8)));

        return response()->json([
            'data' => $discovery->suggest($user, $limit),
        ]);
    }
}
