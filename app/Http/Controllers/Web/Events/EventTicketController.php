<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Events\BuildEventTicketService;
use Illuminate\View\View;

class EventTicketController extends Controller
{
    public function show(Event $event, string $slug, BuildEventTicketService $tickets): View
    {
        $this->authorize('viewTicket', $event);

        try {
            $ticket = $tickets->forMember(request()->user(), $event);
        } catch (DomainException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('events.ticket', [
            'event' => $event->loadMissing(['city', 'category']),
            'ticket' => $ticket,
        ]);
    }
}
