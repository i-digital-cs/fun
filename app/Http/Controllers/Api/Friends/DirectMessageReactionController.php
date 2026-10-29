<?php

namespace App\Http\Controllers\Api\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Services\Chat\ReactToDirectMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectMessageReactionController extends Controller
{
    public function __invoke(
        Request $request,
        DirectConversation $conversation,
        DirectMessage $message,
        ReactToDirectMessageService $reactions,
    ): JsonResponse {
        $validated = $request->validate([
            'emoji' => ['required', 'string', 'max:16'],
        ]);

        try {
            $summary = $reactions->toggle(
                $request->user(),
                $conversation,
                $message,
                $validated['emoji']
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'message_id' => $message->id,
                'emoji_counts' => $summary['emoji_counts'],
                'my_emojis' => $summary['my_emojis'],
            ],
        ]);
    }
}
