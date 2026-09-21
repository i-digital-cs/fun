<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeactivateUserRequest;
use App\Models\User;
use App\Services\Admin\DeactivateUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserModerationController extends Controller
{
    public function deactivate(
        DeactivateUserRequest $request,
        User $user,
        DeactivateUserService $service,
    ): JsonResponse {
        $this->authorize('deactivate', $user);

        try {
            $updated = $service->deactivate($request->user(), $user, $request->validated('reason'));
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.deactivated'),
            'user' => [
                'id' => $updated->id,
                'status' => $updated->status->value,
                'deactivated_at' => $updated->deactivated_at,
            ],
        ]);
    }

    public function reactivate(
        Request $request,
        User $user,
        DeactivateUserService $service,
    ): JsonResponse {
        $this->authorize('reactivate', $user);

        try {
            $updated = $service->reactivate($request->user(), $user);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.reactivated'),
            'user' => [
                'id' => $updated->id,
                'status' => $updated->status->value,
            ],
        ]);
    }
}
