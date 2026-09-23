@props([
    'event',
    'joinAction' => null,
    'favorited' => false,
])

@php
    $members = (int) $event->members_count;
    $max = max(1, (int) $event->max_participants);
    $pct = (int) min(100, round(($members / $max) * 100));
    $urgent = $pct >= 80;
    $categoryName = $event->category?->localizedName() ?? '';
    $emoji = category_emoji($categoryName);
    $isFree = blank($event->cost_per_person) || (float) $event->cost_per_person <= 0;
    $priceLabel = $isFree
        ? __('events.free')
        : __('events.cost_per_person_amount', ['amount' => $event->cost_per_person]);
    $hostInitials = user_initials($event->creator?->name);
    $action = $joinAction?->value;

    if ($pct >= 80) {
        $urgencyKey = 'almost_full';
        $urgencyClass = 'text-orange-600';
    } elseif ($pct >= 60) {
        $urgencyKey = 'filling_fast';
        $urgencyClass = 'text-rose-600';
    } elseif ($event->created_at && $event->created_at->gte(now()->subDays(3))) {
        $urgencyKey = 'new_badge';
        $urgencyClass = 'text-emerald-600';
    } else {
        $urgencyKey = 'popular';
        $urgencyClass = 'text-violet-600';
    }
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-indigo-600/10']) }}>
    <div class="relative h-44 overflow-hidden">
        <a href="{{ route('events.show', $event) }}" class="block h-full">
            <img src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
                 alt="{{ $event->title }}"
                 class="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
                 loading="lazy">
        </a>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-950/50 via-transparent to-slate-950/10"></div>

        <span class="absolute start-3 top-3 flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-bold text-slate-800 shadow-md backdrop-blur">
            <span class="text-sm leading-none">{{ $emoji }}</span>
            {{ $categoryName }}
        </span>
        <span @class([
            'absolute end-3 top-3 rounded-full px-3 py-1.5 text-[11px] font-black text-white shadow-lg',
            'bg-gradient-to-r from-emerald-500 to-teal-500 shadow-emerald-600/40' => $isFree,
            'bg-gradient-to-r from-orange-500 to-amber-500 shadow-orange-600/40' => ! $isFree,
        ])>
            {{ $priceLabel }}
        </span>
        <span class="absolute bottom-3 start-3 flex items-center gap-1 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-black shadow backdrop-blur">
            @if ($urgencyKey === 'almost_full')
                <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M13.5.67s.74 2.65.74 4.8c0 2.06-1.35 3.73-3.41 3.73-2.07 0-3.63-1.67-3.63-3.73 0-2.15.74-4.8.74-4.8S10.5 0 12 0s1.5.67 1.5.67z"/>
                </svg>
            @elseif ($urgencyKey === 'filling_fast')
                <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"/>
                </svg>
            @elseif ($urgencyKey === 'new_badge')
                <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                </svg>
            @else
                <svg class="h-3 w-3 {{ $urgencyClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M3 17l6-6 4 4 8-8"/>
                </svg>
            @endif
            <span class="{{ $urgencyClass }}">{{ __('design.' . $urgencyKey) }}</span>
        </span>
        <div class="absolute end-3 bottom-3 z-10 drop-shadow-md">
            <x-events.favorite-bookmark :event="$event" :favorited="$favorited" id-suffix="rich" />
        </div>

    </div>

    <div class="flex flex-1 flex-col gap-3 p-5">
        <span class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-indigo-600">
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · M j · g:i A') }}
        </span>
        <h3 class="text-base font-extrabold leading-snug tracking-tight text-slate-900 transition group-hover:text-indigo-700">
            <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
        </h3>
        <p class="flex items-center gap-1.5 truncate text-xs text-slate-500">
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5.5z"/>
            </svg>
            {{ $event->venue }}
        </p>

        <div class="mt-1 rounded-2xl border border-slate-100 bg-slate-50/80 p-3">
            <div class="mb-1.5 flex items-center justify-between text-xs">
                <span class="font-medium text-slate-500">{{ __('design.members') }}</span>
                <span class="font-black text-slate-800">{{ $members }} / {{ $max }}</span>
            </div>
            <div class="h-1.5 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                <div @class([
                    'h-full rounded-full',
                    'bg-gradient-to-r from-orange-500 to-amber-500' => $urgent,
                    'bg-gradient-to-r from-indigo-500 to-violet-500' => ! $urgent,
                ]) style="width: {{ $pct }}%"></div>
            </div>
            <div class="mt-2.5 flex items-center justify-between">
                <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-white bg-gradient-to-br from-indigo-500 to-violet-600 text-[9px] font-bold text-white">{{ $hostInitials }}</span>
                <span class="truncate text-[10px] font-semibold text-slate-400">{{ $event->creator?->name }}</span>
            </div>
        </div>

        <div class="mt-auto space-y-2">
            @if ($action === 'request_pending')
                <span class="block rounded-xl border border-slate-200/70 bg-slate-50 px-3 py-2 text-center text-sm font-medium text-slate-500">{{ __('events.request_pending') }}</span>
            @elseif ($action === 'joined')
                <span class="block rounded-xl bg-emerald-50 px-3 py-2 text-center text-sm font-medium text-emerald-700">{{ __('events.joined') }}</span>
            @elseif ($action === 'request_rejected')
                <span class="block rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-center text-sm font-medium text-amber-700">{{ __('events.request_rejected') }}</span>
            @elseif ($action === 'event_full')
                <span class="block rounded-xl border border-slate-200/70 bg-slate-50 px-3 py-2 text-center text-sm font-medium text-slate-500">{{ __('events.event_full') }}</span>
            @endif
            <a href="{{ route('events.show', $event) }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105 active:scale-[0.98]">
                {{ __('events.view_event') }}
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M5 12h14M13 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</article>
