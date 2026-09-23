<?php

namespace App\Http\Controllers\Api\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Events\BuildEventTicketService;
use Illuminate\Http\JsonResponse;

class EventTicketController extends Controller
{
    public function show(Event $event, BuildEventTicketService $tickets): JsonResponse
    {
        $this->authorize('viewTicket', $event);

        try {
            $ticket = $tickets->forMember(request()->user(), $event);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        }

        return response()->json(['data' => $ticket]);
    }
}
