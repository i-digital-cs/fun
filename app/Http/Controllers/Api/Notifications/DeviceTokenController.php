<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Services\Notifications\NotificationInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        $device = $inbox->registerDeviceToken(
            $request->user(),
            $validated['token'],
            $validated['platform'] ?? 'android',
        );

        return response()->json([
            'data' => [
                'id' => $device->id,
                'platform' => $device->platform,
                'last_used_at' => $device->last_used_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Request $request, NotificationInboxService $inbox): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        $inbox->forgetDeviceToken($request->user(), $validated['token']);

        return response()->json(['data' => ['forgotten' => true]]);
    }
}
