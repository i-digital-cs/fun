<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\Chat\StoreEventMessageRequest;
use App\Models\Category;
use App\Models\Event;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\MarkEventChatReadService;
use App\Services\Events\Chat\PostEventMessageService;
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
            'event' => $event->load(['category', 'city']),
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
}
