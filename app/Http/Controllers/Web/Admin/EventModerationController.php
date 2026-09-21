<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Admin\ModerateEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventModerationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $events = Event::query()
            ->with(['category', 'city', 'creator:id,name'])
            ->withCount('members')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.events.index', compact('events', 'status'));
    }

    public function cancel(Event $event, ModerateEventService $service): RedirectResponse
    {
        try {
            $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.event_cancelled'));
    }

    public function complete(Event $event, ModerateEventService $service): RedirectResponse
    {
        try {
            $service->complete(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.event_completed'));
    }
}
