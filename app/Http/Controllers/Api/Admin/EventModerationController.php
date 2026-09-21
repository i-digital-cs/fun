<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Admin\ModerateEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventModerationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = $request->string('status')->toString();

        $events = Event::query()
            ->with(['category:id,name_en,name_ar', 'city:id,name_en,name_ar', 'creator:id,name'])
            ->withCount('members')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return response()->json($events);
    }

    public function cancel(Event $event, ModerateEventService $service): JsonResponse
    {
        try {
            $updated = $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.event_cancelled'),
            'data' => ['id' => $updated->id, 'status' => $updated->status->value],
        ]);
    }

    public function complete(Event $event, ModerateEventService $service): JsonResponse
    {
        try {
            $updated = $service->complete(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('admin.event_completed'),
            'data' => ['id' => $updated->id, 'status' => $updated->status->value],
        ]);
    }
}
