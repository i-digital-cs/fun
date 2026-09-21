<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Models\Event;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\PostEventMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventChatController extends Controller
{
    public function show(Event $event, ListEventMessagesService $listService): View
    {
        $this->authorize('chat', $event);

        $messages = $listService->list(request()->user(), $event, 50);

        return view('events.chat', [
            'event' => $event->load(['category', 'city']),
            'messages' => $messages->getCollection()->reverse()->values(),
            'paginator' => $messages,
        ]);
    }

    public function store(
        StoreEventMessageRequest $request,
        Event $event,
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
                    'user' => [
                        'id' => $message->user?->id,
                        'name' => $message->user?->name,
                    ],
                ],
            ], 201);
        }

        return redirect()->route('events.chat', $event);
    }
}
