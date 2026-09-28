<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notify_friend_events' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'notify_friend_events' => $validated['notify_friend_events'],
        ])->save();

        return response()->json([
            'data' => [
                'notify_friend_events' => (bool) $user->notify_friend_events,
            ],
        ]);
    }
}
