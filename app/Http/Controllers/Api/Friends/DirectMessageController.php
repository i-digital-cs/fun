<?php

namespace App\Http\Controllers\Api\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Friends\StoreDirectMessageRequest;
use App\Http\Resources\DirectMessageResource;
use App\Models\DirectConversation;
use App\Models\User;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DirectMessageController extends Controller
{
    public function index(Request $request, DirectMessageService $dms): JsonResponse
    {
        $list = $dms->listConversations($request->user());

        return response()->json([
            'data' => $list->values(),
            'meta' => [
                'unread_total' => (int) $list->sum('unread_count'),
            ],
        ]);
    }

    public function open(Request $request, User $user, DirectMessageService $dms): JsonResponse
    {
        try {
            $conversation = $dms->findOrCreateWith($request->user(), $user);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $conversation->id,
                'url' => route('dms.show', $conversation),
                'api_path' => '/dms/'.$conversation->id.'/messages',
            ],
        ], 201);
    }

    public function messages(
        Request $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): AnonymousResourceCollection|JsonResponse {
        try {
            $messages = $dms->listMessages(
                $request->user(),
                $conversation,
                (int) $request->integer('per_page', 50)
            );
            $dms->markRead($request->user(), $conversation);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return DirectMessageResource::collection($messages);
    }

    public function store(
        StoreDirectMessageRequest $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): JsonResponse {
        try {
            $message = $dms->sendToConversation(
                $request->user(),
                $conversation,
                $request->string('body')->toString()
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new DirectMessageResource($message))->response()->setStatusCode(201);
    }

    public function markRead(
        Request $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): JsonResponse {
        try {
            $read = $dms->markRead($request->user(), $conversation);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => [
                'conversation_id' => $conversation->id,
                'last_read_message_id' => $read->last_read_message_id,
            ],
        ]);
    }
}
