<?php

namespace App\Http\Controllers\Api\Friends;

use App\Http\Controllers\Controller;
use App\Services\Friends\FriendshipService;
use App\Services\Friends\SearchUsersService;
use App\Services\Profile\PublicProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(
        Request $request,
        SearchUsersService $search,
        PublicProfileService $profiles,
        FriendshipService $friends,
    ): JsonResponse {
        $q = $request->string('q')->toString();
        $users = $search->search($request->user(), $q);

        $data = $users->map(function ($user) use ($request, $profiles, $friends) {
            $base = $profiles->toPublicArray($user);
            $state = $friends->stateFor($request->user(), $user);

            return $base + [
                'friendship' => $state,
                'can_message' => $state['can_message'],
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
