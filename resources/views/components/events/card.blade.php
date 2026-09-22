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
        $when = $startsAt?->timezone(config('app.timezone'))->translatedFormat('D');
    }
    $time = $startsAt?->timezone(config('app.timezone'))->format('g:i A');
    $action = $joinAction?->value;
    $members = (int) $event->members_count;
    $max = max(1, (int) $event->max_participants);
    $pct = (int) min(100, round(($members / $max) * 100));
    $host = $event->creator;
    $hostInitials = $host
        ? collect(preg_split('/\s+/', trim($host->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
        : '';
    $barColor = $pct >= 75 ? 'bg-accent' : 'bg-primary';
@endphp

<article {{ $attributes->merge(['class' => 'card-event flex h-full flex-col overflow-hidden rounded-2xl border-0 bg-surface shadow-sm']) }}>
    <div class="relative">
        @if ($event->imageUrl())
            <a href="{{ route('events.show', $event) }}" class="block aspect-[16/10] overflow-hidden bg-background">
                <img src="{{ $event->imageUrl() }}" alt="" class="h-40 w-full object-cover" loading="lazy">
            </a>
        @else
            <div class="h-40 bg-gradient-to-br from-primary-soft to-slate-100" aria-hidden="true"></div>
        @endif
        <span class="absolute start-2.5 top-2.5 rounded-md bg-surface/95 px-2 py-0.5 text-xs font-semibold text-text shadow-sm">
            {{ $event->category?->localizedName() }}
        </span>
        @if ($event->cost_per_person !== null)
            <span class="absolute bottom-2.5 end-2.5 rounded-md bg-slate-900/80 px-2 py-0.5 text-xs font-semibold text-white">
                {{ __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]) }}
            </span>
        @else
            <span class="absolute bottom-2.5 end-2.5 rounded-md bg-success px-2 py-0.5 text-xs font-semibold text-white">
                {{ __('events.free') }}
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-4">
        <p class="mb-1 text-sm font-semibold text-primary">
            <span class="inline-flex items-center gap-1">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 11H7v-2h4V5h2v8z"/></svg>
                {{ $when }}@if($time) · {{ $time }}@endif
            </span>
        </p>
        <h3 class="text-base font-semibold leading-snug text-text">
            <a href="{{ route('events.show', $event) }}" class="hover:text-primary">
                {{ $event->title }}
                @if ($event->city)
                    <span class="font-normal text-muted">— {{ $event->city->localizedName() }}</span>
                @endif
            </a>
        </h3>
        <p class="mt-1 truncate text-sm text-muted">
            <span class="inline-flex max-w-full items-center gap-1">
                <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <span class="truncate">{{ $event->venue }}</span>
            </span>
        </p>

        <div class="mt-3 mb-3 rounded-xl bg-background p-2">
            <div class="mb-1 flex justify-between text-xs">
                <span class="text-muted">{{ __('home.attendance') }}</span>
                <span class="font-semibold text-text">{{ __('home.attendance_count', ['current' => $members, 'max' => $event->max_participants]) }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-border/60" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary-soft text-[0.7rem] font-bold text-primary">{{ $hostInitials }}</span>
                <span class="truncate text-sm font-medium text-text">{{ $host?->name }}</span>
            </div>
            <span class="shrink-0 text-sm font-semibold text-success">{{ __('home.host') }}</span>
        </div>

        <div class="mt-auto space-y-2">
            @if ($action === 'request_pending')
                <span class="block rounded-lg border border-border px-3 py-2 text-center text-sm font-medium text-muted">{{ __('events.request_pending') }}</span>
            @elseif ($action === 'joined')
                <span class="block rounded-lg bg-success/10 px-3 py-2 text-center text-sm font-medium text-success">{{ __('events.joined') }}</span>
            @elseif ($action === 'request_rejected')
                <span class="block rounded-lg border border-warning/40 px-3 py-2 text-center text-sm font-medium text-warning">{{ __('events.request_rejected') }}</span>
            @elseif ($action === 'event_full')
                <span class="block rounded-lg border border-border px-3 py-2 text-center text-sm font-medium text-muted">{{ __('events.event_full') }}</span>
            @endif
            <a href="{{ route('events.show', $event) }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-white shadow-sm hover:brightness-95">
                {{ __('events.view_event') }}
                <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</article>
