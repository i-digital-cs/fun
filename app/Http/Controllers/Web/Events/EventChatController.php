<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\ReactToEventMessageRequest;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventMessage;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\ListMessageReactionsService;
use App\Services\Events\Chat\MarkEventChatReadService;
use App\Services\Events\Chat\PostEventMessageService;
use App\Services\Events\Chat\ReactToEventMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventChatController extends Controller
{
    public function show(
        Event $event,
        string $slug,
        ListEventMessagesService $listService,
        MarkEventChatReadService $readService,
    ): View {
        $this->authorize('chat', $event);

        $user = request()->user();
        $messages = $listService->list($user, $event, 50);
        $readService->mark($user, $event);

        return view('events.chat', [
            'event' => $event->load(['category', 'city', 'creator:id,public_id,name,profile_image_path']),
            'messages' => $messages->getCollection()->reverse()->values(),
            'paginator' => $messages,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function store(
        StoreEventMessageRequest $request,
        Event $event,
        string $slug,
        PostEventMessageService $service,
    ): RedirectResponse|JsonResponse {
        $this->authorize('chat', $event);

        try {
            $message = $service->post($request->user(), $event, $request->string('body')->toString());
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withErrors(['body' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'id' => $message->id,
                    'event_id' => $message->event_id,
                    'body' => $message->body,
                    'is_system' => $message->is_system,
                    'created_at' => $message->created_at?->toIso8601String(),
                    'likes_count' => 0,
                    'dislikes_count' => 0,
                    'my_reaction' => null,
                    'user' => [
                        'id' => $message->user?->id,
                        'name' => $message->user?->name,
                        'public_id' => $message->user?->public_id,
                        'initials' => user_initials($message->user?->name),
                        'profile_image_path' => $message->user?->profile_image_path,
                    ],
                ],
            ], 201);
        }

        return redirect()->to(event_route('events.chat', $event));
    }

    public function react(
        ReactToEventMessageRequest $request,
        Event $event,
        string $slug,
        EventMessage $message,
        ReactToEventMessageService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $result = $service->react(
                $request->user(),
                $event,
                $message,
                $request->input('type'),
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $result['message']->id,
                'event_id' => $result['message']->event_id,
                'likes_count' => $result['likes_count'],
                'dislikes_count' => $result['dislikes_count'],
                'my_reaction' => $result['my_reaction'],
            ],
        ]);
    }

    public function reactions(
        Event $event,
        string $slug,
        EventMessage $message,
        ListMessageReactionsService $service,
    ): JsonResponse {
        $this->authorize('chat', $event);

        try {
            $lists = $service->list(request()->user(), $event, $message);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['data' => $lists]);
    }
}
