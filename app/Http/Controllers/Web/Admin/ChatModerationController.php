<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventMessage;
use App\Services\Admin\DeleteEventMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ChatModerationController extends Controller
{
    public function index(Event $event): View
    {
        $messages = EventMessage::query()
            ->with('user:id,name')
            ->where('event_id', $event->id)
            ->latest()
            ->paginate(50);

        return view('admin.chat.index', compact('event', 'messages'));
    }

    public function destroy(
        Event $event,
        EventMessage $message,
        DeleteEventMessageService $service,
    ): RedirectResponse {
        abort_unless($message->event_id === $event->id, 404);

        try {
            $service->delete(request()->user(), $message);
        } catch (DomainException $exception) {
            return back()->withErrors(['message' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.message_deleted'));
    }
}
