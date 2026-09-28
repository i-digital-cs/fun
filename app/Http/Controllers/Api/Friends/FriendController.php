<?php

namespace App\Http\Controllers\Api\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Friends\StoreFriendRequest;
use App\Models\User;
use App\Services\Friends\FriendshipService;
use App\Services\Profile\PublicProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendController extends Controller
{
    public function index(Request $request, FriendshipService $friends, PublicProfileService $profiles): JsonResponse
    {
        $list = $friends->listFriends($request->user())
            ->map(fn (User $u) => $profiles->toPublicArray($u))
            ->values();

        return response()->json(['data' => $list]);
    }

    public function requests(Request $request, FriendshipService $friends, PublicProfileService $profiles): JsonResponse
    {
        $incoming = $friends->listIncomingRequests($request->user())->map(fn ($row) => [
            'id' => $row->id,
            'status' => $row->status->value,
            'created_at' => $row->created_at?->toIso8601String(),
            'user' => $profiles->toPublicArray($row->requester),
        ])->values();

        $outgoing = $friends->listOutgoingRequests($request->user())->map(fn ($row) => [
            'id' => $row->id,
            'status' => $row->status->value,
            'created_at' => $row->created_at?->toIso8601String(),
            'user' => $profiles->toPublicArray($row->addressee),
        ])->values();

        return response()->json([
            'data' => [
                'incoming' => $incoming,
                'outgoing' => $outgoing,
            ],
        ]);
    }

    public function store(StoreFriendRequest $request, FriendshipService $friends): JsonResponse
    {
        $target = User::query()
            ->where('public_id', $request->string('public_id')->toString())
            ->firstOrFail();

        try {
            $friendship = $friends->request($request->user(), $target);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $friendship->id,
                'status' => $friendship->status->value,
                'requester_id' => $friendship->requester_id,
                'addressee_id' => $friendship->addressee_id,
            ],
        ], 201);
    }

    public function accept(Request $request, User $user, FriendshipService $friends): JsonResponse
    {
        try {
            $friendship = $friends->accept($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $friendship->id,
                'status' => $friendship->status->value,
            ],
        ]);
    }

    public function decline(Request $request, User $user, FriendshipService $friends): JsonResponse
    {
        try {
            $friendship = $friends->decline($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $friendship->id,
                'status' => $friendship->status->value,
            ],
        ]);
    }

    public function cancel(Request $request, User $user, FriendshipService $friends): JsonResponse
    {
        try {
            $friends->cancel($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('friends.request_cancelled')]);
    }

    public function destroy(Request $request, User $user, FriendshipService $friends): JsonResponse
    {
        try {
            $friends->remove($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => __('friends.removed')]);
    }
}
