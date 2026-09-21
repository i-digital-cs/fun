@props([
    'event',
    'joinAction' => null,
])

@php
    $startsAt = $event->starts_at;
    if ($startsAt?->isToday()) {
        $when = __('events.today');
    } elseif ($startsAt?->isTomorrow()) {
        $when = __('events.tomorrow');
    } else {
        $when = $startsAt?->timezone(config('app.timezone'))->translatedFormat('M j');
    }
    $time = $startsAt?->timezone(config('app.timezone'))->format('g:i A');
    $action = $joinAction?->value;
@endphp

<article {{ $attributes->merge(['class' => 'flex h-full flex-col rounded-2xl border border-border bg-surface p-5 transition hover:border-primary/40 hover:shadow-sm']) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-primary">
        {{ $event->category?->localizedName() }}
    </p>
    <h2 class="mt-2 text-lg font-semibold leading-snug text-text">
        <a href="{{ route('events.show', $event) }}" class="hover:text-primary">
            {{ $event->title }}
            @if ($event->city)
                <span class="font-normal text-muted">— {{ $event->city->localizedName() }}</span>
            @endif
        </a>
    </h2>
    <p class="mt-2 text-sm text-muted">{{ $event->venue }}</p>
    <p class="mt-1 text-sm text-muted">{{ $when }}@if($time) · {{ $time }}@endif</p>
    @if ($event->distance_km !== null)
        <p class="mt-1 text-xs text-muted">{{ __('events.distance_away', ['km' => $event->distance_km]) }}</p>
    @endif
    <p class="mt-3 text-sm font-medium">
        {{ $event->members_count }} / {{ $event->max_participants }} {{ __('events.members') }}
    </p>
    @if ($event->cost_per_person !== null)
        <p class="mt-1 text-sm text-muted">{{ __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]) }}</p>
    @endif

    <div class="mt-auto flex flex-wrap items-center gap-2 pt-4">
        <a href="{{ route('events.show', $event) }}" class="rounded-lg bg-primary px-3 py-2 text-sm font-semibold text-white">
            {{ __('events.view_event') }}
        </a>
        @if ($action === 'request_pending')
            <span class="rounded-lg border border-border px-3 py-2 text-sm font-medium text-muted">{{ __('events.request_pending') }}</span>
        @elseif ($action === 'joined')
            <span class="rounded-lg bg-success/10 px-3 py-2 text-sm font-medium text-success">{{ __('events.joined') }}</span>
        @elseif ($action === 'request_rejected')
            <span class="rounded-lg border border-warning/40 px-3 py-2 text-sm font-medium text-warning">{{ __('events.request_rejected') }}</span>
        @elseif ($action === 'event_full')
            <span class="rounded-lg border border-border px-3 py-2 text-sm font-medium text-muted">{{ __('events.event_full') }}</span>
        @endif
    </div>
</article>
