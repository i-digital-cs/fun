@props([
    'cityName' => null,
    'distanceKm' => 25,
    'hasNearbyOrigin' => false,
    'latitude' => null,
    'longitude' => null,
    'discoveryMode' => 'nationwide',
    'suggestedEvents' => null,
    'expanded' => false,
])

@php
    $suggestedEvents = collect($suggestedEvents ?? []);
    $label = $cityName && $hasNearbyOrigin
        ? __('dashboard.mini_map_near', ['place' => $cityName, 'km' => (int) $distanceKm])
        : __('dashboard.mini_map_nationwide');
@endphp

<section class="mt-7" data-home-mini-map
         data-lat="{{ $latitude }}"
         data-lng="{{ $longitude }}"
         data-distance-km="{{ (int) $distanceKm }}"
         data-discovery-mode="{{ $discoveryMode }}"
         data-location-label="{{ $cityName }}"
         data-i18n-empty="{{ __('dashboard.mini_map_empty') }}"
         data-i18n-map-error="{{ __('dashboard.mini_map_error') }}"
         data-i18n-loading="{{ __('dashboard.mini_map_loading') }}"
         data-i18n-tile-error="{{ __('dashboard.mini_map_tile_error') }}"
         data-i18n-retry="{{ __('dashboard.mini_map_retry') }}"
         data-i18n-approx="{{ __('events.city_level_preview') }}">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h2 class="text-[1.15rem] font-extrabold tracking-tight text-[#19213D]">{{ __('dashboard.explore_around_you') }}</h2>
            <p class="mt-0.5 text-[12px] text-slate-500">{{ __('dashboard.explore_around_you_sub') }}</p>
        </div>
        <a href="{{ route('events.index', array_filter(['view' => 'map'])) }}"
           class="inline-flex shrink-0 items-center rounded-xl bg-[#7138F5] px-3.5 py-2 text-xs font-bold text-white no-underline shadow-sm hover:bg-[#5825D6]">
            {{ __('dashboard.explore_full_map') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-3.5 py-2">
            <p class="truncate text-xs font-semibold text-slate-700">{{ $label }}</p>
            <p class="shrink-0 text-[10px] font-medium text-slate-400" data-home-mini-map-status></p>
        </div>

        <div data-home-mini-map-stage class="relative w-full">
            {{-- Compact preview: mobile ~230, desktop ~300. Inline min-height guards Leaflet collapse. --}}
            <div data-home-mini-map-canvas
                 class="home-mini-map__canvas w-full bg-slate-100"
                 style="height: 230px; min-height: 230px;"
                 role="img"
                 aria-label="{{ __('dashboard.explore_around_you') }}"></div>
        </div>
        <style>
            @media (min-width: 640px) {
                [data-home-mini-map-canvas].home-mini-map__canvas { height: 250px !important; min-height: 250px !important; }
            }
            @media (min-width: 1024px) {
                [data-home-mini-map-canvas].home-mini-map__canvas { height: 300px !important; min-height: 300px !important; }
            }
            [data-home-mini-map-canvas].leaflet-container { width: 100%; height: 100%; }
        </style>

        @if ($suggestedEvents->isNotEmpty())
            <ul class="divide-y divide-slate-100">
                @foreach ($suggestedEvents->take(4) as $event)
                    @php
                        $precision = $event->distance_precision ?? null;
                        $isApprox = $precision === 'city_level';
                        $dist = $event->distance_km ?? null;
                    @endphp
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
                                    @if ($dist !== null && $isApprox)
                                        · {{ __('events.approx_distance_city', ['km' => number_format((float) $dist, 1)]) }}
                                    @elseif ($dist !== null && $precision === 'exact')
                                        · {{ number_format((float) $dist, 1) }} {{ __('location.km') }}
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($expanded)
                <p class="border-t border-amber-100 bg-amber-50/80 px-3.5 py-2 text-[11px] font-medium text-amber-900">
                    {{ __('events.expanded_notice_short') }}
                </p>
            @endif
        @endif
    </div>
</section>

@once
    @push('scripts')
        @vite(['resources/js/home-mini-map.js'])
    @endpush
@endonce
