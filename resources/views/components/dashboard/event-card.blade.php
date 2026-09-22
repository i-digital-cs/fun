@props([
    'event',
    'joinAction' => null,
])

@php
    $members = (int) $event->members_count;
    $max = max(1, (int) $event->max_participants);
    $categoryName = $event->category?->localizedName() ?? '';
    $emoji = category_emoji($categoryName);
    $isFree = blank($event->cost_per_person) || (float) $event->cost_per_person <= 0;
    $costLabel = $isFree
        ? __('events.free')
        : __('events.cost_per_person_amount', ['amount' => number_format((float) $event->cost_per_person, 0)]);
    $startsAt = $event->starts_at?->timezone(config('app.timezone'));
    $timeLabel = $startsAt
        ? ($startsAt->isToday()
            ? __('events.today') . ' · ' . $startsAt->translatedFormat('g:i A')
            : ($startsAt->isTomorrow()
                ? __('events.tomorrow') . ' · ' . $startsAt->translatedFormat('g:i A')
                : $startsAt->translatedFormat('D · M j · g:i A')))
        : '';
    $distanceLabel = isset($event->distance_km)
        ? number_format((float) $event->distance_km, 1) . ' km'
        : null;
@endphp

<article {{ $attributes->merge(['class' => 'group overflow-hidden rounded-[17px] border border-[#e7ebf3] bg-white shadow-[0_5px_20px_rgba(15,23,42,0.025)] transition hover:-translate-y-0.5 hover:shadow-[0_12px_35px_rgba(15,23,42,0.06)]']) }}>
    <div class="relative h-[157px] overflow-hidden">
        <img src="{{ event_cover_url($event->imageUrl(), $event->id) }}"
             alt="{{ $event->title }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
             loading="lazy">
        <span class="absolute start-3 top-3 rounded-full bg-slate-900/78 px-2.5 py-1.5 text-[10px] font-bold text-white">
            {{ $categoryName }}
        </span>
        @if ($distanceLabel)
            <span class="absolute end-3 top-3 rounded-full bg-slate-900/68 px-2.5 py-1.5 text-[10px] font-medium text-white">
                {{ $distanceLabel }}
            </span>
        @endif
    </div>

    <div class="p-3.5">
        <h3 class="mb-2 text-[15px] font-extrabold leading-snug text-slate-900">
            <a href="{{ route('events.show', $event) }}" class="text-inherit no-underline hover:text-indigo-600">{{ $event->title }}</a>
        </h3>

        <p class="mb-1.5 flex items-center gap-1.5 text-[11px] text-slate-500">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                <circle cx="12" cy="9" r="2.5"/>
            </svg>
            {{ $event->venue }}
        </p>

        <p class="mb-1.5 flex items-center gap-1.5 text-[11px] text-slate-500">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/>
            </svg>
            {{ $timeLabel }}
        </p>

        <p class="flex items-center gap-1.5 text-[11px] text-slate-500">
            <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M17 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path stroke-linecap="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
            {{ $members }} / {{ $max }} {{ __('design.members') }}
        </p>

        <div class="mt-2.5 flex items-center justify-between gap-2">
            <span class="text-[11px] font-extrabold text-slate-800">{{ $costLabel }}</span>
            <a href="{{ route('events.show', $event) }}"
               class="rounded-lg bg-indigo-50 px-2.5 py-2 text-[10px] font-extrabold text-indigo-600 no-underline transition hover:bg-indigo-100">
                {{ __('dashboard.view_event') }}
            </a>
        </div>
    </div>
</article>
