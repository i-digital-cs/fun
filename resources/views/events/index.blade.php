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
        $radiusLabel = $activeKm === 0
            ? __('location.all_distances')
            : __('location.within_km', ['km' => $activeKm]);
        if ($activeDevice) {
            $acc = $filters['accuracy_m'] ?? null;
            $accSuffix = ($acc !== null && (float) $acc > 0)
                ? ' (±'.(int) round((float) $acc).' m)'
                : '';
            $locationChipLabel = __('location.current_location').$accSuffix.' · '.$radiusLabel;
        } elseif ($discoveryMode === 'manual' && $activeCityName) {
            $locationChipLabel = $activeCityName.' · '.$radiusLabel;
        } else {
            $locationChipLabel = __('location.all_egypt').' · '.$radiusLabel;
        }

        $discoveryBoot = [
            'mode' => $discoveryMode,
            'cityId' => $filters['city_id'] ?? null,
            'cityName' => $activeCityName,
            'distanceKm' => $activeKm,
            'lat' => $activeDevice ? $activeLat : null,
            'lng' => $activeDevice ? $activeLng : null,
            'accuracyM' => $activeDevice ? ($filters['accuracy_m'] ?? null) : null,
        ];
    @endphp

    <script type="application/json" id="discovery-location-boot"
            data-label-current="{{ __('location.current_location') }}"
            data-label-nationwide="{{ __('location.all_egypt') }}"
            data-label-all-distances="{{ __('location.all_distances') }}"
            data-label-within="{{ __('location.within_km') }}">{!! json_encode($discoveryBoot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.discover_document_title') }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">{{ __('events.discover_intro') }}</p>
            <button type="button"
                    @auth data-location-modal-open @else onclick="document.getElementById('city_q')?.focus()" @endauth
                    class="mt-3 inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700">
                <span aria-hidden="true">📍</span>
                <span class="truncate" data-discovery-chip-label>{{ $locationChipLabel }}</span>
            </button>
            @if (($landingCities ?? collect())->isNotEmpty() || ($landingCategories ?? collect())->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                    @foreach ($landingCities as $landingCity)
                        <a href="{{ route('events.city', $landingCity) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $landingCity->localizedName() }}</a>
                    @endforeach
                    @foreach ($landingCategories as $landingCategory)
                        <a href="{{ route('events.activity', $landingCategory) }}" class="font-semibold text-indigo-700 hover:text-indigo-900">{{ $landingCategory->localizedName() }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        @auth
            @if (auth()->user()->canParticipate())
                <a href="{{ route('events.create') }}" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-5 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-orange-500/30 transition hover:shadow-xl hover:shadow-orange-500/45 hover:brightness-105">
                    {{ __('events.create_event') }}
                </a>
            @endif
        @endauth
    </div>

    <div
        data-live-search
        data-live-search-mode="page"
        data-live-search-target="#discover-results"
        data-live-search-url="{{ route('search.suggest') }}"
        data-live-search-searching-label="{{ __('ui.searching') }}"
    >
        <form method="GET" action="{{ route('events.index') }}" class="relative mb-6 grid gap-4 rounded-3xl border border-slate-200/70 bg-white p-5 shadow-sm sm:grid-cols-2 lg:grid-cols-5" data-discover-filters>
            <input type="hidden" name="discovery_mode" value="{{ $discoveryMode }}">
            @if ($activeDevice)
                <input type="hidden" name="latitude" value="{{ $activeLat }}">
                <input type="hidden" name="longitude" value="{{ $activeLng }}">
                @if (! empty($filters['accuracy_m']))
                    <input type="hidden" name="accuracy_m" value="{{ $filters['accuracy_m'] }}">
                @endif
            @endif
            <div class="lg:col-span-2">
                <label for="q" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $filters['q'] }}" placeholder="{{ __('events.search_placeholder') }}" autocomplete="off" data-live-search-input class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
            </div>
            <div>
                <label for="category_id" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.category') }}</label>
                <select id="category_id" name="category_id" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    <option value="">{{ __('events.all_categories') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) $filters['category_id'] === $category->id)>
                            {{ $category->localizedName() }}
                            @if (isset($category->published_events_count))
                                ({{ $category->published_events_count }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="relative" data-discover-city-search data-cities-search-url="{{ url('/api/cities/search') }}">
                <label for="city_q" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('events.city') }}</label>
                <input type="hidden" name="city_id" id="city_id" value="{{ $filters['city_id'] }}" @if ($activeDevice || $discoveryMode === 'nationwide') disabled @endif>
                <input id="city_q" type="search" autocomplete="off"
                       value="{{ $discoveryMode === 'manual' ? ($activeCityName ?? '') : '' }}"
                       placeholder="{{ __('location.search_placeholder') }}"
                       data-discover-city-input
                       class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <ul class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-xl border border-slate-200 bg-white text-sm shadow-lg empty:hidden"
                    data-discover-city-results
                    role="listbox"
                    hidden></ul>
            </div>
            <div>
                <label for="distance_km" class="mb-1.5 block text-xs font-medium text-slate-500">{{ __('location.discovery_distance') }}</label>
                <select id="distance_km" name="distance_km" class="w-full rounded-xl border border-slate-200/70 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
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
            </div>
            <div class="flex flex-wrap items-end gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:brightness-105">{{ __('events.apply_filters') }}</button>
                <a href="{{ route('events.index', ['discovery_mode' => 'nationwide', 'city_id' => '', 'distance_km' => 0]) }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.clear_filters') }}</a>
                <button type="button"
                        data-discover-use-gps
                        data-location-unavailable="{{ __('events.location_unavailable') }}"
                        data-location-denied="{{ __('events.location_permission_denied') }}"
                        data-location-timeout="{{ __('location.gps_timeout') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100">
                    {{ __('location.detect_my_location') }}
                </button>
                @auth
                    <a href="{{ route('settings.location.edit') }}"
                       data-location-modal-open
                       class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('events.set_location_cta') }}</a>
                @endauth
            </div>
        </form>

        <div class="hidden" data-live-search-searching data-live-search-searching-template aria-hidden="true">
            <x-ui.searching />
        </div>

        <div id="discover-results" class="live-search-page-target relative min-h-[12rem]">
            @include('events.partials.results')
        </div>
    </div>
@endsection
