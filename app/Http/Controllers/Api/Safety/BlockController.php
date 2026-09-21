<?php

namespace App\Http\Controllers\Api\Safety;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\Safety\UserBlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $blocks = UserBlock::query()
            ->with('blocked:id,name')
            ->where('blocker_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($blocks);
    }

    public function store(Request $request, User $user, UserBlockService $service): JsonResponse
    {
        try {
            $block = $service->block($request->user(), $user);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('safety.user_blocked'),
            'data' => [
                'blocker_id' => $block->blocker_id,
                'blocked_id' => $block->blocked_id,
            ],
        ], 201);
    }

    public function destroy(Request $request, User $user, UserBlockService $service): JsonResponse
    {
        $service->unblock($request->user(), $user);

        return response()->json(['message' => __('safety.user_unblocked')]);
    }
}
