@props([
    'cityName' => null,
    'distanceKm' => 25,
    'hasNearbyOrigin' => false,
    'latitude' => null,
    'longitude' => null,
    'discoveryMode' => 'nationwide',
    'suggestedEvents' => null,
])

@php
    $suggestedEvents = $suggestedEvents ?? collect();
    $label = $cityName
        ? __('dashboard.mini_map_near', ['place' => $cityName, 'km' => (int) $distanceKm])
        : __('dashboard.mini_map_nationwide');
@endphp

<section class="mt-7" data-home-mini-map
         data-lat="{{ $latitude }}"
         data-lng="{{ $longitude }}"
         data-distance-km="{{ (int) $distanceKm }}"
         data-discovery-mode="{{ $discoveryMode }}"
         data-i18n-empty="{{ __('dashboard.mini_map_empty') }}"
         data-i18n-map-error="{{ __('dashboard.mini_map_error') }}">
    <div class="mb-3.5 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">{{ __('dashboard.explore_around_you') }}</h2>
            <p class="mt-0.5 text-[12px] text-slate-500">{{ __('dashboard.explore_around_you_sub') }}</p>
        </div>
        <a href="{{ route('events.index') }}"
           class="inline-flex items-center rounded-xl bg-[#7138F5] px-3.5 py-2 text-xs font-bold text-white no-underline shadow-sm hover:bg-[#5825D6]">
            {{ __('dashboard.explore_full_map') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3.5 py-2.5">
            <p class="truncate text-xs font-semibold text-slate-700">{{ $label }}</p>
            <p class="shrink-0 text-[10px] font-medium text-slate-400" data-home-mini-map-status></p>
        </div>
        <div data-home-mini-map-canvas class="h-[300px] w-full bg-slate-100 lg:h-[320px]" style="min-height: 300px;"></div>
        @if ($suggestedEvents->isNotEmpty())
            <ul class="divide-y divide-slate-100">
                @foreach ($suggestedEvents->take(4) as $event)
                    <li>
                        <a href="{{ event_show_url($event) }}" class="flex items-center gap-3 px-3.5 py-2.5 no-underline transition hover:bg-[#F8F9FE]">
                            <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                <x-events.cover-img :event="$event" size="card" class="h-full w-full object-cover" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-bold text-slate-900">{{ $event->title }}</span>
                                <span class="block truncate text-[11px] text-slate-500">
                                    {{ $event->starts_at?->timezone(config('app.timezone'))->translatedFormat('M j · g:i A') }}
                                    @if ($event->city) · {{ $event->city->localizedName() }} @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>

@once
    @push('scripts')
        @vite(['resources/js/home-mini-map.js'])
    @endpush
@endonce
