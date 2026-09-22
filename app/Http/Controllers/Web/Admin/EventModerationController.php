<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEventAdminRequest;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Services\Admin\ModerateEventService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function calendar(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $cursor = Carbon::create($year, $month, 1)->startOfDay();
        $rangeStart = $cursor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $rangeEnd = $cursor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $status = $request->string('status')->toString();
        $categoryId = $request->integer('category_id') ?: null;
        $cityId = $request->integer('city_id') ?: null;
        $q = trim($request->string('q')->toString());

        $allowedStatuses = array_column(EventStatus::cases(), 'value');
        if ($status !== '' && ! in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        $events = Event::query()
            ->with(['category', 'city', 'creator:id,name'])
            ->withCount('members')
            ->whereBetween('starts_at', [$rangeStart, $rangeEnd])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->when($cityId, fn ($query) => $query->where('city_id', $cityId))
            ->when($q !== '', function ($query) use ($q) {
                $term = '%'.$q.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->orderBy('starts_at')
            ->get();

        /** @var Collection<string, Collection<int, Event>> $eventsByDay */
        $eventsByDay = $events->groupBy(fn (Event $event) => $event->starts_at->timezone(config('app.timezone'))->format('Y-m-d'));

        $weeks = [];
        $day = $rangeStart->copy();
        while ($day <= $rangeEnd) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $day->format('Y-m-d');
                $week[] = [
                    'date' => $day->copy(),
                    'in_month' => $day->month === $month,
                    'is_today' => $day->isToday(),
                    'events' => $eventsByDay->get($key, collect()),
                ];
                $day->addDay();
            }
            $weeks[] = $week;
        }

        $prev = $cursor->copy()->subMonth();
        $next = $cursor->copy()->addMonth();

        return view('admin.events.calendar', [
            'cursor' => $cursor,
            'weeks' => $weeks,
            'prev' => $prev,
            'next' => $next,
            'status' => $status,
            'categoryId' => $categoryId,
            'cityId' => $cityId,
            'q' => $q,
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name_en')->get(),
            'cities' => City::query()->orderBy('name_en')->get(),
            'eventCount' => $events->count(),
        ]);
    }

    public function edit(Event $event): View
    {
        $this->authorize('moderate', $event);

        return view('admin.events.edit', [
            'event' => $event->load(['category', 'city', 'creator']),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name_en')->get(),
            'cities' => City::query()->orderBy('name_en')->get(),
            'statuses' => EventStatus::cases(),
        ]);
    }

    public function update(
        UpdateEventAdminRequest $request,
        Event $event,
        ModerateEventService $service,
    ): RedirectResponse {
        $this->authorize('moderate', $event);

        try {
            $service->update(
                $request->user(),
                $event,
                $request->safe()->except('image'),
                $request->file('image')
            );
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors(['event' => $exception->getMessage()]);
        }

        return redirect()->route('admin.events.index')
            ->with('status', __('admin.event_updated'));
    }

    public function destroy(Event $event, ModerateEventService $service): RedirectResponse
    {
        $this->authorize('moderate', $event);

        try {
            $service->delete(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return redirect()->route('admin.events.index')
            ->with('status', __('admin.event_deleted'));
    }

    public function cancel(Event $event, ModerateEventService $service): RedirectResponse
    {
        $this->authorize('moderate', $event);

        try {
            $service->cancel(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.event_cancelled'));
    }

    public function complete(Event $event, ModerateEventService $service): RedirectResponse
    {
        $this->authorize('moderate', $event);

        try {
            $service->complete(request()->user(), $event);
        } catch (DomainException $exception) {
            return back()->withErrors(['event' => $exception->getMessage()]);
        }

        return back()->with('status', __('admin.event_completed'));
    }
}
