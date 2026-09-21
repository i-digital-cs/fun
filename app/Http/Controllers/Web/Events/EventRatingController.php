<?php

namespace App\Http\Controllers\Web\Events;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Events\StoreEventRatingRequest;
use App\Models\Event;
use App\Services\Events\RateEventCreatorService;
use Illuminate\Http\RedirectResponse;

class EventRatingController extends Controller
{
    public function store(
        StoreEventRatingRequest $request,
        Event $event,
        RateEventCreatorService $service,
    ): RedirectResponse {
        $this->authorize('rate', $event);

        try {
            $service->rate(
                $request->user(),
                $event,
                (int) $request->validated('stars'),
                $request->validated('comment'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['rating' => $exception->getMessage()]);
        }

        return back()->with('status', __('ratings.submitted'));
    }
}
