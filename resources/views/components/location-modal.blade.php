@auth
@php
    $locationUser = auth()->user()->loadMissing('city');
    $locationDistances = config('location.distances_km', [5, 10, 25, 50, 100, 0]);
    $openOnError = $errors->has('city_id') || $errors->has('distance_km');
    $selectedCityLabel = $locationUser->city?->localizedName() ?? '';
@endphp

<dialog id="location-modal"
        class="location-modal fixed inset-0 m-auto h-fit max-h-[min(90vh,42rem)] w-[min(100%-2rem,26rem)] overflow-visible rounded-3xl border-0 bg-transparent p-0 shadow-none outline-none backdrop:bg-slate-950/50"
        aria-labelledby="location-modal-title"
        data-cities-search-url="{{ url('/api/cities/search') }}"
        data-discover-url="{{ route('events.index') }}"
        data-location-unavailable="{{ __('events.location_unavailable') }}"
        data-location-denied="{{ __('events.location_permission_denied') }}"
        @if ($openOnError) data-open-on-load="1" @endif>
    <div class="box-border flex w-full flex-col overflow-visible rounded-3xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/25">
        <div class="flex items-start justify-between gap-4 px-6 pb-4 pt-8">
            <div class="min-w-0 flex-1">
                <h2 id="location-modal-title" class="text-lg font-extrabold tracking-tight text-slate-900">
                    {{ __('location.location_settings') }}
                </h2>
                <p class="mt-1 text-sm leading-snug text-slate-500">{{ __('location.modal_sub_v4') }}</p>
            </div>
            <div class="cancel-btn-slot shrink-0 py-0">
                <x-ui.cancel-btn
                    type="button"
                    size="sm"
                    data-location-modal-close
                    :label="__('ui.close')"
                />
            </div>
        </div>

        <div class="mx-6 border-t border-slate-100"></div>

        <form method="POST" action="{{ route('settings.location.update') }}" class="flex flex-col gap-4 px-6 py-5" data-location-form>
            @csrf
            @method('PUT')

            <input type="hidden" name="city_id" id="location-modal-city-id" value="{{ old('city_id', $locationUser->city_id) }}" required>

            <div class="w-full" data-city-search>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-city-q">{{ __('location.search_location') }}</label>
                <input id="location-modal-city-q"
                       type="search"
                       autocomplete="off"
                       value="{{ $selectedCityLabel }}"
                       placeholder="{{ __('location.search_placeholder') }}"
                       class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
                       data-city-search-input>
                <p class="mt-1 text-xs text-slate-500" data-city-selected-label>
                    @if ($selectedCityLabel !== '')
                        {{ __('location.selected') }}: {{ $selectedCityLabel }}
                    @else
                        {{ __('location.pick_from_results') }}
                    @endif
                </p>
                <ul class="mt-2 max-h-44 overflow-auto rounded-xl border border-slate-200 bg-white text-sm shadow-sm empty:hidden"
                    data-city-search-results
                    role="listbox"
                    hidden></ul>
                @error('city_id')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="w-full">
                <label class="mb-1.5 block text-sm font-semibold text-slate-700" for="location-modal-distance">{{ __('location.discovery_distance') }}</label>
                <select id="location-modal-distance"
                        name="distance_km"
                        required
                        class="box-border w-full max-w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                    @foreach ($locationDistances as $distance)
                        <option value="{{ $distance }}" @selected((int) old('distance_km', $locationUser->distance_km ?? config('location.default_distance_km')) === (int) $distance)>
                            @if ((int) $distance === 0)
                                {{ __('location.all_distances') }}
                            @else
                                {{ $distance }} {{ __('location.km') }}
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('distance_km')
                    <p class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="button"
                    data-location-use-device
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-800 transition hover:bg-indigo-100">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
                {{ __('location.use_device') }}
            </button>
            <p class="text-xs leading-snug text-slate-500">{{ __('location.device_hint') }}</p>

            <div class="mt-1 grid w-full grid-cols-1 gap-2 sm:grid-cols-2 sm:items-center">
                <div class="cancel-btn-slot">
                    <x-ui.cancel-btn
                        type="button"
                        size="block"
                        data-location-modal-close
                        :label="__('ui.cancel')"
                    />
                </div>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 px-3 py-3 text-sm font-extrabold text-white shadow-lg shadow-orange-500/25 transition hover:brightness-105">
                    {{ __('location.save_short') }}
                </button>
            </div>
        </form>
    </div>
