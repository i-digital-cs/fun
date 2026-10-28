@extends('layouts.dashboard')

@section('title', __('events.discover'))

@section('content')
    @php
        $activeLat = $filters['latitude'] ?? null;
        $activeLng = $filters['longitude'] ?? null;
        $activeDevice = ! empty($hasDeviceOrigin) && $activeLat !== null && $activeLng !== null;
        $activeCityName = $filterCity?->localizedName();
        $activeKm = (int) ($filters['distance_km'] ?? config('location.default_distance_km'));
        $radiusLabel = $activeKm === 0
            ? __('location.all_distances')
            : __('location.within_km', ['km' => $activeKm]);
        $locationChipLabel = $activeDevice
            ? __('location.device_active').' · '.$radiusLabel
            : (($activeCityName ?: __('events.all_cities')).' · '.$radiusLabel);
    @endphp

    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ __('events.discover_document_title') }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-600">{{ __('events.discover_intro') }}</p>
            <button type="button"
                    @auth data-location-modal-open @else onclick="document.getElementById('city_q')?.focus()" @endauth
                    class="mt-3 inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200/80 bg-white px-3 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-indigo-300 hover:text-indigo-700">
                <span aria-hidden="true">📍</span>
                <span class="truncate">{{ $locationChipLabel }}</span>
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
            @if ($activeDevice)
                <input type="hidden" name="latitude" value="{{ $activeLat }}">
                <input type="hidden" name="longitude" value="{{ $activeLng }}">
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
                {{-- Omit city_id while device GPS is active so lat/lng are not overridden by empty city_id=. --}}
                <input type="hidden" name="city_id" id="city_id" value="{{ $filters['city_id'] }}" @if ($activeDevice) disabled @endif>
                <input id="city_q" type="search" autocomplete="off"
                       value="{{ $activeDevice ? '' : ($activeCityName ?? '') }}"
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
                <a href="{{ route('events.index') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200/70 bg-white/80 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700">{{ __('events.clear_filters') }}</a>
                <button type="button"
                        data-discover-use-gps
                        data-location-unavailable="{{ __('events.location_unavailable') }}"
                        data-location-denied="{{ __('events.location_permission_denied') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100">
                    {{ __('location.use_device') }}
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

    <script>
    (() => {
        const root = document.querySelector('[data-discover-city-search]');
        if (root) {
            const input = root.querySelector('[data-discover-city-input]');
            const hidden = root.querySelector('#city_id');
            const results = root.querySelector('[data-discover-city-results]');
            const searchUrl = root.dataset.citiesSearchUrl;
            let t = null;
            let ac = null;
            const render = (items) => {
                results.innerHTML = '';
                if (!items.length) { results.hidden = true; return; }
                items.forEach((item) => {
                    const li = document.createElement('li');
                    li.className = 'cursor-pointer px-3 py-2 hover:bg-indigo-50';
                    li.innerHTML = `<div class="font-semibold text-slate-900"></div><div class="text-xs text-slate-500"></div>`;
                    li.children[0].textContent = item.name || '';
                    li.children[1].textContent = item.subtitle || '';
                    li.addEventListener('click', () => {
                        hidden.disabled = false;
                        hidden.value = String(item.id);
                        input.value = item.name;
                        results.hidden = true;
                        const form = root.closest('form');
                        form?.querySelectorAll('input[name="latitude"], input[name="longitude"]').forEach((el) => el.remove());
                    });
                    results.appendChild(li);
                });
                results.hidden = false;
            };
            const run = async () => {
                if (ac) ac.abort();
                ac = new AbortController();
                try {
                    const url = new URL(searchUrl, window.location.origin);
                    url.searchParams.set('q', input.value.trim());
                    url.searchParams.set('limit', '12');
                    const res = await fetch(url.toString(), { headers: { Accept: 'application/json' }, signal: ac.signal, credentials: 'same-origin' });
                    if (!res.ok) throw new Error('fail');
                    const payload = await res.json();
                    render(Array.isArray(payload.data) ? payload.data : []);
                } catch (e) {
                    if (e && e.name === 'AbortError') return;
                    render([]);
                }
            };
            input?.addEventListener('input', () => {
                hidden.value = '';
                clearTimeout(t);
                t = setTimeout(run, 280);
            });
            input?.addEventListener('focus', () => { if (!results.children.length) run(); });
        }

        const gpsBtn = document.querySelector('[data-discover-use-gps]');
        if (gpsBtn) {
            gpsBtn.addEventListener('click', () => {
                if (!window.isSecureContext || !navigator.geolocation) {
                    window.alert(gpsBtn.dataset.locationUnavailable || '');
                    return;
                }
                gpsBtn.disabled = true;
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('city_id');
                        url.searchParams.set('latitude', String(pos.coords.latitude));
                        url.searchParams.set('longitude', String(pos.coords.longitude));
                        const distance = document.getElementById('distance_km');
                        if (distance?.value) url.searchParams.set('distance_km', distance.value);
                        window.location.assign(url.toString());
                    },
                    () => {
                        gpsBtn.disabled = false;
                        window.alert(gpsBtn.dataset.locationDenied || '');
                    },
                    { enableHighAccuracy: false, maximumAge: 0, timeout: 12000 }
                );
            });
        }
    })();
    </script>
@endsection
