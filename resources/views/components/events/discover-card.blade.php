@props([
    'event',
    'selected' => false,
])

@php
    $isFree = blank($event->cost_per_person) || (float) $event->cost_per_person <= 0;
    $priceLabel = $isFree
        ? __('events.free')
        : __('events.cost_per_person_amount', [
            'amount' => number_format((float) $event->cost_per_person, 2, '.', ''),
        ]);
    $schedule = $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('D · M j · g:i A');
    $members = (int) ($event->members_count ?? 0);
@endphp

<article
    {{ $attributes->class([
        'group flex gap-2.5 rounded-lg border bg-white p-2 transition',
        'border-indigo-400 ring-2 ring-indigo-200' => $selected,
        'border-slate-200/80 hover:border-indigo-300 hover:shadow-sm' => ! $selected,
    ]) }}
>
    <a href="{{ event_show_url($event) }}" class="relative h-14 w-14 shrink-0 overflow-hidden rounded-md bg-slate-100" tabindex="-1" aria-hidden="true">
        <x-events.cover-img :event="$event" size="card" class="h-full w-full object-cover" />
    </a>
    <div class="min-w-0 flex-1">
        <h3 class="truncate text-[13px] font-bold leading-snug text-slate-900">
            <a href="{{ event_show_url($event) }}" dir="{{ bidi_dir($event->title) }}" class="bidi-text hover:text-indigo-700">{{ $event->title }}</a>
        </h3>
        @if ($schedule)
            <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $schedule }}</p>
        @endif
        <p class="mt-0.5 truncate text-[11px] text-slate-600" dir="{{ bidi_dir($event->locationLabel()) }}">{{ $event->locationLabel() }}</p>
        @if ($event->distance_km !== null)
            @php
                $distPrecision = $event->distance_precision ?? ($event->hasPlacePin() ? 'exact' : 'city_level');
                $distLabel = $distPrecision === 'exact'
                    ? __('events.exact_distance', ['km' => $event->distance_km])
                    : __('events.approx_distance_city', ['km' => $event->distance_km]);
            @endphp
            <p class="mt-0.5 truncate text-[10px] font-medium {{ $distPrecision === 'exact' ? 'text-indigo-700' : 'text-slate-500' }}">{{ $distLabel }}</p>
        @endif
        <div class="mt-1 flex flex-wrap items-center gap-1.5">
            <span @class([
                'rounded px-1.5 py-0.5 text-[10px] font-semibold',
                'bg-emerald-50 text-emerald-700' => $isFree,
                'bg-slate-100 text-slate-700' => ! $isFree,
            ])>{{ $priceLabel }}</span>
            @if ($members > 0)
                <span class="text-[10px] text-slate-500">{{ $members }}</span>
            @endif
            <a href="{{ event_show_url($event) }}" class="ms-auto text-[10px] font-semibold text-indigo-600 hover:text-indigo-800">
                {{ __('events.view_details') }}
            </a>
        </div>
    </div>
</article>