</dialog>

<script>
(() => {
    const modal = document.getElementById('location-modal');
    if (!modal) return;

    const open = () => {
        if (typeof modal.showModal === 'function') modal.showModal();
    };
    const close = () => {
        if (typeof modal.close === 'function') modal.close();
    };

    document.querySelectorAll('[data-location-modal-open]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            const details = el.closest('details');
            if (details) details.open = false;
            open();
        });
    });

    modal.querySelectorAll('[data-location-modal-close]').forEach((el) => {
        el.addEventListener('click', close);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) close();
    });

    if (modal.dataset.openOnLoad === '1') open();

    const searchUrl = modal.dataset.citiesSearchUrl;
    const input = modal.querySelector('[data-city-search-input]');
    const results = modal.querySelector('[data-city-search-results]');
    const cityIdInput = modal.querySelector('#location-modal-city-id');
    const selectedLabel = modal.querySelector('[data-city-selected-label]');
    const selectedPrefix = @json(__('location.selected') . ': ');
    let debounceTimer = null;
    let abortController = null;

    const renderResults = (items) => {
        if (!results) return;
        results.innerHTML = '';
        if (!items.length) {
            results.hidden = true;
            return;
        }
        items.forEach((item) => {
            const li = document.createElement('li');
            li.setAttribute('role', 'option');
            li.className = 'cursor-pointer px-3 py-2 hover:bg-indigo-50';
            li.innerHTML = '<div class="font-semibold text-slate-900"></div><div class="text-xs text-slate-500"></div>';
            li.children[0].textContent = item.name || '';
            li.children[1].textContent = item.subtitle || '';
            li.addEventListener('click', () => {
                cityIdInput.value = String(item.id);
                input.value = item.name;
                if (selectedLabel) selectedLabel.textContent = selectedPrefix + item.name;
                results.hidden = true;
                results.innerHTML = '';
            });
            results.appendChild(li);
        });
        results.hidden = false;
    };

    const runSearch = async () => {
        if (!searchUrl || !input) return;
        if (abortController) abortController.abort();
        abortController = new AbortController();
        const q = input.value.trim();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q);
            url.searchParams.set('limit', '12');
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' },
                signal: abortController.signal,
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('search failed');
            const payload = await res.json();
            renderResults(Array.isArray(payload.data) ? payload.data : []);
        } catch (err) {
            if (err && err.name === 'AbortError') return;
            renderResults([]);
        }
    };

    if (input) {
        input.addEventListener('input', () => {
            cityIdInput.value = '';
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(runSearch, 280);
        });
        input.addEventListener('focus', () => {
            if (!results || results.children.length === 0) runSearch();
        });
    }

    const deviceBtn = modal.querySelector('[data-location-use-device]');
    if (deviceBtn) {
        deviceBtn.addEventListener('click', () => {
            if (!window.isSecureContext || !navigator.geolocation) {
                window.alert(modal.dataset.locationUnavailable || '');
                return;
            }
            deviceBtn.disabled = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const url = new URL(modal.dataset.discoverUrl || '/events', window.location.origin);
                    url.searchParams.set('latitude', String(pos.coords.latitude));
                    url.searchParams.set('longitude', String(pos.coords.longitude));
                    const distance = modal.querySelector('#location-modal-distance');
                    if (distance && distance.value !== '') {
                        url.searchParams.set('distance_km', distance.value);
                    }
                    // Ephemeral device origin only — do not persist GPS on the user.
                    window.location.assign(url.toString());
                },
                () => {
                    deviceBtn.disabled = false;
                    window.alert(modal.dataset.locationDenied || '');
                },
                { enableHighAccuracy: false, maximumAge: 0, timeout: 12000 }
            );
        });
    }
})();
</script>
@endauth
