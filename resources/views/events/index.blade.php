@extends('layouts.dashboard')

@section('title', __('events.discover'))

@section('content')
    @php
        $discoveryMode = $discoveryMode ?? ($filters['discovery_mode'] ?? 'nationwide');
        $activeLat = $filters['latitude'] ?? null;
        $activeLng = $filters['longitude'] ?? null;
        $activeDevice = $discoveryMode === 'device' && $activeLat !== null && $activeLng !== null;
        $activeCityName = $filterCity?->localizedName();
        $activeKm = (int) ($filters['distance_km'] ?? config('location.default_distance_km'));
        if ($discoveryMode === 'nationwide') {
            $activeKm = 0;
        }
        $radiusLabel = $activeKm === 0
            ? __('location.all_distances')
            : __('location.within_km', ['km' => $activeKm]);
        if ($activeDevice) {
            $locationChipLabel = __('location.current_location').' · '.$radiusLabel;
        } elseif ($discoveryMode === 'manual' && $activeCityName) {
            $locationChipLabel = $activeCityName.' · '.$radiusLabel;
        } else {
            // Nationwide: never append a radius label.
            $locationChipLabel = __('location.all_egypt');
        }

        if ($activeDevice) {
            $resultsHeading = __('events.nearby_events');
        } elseif ($discoveryMode === 'manual' && $activeCityName) {
            $resultsHeading = __('events.events_near_place', ['place' => $activeCityName]);
        } else {
            $resultsHeading = __('events.events_in_egypt');
        }

        $discoveryBoot = [
            'mode' => $discoveryMode,
            'cityId' => $filters['city_id'] ?? null,
            'cityName' => $activeCityName,
            'distanceKm' => $discoveryMode === 'nationwide' ? 0 : $activeKm,
            'lat' => $activeDevice ? $activeLat : null,
            'lng' => $activeDevice ? $activeLng : null,
            'accuracyM' => $activeDevice ? ($filters['accuracy_m'] ?? null) : null,
            'locationLabel' => null,
            'shortLabel' => null,
        ];
        $viewMode = request('view', 'map') === 'list' ? 'list' : 'map';
        $sort = request('sort', 'soonest');
        $resultCount = method_exists($events, 'total') ? $events->total() : $events->count();
        $control = 'h-9 rounded-lg border border-slate-200 bg-white px-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100';
        $useLivingMap = (bool) config('living_map.replace_discover_map');
        $livingMapLat = $activeDevice
            ? $activeLat
            : ($filterCity?->latitude);
        $livingMapLng = $activeDevice
            ? $activeLng
            : ($filterCity?->longitude);
        $livingMapMode = $activeDevice
            ? 'device'
            : (($discoveryMode === 'manual' && $livingMapLat !== null && $livingMapLng !== null)
                ? 'manual'
                : 'nationwide');
        $livingMapKm = $discoveryMode === 'nationwide' ? 0 : $activeKm;
    @endphp

    <script type="application/json" id="discovery-location-boot"
            data-label-current="{{ __('location.current_location') }}"
            data-label-nationwide="{{ __('location.all_egypt') }}"
            data-label-all-distances="{{ __('location.all_distances') }}"
            data-label-within="{{ __('location.within_km') }}"
            data-label-chip-place="{{ __('location.chip_current_with_place') }}"
            data-label-detecting="{{ __('location.detecting_location') }}"
            data-label-detected="{{ __('location.location_detected') }}"
            data-label-area-unavailable="{{ __('location.area_name_unavailable') }}"
            data-label-accuracy="{{ __('location.accuracy_meters') }}"
            data-label-low-accuracy="{{ __('location.gps_low_accuracy') }}"
            data-reverse-url="{{ url('/api/places/reverse') }}"
    >{!! json_encode($discoveryBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

    <div
        data-live-search
        data-live-search-mode="page"
        data-live-search-target="#discover-results"
        data-live-search-url="{{ route('search.suggest') }}"
        data-live-search-searching-label="{{ __('ui.searching') }}"
        class="flex min-h-0 flex-col gap-2"
        data-discover-workspace
        data-discover-view="{{ $viewMode }}"
    >
        {{-- One coherent toolbar: Search | Location | Radius | Category | Apply | Map/List --}}
        <form method="GET" action="{{ route('events.index') }}"
              class="flex flex-wrap items-center gap-1.5 rounded-xl border border-slate-200/80 bg-white p-1.5 shadow-sm sm:flex-nowrap"
              data-discover-filters
              data-discover-toolbar>
            <input type="hidden" name="discovery_mode" value="{{ $discoveryMode }}">
            <input type="hidden" name="view" value="{{ $viewMode }}" data-discover-view-input>
            <input type="hidden" name="city_id" id="city_id" value="{{ $filters['city_id'] }}" @if ($activeDevice || $discoveryMode === 'nationwide') disabled @endif>
            @if ($activeDevice)
                <input type="hidden" name="latitude" value="{{ $activeLat }}">
                <input type="hidden" name="longitude" value="{{ $activeLng }}">
                @if (! empty($filters['accuracy_m']))
                    <input type="hidden" name="accuracy_m" value="{{ $filters['accuracy_m'] }}">
                @endif
            @endif

            <div class="relative min-w-0 flex-1 basis-full sm:basis-auto sm:min-w-[11rem]">
                <label for="q" class="sr-only">{{ __('events.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}"
                       placeholder="{{ __('events.search_placeholder') }}"
                       autocomplete="off" data-live-search-input
                       class="{{ $control }} w-full bg-slate-50/70">
            </div>

            <button type="button"
                    @auth data-location-modal-open @else data-discover-use-gps @endauth
                    data-location-unavailable="{{ __('events.location_unavailable') }}"
                    data-location-denied="{{ __('events.location_permission_denied') }}"
                    data-location-timeout="{{ __('location.gps_timeout') }}"
                    class="inline-flex h-9 max-w-[13rem] shrink-0 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-sm font-semibold text-slate-800 hover:border-indigo-300 hover:text-indigo-700"
                    title="{{ __('location.location_settings') }}">
                <svg class="h-3.5 w-3.5 shrink-0 text-indigo-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                <span class="truncate" data-discovery-chip-label>{{ $locationChipLabel }}</span>
            </button>

            <div class="shrink-0">
                <label for="distance_km" class="sr-only">{{ __('location.discovery_distance') }}</label>
                @if ($discoveryMode === 'nationwide')
                    <input type="hidden" name="distance_km" value="0">
                    <span class="inline-flex h-9 items-center rounded-lg border border-slate-200 bg-slate-50 px-2.5 text-sm text-slate-500" title="{{ __('location.all_egypt') }}">
                        {{ __('location.all_egypt') }}
                    </span>
                @else
                    <select id="distance_km" name="distance_km" class="{{ $control }}">
                        @foreach ($distances as $distance)
                            <option value="{{ $distance }}" @selected((int) $filters['distance_km'] === $distance)>
                                @if ((int) $distance === 0)
                                    {{ __('location.all_distances') }}
                                @else
                                    {{ $distance }} {{ __('location.km') }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="shrink-0">
                <label for="category_id" class="sr-only">{{ __('events.category') }}</label>
                <select id="category_id" name="category_id" class="{{ $control }} max-w-[9rem]">
                    <option value="">{{ __('events.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $filters['category_id'] === $category->id)>
                            {{ $category->localizedName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-[#7138F5] px-3 text-sm font-semibold text-white hover:brightness-105">
                {{ __('events.apply_filters') }}
            </button>

            <div class="ms-auto inline-flex h-9 shrink-0 rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="Map or list">
                <button type="button" data-discover-view-toggle="map"
                        class="rounded-md px-2.5 text-xs font-bold {{ $viewMode === 'map' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600' }}">
                    Map
                </button>
                <button type="button" data-discover-view-toggle="list"
                        class="rounded-md px-2.5 text-xs font-bold {{ $viewMode === 'list' ? 'bg-white text-indigo-700 shadow-sm' : 'text-slate-600' }}">
                    List
                </button>
            </div>
        </form>

        {{-- Hidden place-search mount for modal/JS parity (not shown in toolbar) --}}
        <div class="hidden" data-discover-city-search data-cities-search-url="{{ url('/api/places/search') }}" data-places-search="1" aria-hidden="true">
            <input id="city_q" type="search" data-discover-city-input value="{{ $discoveryMode === 'manual' ? ($activeCityName ?? '') : '' }}" tabindex="-1">
            <ul data-discover-city-results role="listbox" hidden></ul>
        </div>

        <div data-discover-location-status
             class="hidden rounded-lg border border-indigo-100 bg-indigo-50/80 px-3 py-1.5 text-sm text-indigo-950"
             role="status"
             aria-live="polite">
            <p class="font-semibold" data-location-status-title></p>
            <p class="mt-0.5 text-indigo-900/90" data-location-status-place></p>
            <p class="mt-0.5 text-xs text-indigo-800/80" data-location-status-accuracy></p>
            <p class="mt-0.5 text-xs font-medium text-emerald-700" data-location-status-ok hidden></p>
            <div class="mt-1.5 flex flex-wrap gap-2">
                <button type="button" data-discover-use-gps class="text-xs font-semibold text-indigo-700 underline">{{ __('location.retry_location') }}</button>
                @auth
                    <button type="button" data-location-modal-open class="text-xs font-semibold text-indigo-700 underline">{{ __('location.search_location') }}</button>
                @endauth
            </div>
        </div>

        <div class="hidden" data-live-search-searching data-live-search-searching-template aria-hidden="true">
            <x-ui.searching />
        </div>

        <div class="discover-split-grid grid min-h-[20rem] gap-2 lg:grid-cols-12 lg:items-stretch lg:overflow-hidden"
             data-discover-split
             style="--discover-split-h: min(70vh, 40rem)">
            <style>
                @media (min-width: 1024px) {
                    [data-discover-split].discover-split-grid {
                        height: var(--discover-split-h);
                        max-height: var(--discover-split-h);
                        overflow: hidden;
                    }
                    [data-discover-split] [data-discover-map-pane],
                    [data-discover-split] [data-discover-map-list],
                    [data-discover-split] [data-discover-map],
                    [data-discover-split] [data-discover-living-map-host],
                    [data-discover-split] [data-living-map] {
                        min-height: 0 !important;
                        height: 100%;
                    }
                    [data-discover-split] #discover-results {
                        min-height: 0 !important;
                    }
                }
            </style>
            <div @class([
                'order-1 flex min-h-0 flex-col',
                'lg:col-span-8' => $viewMode === 'map',
                'hidden lg:col-span-8' => $viewMode === 'list',
            ]) data-discover-map-pane>
                @if ($useLivingMap)
                    <div class="relative min-h-[20rem] flex-1 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm lg:min-h-0 lg:h-full"
                         data-discover-living-map-host
                         data-discover-i18n-exact-unavailable="{{ __('events.exact_venue_unavailable') }}"
                         data-discover-i18n-city-level="{{ __('events.city_level_preview') }}"
                         data-discover-i18n-not-on-map="{{ __('events.event_not_on_map') }}"
                         data-discover-i18n-no-events-radius="{{ __('events.map_no_events_in_radius') }}"
                         data-discover-i18n-no-events-viewport="{{ __('events.map_no_events_viewport') }}">
                        <button type="button"
                                hidden
                                data-discover-map-search-area
                                class="absolute start-1/2 top-3 z-[500] -translate-x-1/2 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-indigo-700 shadow-md rtl:translate-x-1/2">
                            {{ __('location.search_this_area') }}
                        </button>
                        <x-living-map.stage
                            mode="discover"
                            class="h-full"
                            :show-banner="false"
                            :show-tour-controls="true"
                            :show-radius-control="false"
                            :show-list="false"
                            :show-heading="false"
                            :latitude="$livingMapLat"
                            :longitude="$livingMapLng"
                            :distance-km="(int) $livingMapKm"
                            :discovery-mode="$livingMapMode"
                            :location-label="$activeCityName"
                            :city-id="$filters['city_id'] ?? null"
                            :has-nearby-origin="(bool) $hasNearbyOrigin"
                        />
                    </div>
                @else
                    <div data-discover-map
                         class="relative min-h-[20rem] flex-1 overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm lg:min-h-0 lg:h-full"
                         data-discover-i18n-exact-unavailable="{{ __('events.exact_venue_unavailable') }}"
                         data-discover-i18n-show-all="{{ __('events.show_all_results') }}"
                         data-discover-i18n-city-heading="{{ __('events.city_events_heading') }}"
                         data-discover-i18n-city-level="{{ __('events.city_level_preview') }}"
                         data-discover-i18n-not-on-map="{{ __('events.event_not_on_map') }}"
                         data-discover-i18n-no-events-radius="{{ __('events.map_no_events_in_radius') }}"
                         data-discover-i18n-no-events-viewport="{{ __('events.map_no_events_viewport') }}">
                        <button type="button"
                                hidden
                                data-discover-map-search-area
                                class="absolute start-1/2 top-3 z-[500] -translate-x-1/2 rounded-full bg-white px-3.5 py-1.5 text-xs font-bold text-indigo-700 shadow-md rtl:translate-x-1/2">
                            {{ __('location.search_this_area') }}
                        </button>
                        <div data-discover-map-canvas class="absolute inset-0 h-full w-full" style="min-height: 20rem;"></div>
                        <p class="pointer-events-none absolute bottom-0 inset-x-0 z-[400] bg-white/90 px-3 py-1 text-[10px] text-slate-500">
                            © OpenStreetMap · Purple = city-level · Orange pin = exact venue
                        </p>
                    </div>
                @endif
            </div>

            <div @class([
                'order-2 flex min-h-0 flex-col overflow-hidden',
                'lg:col-span-4' => $viewMode === 'map',
                'lg:col-span-12' => $viewMode === 'list',
            ]) data-discover-map-list>
                <div class="mb-1.5 flex shrink-0 items-center justify-between gap-2">
                    <h2 class="text-sm font-bold text-slate-900" data-discover-results-heading>
                        {{ $resultsHeading }}
                        <span class="font-semibold text-slate-500" data-discover-results-count>({{ $resultCount }})</span>
                    </h2>
                    <label class="sr-only" for="discover_sort">Sort</label>
                    <select id="discover_sort" data-discover-sort
                            class="h-7 rounded-md border border-slate-200 bg-white px-1.5 text-[11px] text-slate-700">
                        <option value="soonest" @selected($sort === 'soonest')>Soonest</option>
                        @if ($activeDevice || ($discoveryMode === 'manual' && $activeCityName))
                            <option value="nearest" @selected($sort === 'nearest')>Nearest</option>
                        @endif
                        <option value="newest" @selected($sort === 'newest')>Newest</option>
                    </select>
                </div>
                <div id="discover-results" class="live-search-page-target relative min-h-[10rem] flex-1 overflow-y-auto fancy-scroll rounded-xl border border-slate-200/70 bg-[#F8F9FE] p-1.5 lg:min-h-0">
                    @include('events.partials.results')
                </div>
            </div>
        </div>
    </div>
@endsection
