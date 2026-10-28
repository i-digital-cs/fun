@props([
    'event',
    'joinAction' => null,
    'favorited' => false,
    'compact' => false,
])

@php
    $categoryName = $event->category?->localizedName() ?? '';
    $cityName = $event->city?->localizedName() ?? '';
    $starts = $event->starts_at?->timezone(config('app.timezone'));
    $schedule = $starts
        ? ($starts->isToday()
            ? __('events.today').' · '.$starts->translatedFormat('g:i A')
            : ($starts->isTomorrow()
                ? __('events.tomorrow').' · '.$starts->translatedFormat('g:i A')
                : $starts->translatedFormat('D · M j · g:i A')))
        : '';
    $cover = method_exists($event, 'coverUrl') ? $event->coverUrl('card') : null;
    $isFree = method_exists($event, 'isFree')
        ? $event->isFree()
        : ((float) ($event->cost_per_person ?? 0) <= 0);
    $price = $isFree
        ? __('events.free')
        : number_format((float) ($event->cost_per_person ?? 0), 0).' '.__('events.currency_short');
    $members = (int) ($event->members_count ?? 0);
    $max = (int) $event->max_participants;
    $pct = $max > 0 ? min(100, (int) round(($members / $max) * 100)) : 0;
    $distanceKm = isset($event->distance_km) ? (float) $event->distance_km : null;
    $place = method_exists($event, 'locationLabel')
        ? $event->locationLabel()
        : ($event->venue ?: $cityName);
    $showUrl = event_show_url($event);
    $full = $joinAction === 'event_full' || ($max > 0 && $members >= $max);
@endphp

<article {{ $attributes->class([
    'group flex flex-col overflow-hidden rounded-[1.15rem] border border-[#E8EAF2] bg-white shadow-[0_12px_28px_-22px_rgba(25,33,61,0.45)] transition hover:-translate-y-0.5 hover:shadow-[0_18px_36px_-20px_rgba(113,56,245,0.28)]',
]) }}>
    <a href="{{ $showUrl }}" class="relative block aspect-[16/10] overflow-hidden no-underline">
        @if ($cover)
            <img src="{{ $cover }}" alt="" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy" width="480" height="300">
        @else
            <div class="flex h-full w-full items-center justify-center bg-[#EEF2FF] text-3xl">{{ category_emoji($categoryName) }}</div>
        @endif
        <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/35 to-transparent"></div>
        @if ($categoryName !== '')
            <span class="absolute start-2.5 top-2.5 rounded-full bg-white/95 px-2.5 py-1 text-[10px] font-extrabold text-[#19213D] shadow-sm">
                {{ category_emoji($categoryName) }} {{ $categoryName }}
            </span>
        @endif
        @auth
            <div class="absolute end-2.5 top-2.5" onclick="event.stopPropagation()">
                <x-events.favorite-bookmark :event="$event" :favorited="$favorited" id-suffix="social" />
            </div>
        @endauth
    </a>

    <div class="flex flex-1 flex-col gap-1.5 p-3.5">
        <a href="{{ $showUrl }}" class="no-underline">
            <h3 class="line-clamp-2 text-[14px] font-extrabold leading-snug tracking-tight text-[#19213D]" dir="{{ bidi_dir($event->title) }}">
                {{ $event->title }}
            </h3>
        </a>
        @if ($schedule !== '')
            <p class="text-[11px] font-semibold text-[#7138F5]/90">{{ $schedule }}</p>
        @endif
        <p class="truncate text-[11px] text-slate-500">
            {{ $place }}
            @if ($distanceKm !== null)
                · ~{{ $distanceKm >= 10 ? number_format($distanceKm, 0) : number_format($distanceKm, 1) }} {{ __('location.km') }}
            @elseif ($cityName !== '')
                · {{ $cityName }}
            @endif
        </p>

        <div class="mt-1.5 flex items-center gap-2">
            <div class="h-1.5 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-[#7138F5]" style="width: {{ $pct }}%"></div>
            </div>
            <span class="shrink-0 text-[10px] font-bold text-slate-500">{{ $members }}/{{ $max }}</span>
        </div>

        <div class="mt-auto flex items-center justify-between gap-2 pt-2">
            <span @class([
                'rounded-full px-2.5 py-1 text-[10px] font-extrabold',
                'bg-emerald-50 text-emerald-700' => $isFree && ! $full,
                'bg-orange-50 text-orange-700' => ! $isFree && ! $full,
                'bg-slate-100 text-slate-600' => $full,
            ])>
                {{ $full ? __('events.event_full') : $price }}
            </span>
            <a href="{{ $showUrl }}" class="text-[11px] font-extrabold text-[#FF762E] no-underline hover:text-[#E85F1A]">
                {{ __('events.view_event') }} →
            </a>
        </div>
    </div>
</article>
