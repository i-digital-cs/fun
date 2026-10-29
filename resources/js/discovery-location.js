/**
 * Authoritative client discovery-location state (sessionStorage).
 * Modes: manual | device | nationwide — mutually exclusive params.
 */
const STORAGE_KEY = 'funnity_discovery_v4';

const defaultState = () => ({
    mode: 'nationwide',
    cityId: null,
    cityName: null,
    distanceKm: 25,
    lat: null,
    lng: null,
    accuracyM: null,
    locationLabel: null,
    shortLabel: null,
    updatedAt: null,
});

export function loadDiscoveryState() {
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (!raw) return defaultState();
        const parsed = JSON.parse(raw);
        if (!parsed || typeof parsed !== 'object') return defaultState();
        return {
            ...defaultState(),
            ...parsed,
            cityId: parsed.cityId != null ? Number(parsed.cityId) : null,
            distanceKm: Number(parsed.distanceKm ?? 25),
            lat: parsed.lat != null ? Number(parsed.lat) : null,
            lng: parsed.lng != null ? Number(parsed.lng) : null,
            accuracyM: parsed.accuracyM != null ? Number(parsed.accuracyM) : null,
            locationLabel: parsed.locationLabel ?? null,
            shortLabel: parsed.shortLabel ?? null,
        };
    } catch {
        return defaultState();
    }
}

export function saveDiscoveryState(partial) {
    const prev = loadDiscoveryState();
    const next = {
        ...prev,
        ...partial,
        updatedAt: Date.now(),
    };
    if (next.mode === 'manual') {
        // Keep place lat/lng when selected from places search; clear GPS accuracy.
        next.accuracyM = null;
        next.locationLabel = null;
        next.shortLabel = null;
    } else if (next.mode === 'device') {
        next.cityId = null;
        next.cityName = null;
        // Preserve locationLabel / shortLabel from partial or previous device session.
    } else {
        next.mode = 'nationwide';
        next.cityId = null;
        next.cityName = null;
        next.lat = null;
        next.lng = null;
        next.accuracyM = null;
        next.locationLabel = null;
        next.shortLabel = null;
    }
    sessionStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    document.dispatchEvent(new CustomEvent('funnity:discovery-location', { detail: next }));
    return next;
}

export function clearDiscoveryCoords(state = loadDiscoveryState()) {
    return saveDiscoveryState({
        ...state,
        lat: null,
        lng: null,
        accuracyM: null,
        locationLabel: null,
        shortLabel: null,
    });
}

export function chipLabel(state, labels) {
    // Nationwide: never append a radius — radius only applies with an origin.
    if (state.mode === 'nationwide' || (!state.mode && !state.cityName && state.lat == null)) {
        return labels.allEgypt;
    }
    const radius =
        Number(state.distanceKm) === 0
            ? labels.allDistances
            : labels.withinKm.replace(':km', String(state.distanceKm));
    if (state.mode === 'device') {
        const place = state.shortLabel || state.locationLabel;
        if (place && labels.chipPlace) {
            return `${labels.chipPlace.replace(':place', place)} · ${radius}`;
        }
        return `${labels.currentLocation} · ${radius}`;
    }
    if (state.mode === 'manual' && state.cityName) {
        return `${state.cityName} · ${radius}`;
    }
    return labels.allEgypt;
}

/**
 * Build Discover URL with exclusive mode params (never mix city_id + lat/lng).
 */
export function buildDiscoverUrl(baseUrl, state) {
    const url = new URL(baseUrl, window.location.origin);
    ['city_id', 'latitude', 'longitude', 'discovery_mode', 'accuracy_m'].forEach((k) =>
        url.searchParams.delete(k)
    );
    url.searchParams.set('discovery_mode', state.mode);
    // Radius only meaningful with an origin; nationwide always uses all-distances (0).
    const distanceKm =
        state.mode === 'nationwide' ? 0 : Number(state.distanceKm ?? 25);
    url.searchParams.set('distance_km', String(distanceKm));
    if (state.mode === 'manual') {
        if (state.cityId) {
            url.searchParams.set('city_id', String(state.cityId));
        }
        if (state.lat != null && state.lng != null) {
            url.searchParams.set('latitude', String(state.lat));
            url.searchParams.set('longitude', String(state.lng));
        }
    } else if (state.mode === 'nationwide') {
        url.searchParams.set('city_id', '');
    } else if (state.mode === 'device' && state.lat != null && state.lng != null) {
        url.searchParams.set('latitude', String(state.lat));
        url.searchParams.set('longitude', String(state.lng));
        if (state.accuracyM != null) {
            url.searchParams.set('accuracy_m', String(Math.round(state.accuracyM)));
        }
    }
    return url;
}

export function syncStateFromServerPayload(payload) {
    if (!payload || typeof payload !== 'object') return loadDiscoveryState();
    const prev = loadDiscoveryState();
    const mode = payload.mode || 'nationwide';
    const lat = mode === 'device' || mode === 'manual' ? payload.lat ?? null : null;
    const lng = mode === 'device' || mode === 'manual' ? payload.lng ?? null : null;
    // Preserve reverse-geocoded labels across reload when coords still match.
    const sameDeviceFix =
        mode === 'device' &&
        prev.mode === 'device' &&
        lat != null &&
        lng != null &&
        prev.lat != null &&
        Math.abs(prev.lat - lat) < 1e-5 &&
        Math.abs(prev.lng - lng) < 1e-5;
    return saveDiscoveryState({
        mode,
        cityId: payload.cityId ?? null,
        cityName: payload.cityName ?? null,
        distanceKm: payload.distanceKm ?? 25,
        lat,
        lng,
        accuracyM: mode === 'device' ? payload.accuracyM ?? null : null,
        locationLabel:
            mode === 'device'
                ? payload.locationLabel ?? (sameDeviceFix ? prev.locationLabel : null)
                : null,
        shortLabel:
            mode === 'device'
                ? payload.shortLabel ?? (sameDeviceFix ? prev.shortLabel : null)
                : null,
    });
}

const LOW_ACCURACY_M = 500;

export function requestDevicePosition(options = {}) {
    return new Promise((resolve, reject) => {
        if (!window.isSecureContext || !navigator.geolocation) {
            reject(new Error('unavailable'));
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                const accuracyM = pos.coords.accuracy;
                if (
                    !Number.isFinite(lat) ||
                    !Number.isFinite(lng) ||
                    lat < -90 ||
                    lat > 90 ||
                    lng < -180 ||
                    lng > 180
                ) {
                    reject(new Error('invalid'));
                    return;
                }
                resolve({
                    lat,
                    lng,
                    accuracyM,
                    approximate: accuracyM == null || accuracyM > LOW_ACCURACY_M,
                });
            },
            (err) => {
                if (err?.code === 1) reject(new Error('denied'));
                else if (err?.code === 3) reject(new Error('timeout'));
                else reject(new Error('unavailable'));
            },
            {
                enableHighAccuracy: options.enableHighAccuracy !== false,
                maximumAge: 0,
                timeout: options.timeoutMs ?? 15000,
            }
        );
    });
}

export async function reverseGeocodeLabel(lat, lng, reverseUrl) {
    if (!reverseUrl) return null;
    try {
        const url = new URL(reverseUrl, window.location.origin);
        url.searchParams.set('latitude', String(lat));
        url.searchParams.set('longitude', String(lng));
        const res = await fetch(url.toString(), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) return null;
        const payload = await res.json();
        return payload?.data ?? null;
    } catch {
        return null;
    }
}

function paintLocationStatus(state, labels, opts = {}) {
    const panel = document.querySelector('[data-discover-location-status]');
    if (!panel) return;
    const title = panel.querySelector('[data-location-status-title]');
    const place = panel.querySelector('[data-location-status-place]');
    const accuracy = panel.querySelector('[data-location-status-accuracy]');
    const ok = panel.querySelector('[data-location-status-ok]');

    if (opts.detecting) {
        panel.classList.remove('hidden');
        if (title) title.textContent = labels.detecting || 'Detecting your location…';
        if (place) place.textContent = '';
        if (accuracy) accuracy.textContent = '';
        if (ok) ok.hidden = true;
        return;
    }

    if (state.mode !== 'device' || state.lat == null) {
        if (!opts.force) panel.classList.add('hidden');
        return;
    }

    panel.classList.remove('hidden');
    if (title) title.textContent = labels.currentLocation || 'Current location';
    if (place) {
        place.textContent =
            state.locationLabel ||
            labels.areaUnavailable ||
            'Current location detected — area name unavailable';
    }
    if (accuracy) {
        accuracy.textContent =
            state.accuracyM != null && state.accuracyM > 0
                ? (labels.accuracyMeters || 'Accuracy: ±:m m').replace(
                      ':m',
                      String(Math.round(state.accuracyM))
                  )
                : '';
    }
    if (ok) {
        ok.textContent = labels.detected || 'Location detected successfully';
        ok.hidden = false;
    }
    if (opts.lowAccuracy && labels.lowAccuracy) {
        if (accuracy) {
            accuracy.textContent =
                (accuracy.textContent ? accuracy.textContent + ' · ' : '') + labels.lowAccuracy;
        }
    }
}

export function initDiscoveryLocationUi() {
    const boot = document.getElementById('discovery-location-boot');
    if (boot?.textContent) {
        try {
            syncStateFromServerPayload(JSON.parse(boot.textContent));
        } catch {
            /* ignore */
        }
    }

    const labels = {
        currentLocation: boot?.dataset?.labelCurrent || 'Current location',
        allEgypt: boot?.dataset?.labelNationwide || 'All Egypt',
        allDistances: boot?.dataset?.labelAllDistances || 'All distances',
        withinKm: boot?.dataset?.labelWithin || 'Within :km km',
        chipPlace: boot?.dataset?.labelChipPlace || 'Current location · :place',
        detecting: boot?.dataset?.labelDetecting || 'Detecting your location…',
        detected: boot?.dataset?.labelDetected || 'Location detected successfully',
        areaUnavailable:
            boot?.dataset?.labelAreaUnavailable ||
            'Current location detected — area name unavailable',
        accuracyMeters: boot?.dataset?.labelAccuracy || 'Accuracy: ±:m m',
        lowAccuracy: boot?.dataset?.labelLowAccuracy || '',
    };
    const reverseUrl = boot?.dataset?.reverseUrl || '/api/places/reverse';

    const paintChips = () => {
        const state = loadDiscoveryState();
        const text = chipLabel(state, labels);
        document.querySelectorAll('[data-discovery-chip-label]').forEach((el) => {
            el.textContent = text;
        });
        document.querySelectorAll('[data-topbar-location-label]').forEach((el) => {
            el.textContent =
                state.mode === 'manual' && state.cityName
                    ? state.cityName
                    : state.mode === 'device'
                      ? state.shortLabel || labels.currentLocation
                      : labels.allEgypt;
        });
        if (state.mode === 'device' && state.lat != null) {
            paintLocationStatus(state, labels);
        }
    };

    paintChips();
    document.addEventListener('funnity:discovery-location', paintChips);

    // If device mode but no label yet (e.g. page load after GPS), reverse-geocode once.
    const existing = loadDiscoveryState();
    if (
        existing.mode === 'device' &&
        existing.lat != null &&
        existing.lng != null &&
        !existing.locationLabel &&
        !existing.shortLabel
    ) {
        reverseGeocodeLabel(existing.lat, existing.lng, reverseUrl).then((data) => {
            if (!data) {
                paintLocationStatus(loadDiscoveryState(), labels, { force: true });
                return;
            }
            saveDiscoveryState({
                ...loadDiscoveryState(),
                locationLabel: data.display_name || null,
                shortLabel: data.short_name || null,
            });
        });
    }

    initLocationModal(labels, reverseUrl);
    initDiscoverFilters(labels, reverseUrl);
    initViewToggle();
}

function initViewToggle() {
    document.querySelectorAll('[data-discover-view-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-discover-view-toggle') || 'map';
            const url = new URL(window.location.href);
            url.searchParams.set('view', view);
            window.location.assign(url.toString());
        });
    });

    const sort = document.querySelector('[data-discover-sort]');
    sort?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('sort', sort.value);
        window.location.assign(url.toString());
    });
}

function initLocationModal(labels, reverseUrl) {
    const modal = document.getElementById('location-modal');
    if (!modal) return;

    const form = modal.querySelector('[data-location-form]');
    const cityIdInput = modal.querySelector('#location-modal-city-id');
    const cityQ = modal.querySelector('[data-city-search-input]');
    const selectedLabel = modal.querySelector('[data-city-selected-label]');
    const distanceSelect = modal.querySelector('#location-modal-distance');
    const results = modal.querySelector('[data-city-search-results]');
    const modeInput = modal.querySelector('#location-modal-mode');
    const activeBanner = modal.querySelector('[data-location-active-banner]');
    const devicePanel = modal.querySelector('[data-location-device-panel]');
    const manualPanel = modal.querySelector('[data-location-manual-panel]');
    const errorEl = modal.querySelector('[data-location-error]');
    const searchUrl = modal.dataset.citiesSearchUrl;
    const selectedPrefix = modal.dataset.selectedPrefix || 'Selected: ';
    let snapshot = null;
    let debounceTimer = null;
    let abortController = null;

    const setError = (msg) => {
        if (!errorEl) return;
        errorEl.textContent = msg || '';
        errorEl.hidden = !msg;
    };

    const paintActiveBanner = (state) => {
        if (activeBanner) {
            activeBanner.textContent = chipLabel(state, labels);
        }
        if (modeInput) modeInput.value = state.mode;
        if (devicePanel) {
            devicePanel.hidden = state.mode !== 'device';
            const acc = devicePanel.querySelector('[data-location-accuracy]');
            const placeEl = devicePanel.querySelector('[data-location-place-name]');
            if (placeEl) {
                placeEl.textContent =
                    state.locationLabel ||
                    state.shortLabel ||
                    labels.areaUnavailable ||
                    '';
                placeEl.hidden = state.mode !== 'device';
            }
            if (acc) {
                if (state.accuracyM != null && state.accuracyM > 0) {
                    acc.textContent =
                        state.accuracyM > LOW_ACCURACY_M
                            ? (modal.dataset.approxAccuracy || 'Approximate · ±:m m').replace(
                                  ':m',
                                  String(Math.round(state.accuracyM))
                              )
                            : (modal.dataset.accuracy || 'Accuracy · ±:m m').replace(
                                  ':m',
                                  String(Math.round(state.accuracyM))
                              );
                    acc.hidden = false;
                } else {
                    acc.hidden = true;
                }
            }
        }
        if (manualPanel) {
            manualPanel.hidden = state.mode === 'device';
        }
    };

    const hydrateFromState = (state) => {
        if (distanceSelect) distanceSelect.value = String(state.distanceKm ?? 25);
        if (cityIdInput) {
            cityIdInput.value = state.mode === 'manual' && state.cityId ? String(state.cityId) : '';
            cityIdInput.required = state.mode === 'manual' && !state.lat;
        }
        if (cityQ) {
            cityQ.value = state.mode === 'manual' && state.cityName ? state.cityName : '';
        }
        if (selectedLabel) {
            if (state.mode === 'manual' && state.cityName) {
                selectedLabel.textContent = selectedPrefix + state.cityName;
            } else if (state.mode === 'device') {
                selectedLabel.textContent =
                    state.shortLabel || state.locationLabel || labels.currentLocation;
            } else {
                selectedLabel.textContent = labels.allEgypt;
            }
        }
        paintActiveBanner(state);
        setError('');
        if (results) {
            results.hidden = true;
            results.innerHTML = '';
        }
    };

    const open = () => {
        snapshot = structuredClone(loadDiscoveryState());
        hydrateFromState(snapshot);
        if (typeof modal.showModal === 'function') modal.showModal();
    };

    const closeDiscard = () => {
        if (snapshot) {
            saveDiscoveryState(snapshot);
            hydrateFromState(snapshot);
        }
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
        el.addEventListener('click', closeDiscard);
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeDiscard();
    });

    modal.addEventListener('cancel', (e) => {
        e.preventDefault();
        closeDiscard();
    });

    if (modal.dataset.openOnLoad === '1') open();

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
            li.innerHTML =
                '<div class="font-semibold text-slate-900"></div><div class="text-xs text-slate-500"></div>';
            li.children[0].textContent = item.name || item.label || '';
            li.children[1].textContent = item.subtitle || '';
            li.addEventListener('click', () => {
                const resolvedCityId =
                    item.city_id ??
                    (typeof item.id === 'number'
                        ? item.id
                        : String(item.id || '').startsWith('city:')
                          ? Number(String(item.id).slice(5))
                          : null);
                const next = saveDiscoveryState({
                    mode: 'manual',
                    cityId: resolvedCityId,
                    cityName: item.name || item.label,
                    lat: item.latitude ?? item.lat ?? null,
                    lng: item.longitude ?? item.lng ?? null,
                    distanceKm: Number(distanceSelect?.value || 25),
                });
                if (cityIdInput && resolvedCityId) {
                    cityIdInput.value = String(resolvedCityId);
                }
                hydrateFromState(next);
            });
            results.appendChild(li);
        });
        results.hidden = false;
    };

    const runSearch = async () => {
        if (!searchUrl || !cityQ) return;
        if (abortController) abortController.abort();
        abortController = new AbortController();
        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', cityQ.value.trim());
            url.searchParams.set('limit', '12');
            const res = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
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

    cityQ?.addEventListener('input', () => {
        if (cityIdInput) cityIdInput.value = '';
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runSearch, 280);
    });
    cityQ?.addEventListener('focus', () => {
        if (!results?.children.length) runSearch();
    });

    distanceSelect?.addEventListener('change', () => {
        const state = loadDiscoveryState();
        saveDiscoveryState({
            ...state,
            distanceKm: Number(distanceSelect.value),
        });
        paintActiveBanner(loadDiscoveryState());
    });

    modal.querySelector('[data-location-nationwide]')?.addEventListener('click', () => {
        const next = saveDiscoveryState({
            mode: 'nationwide',
            distanceKm: 0,
        });
        hydrateFromState(next);
    });

    const deviceBtn = modal.querySelector('[data-location-use-device]');
    deviceBtn?.addEventListener('click', async () => {
        setError('');
        deviceBtn.disabled = true;
        paintLocationStatus({}, labels, { detecting: true });
        try {
            const pos = await requestDevicePosition();
            const geo = await reverseGeocodeLabel(pos.lat, pos.lng, reverseUrl);
            const next = saveDiscoveryState({
                mode: 'device',
                lat: pos.lat,
                lng: pos.lng,
                accuracyM: pos.accuracyM,
                locationLabel: geo?.display_name || null,
                shortLabel: geo?.short_name || null,
                distanceKm: Number(distanceSelect?.value || 25),
            });
            hydrateFromState(next);
            paintLocationStatus(next, labels, { lowAccuracy: pos.approximate });
            if (pos.approximate) {
                setError(
                    modal.dataset.lowAccuracy ||
                        'Location is approximate (network-based). You can retry or pick a city.'
                );
            }
            const url = buildDiscoverUrl(modal.dataset.discoverUrl || '/events', next);
            window.location.assign(url.toString());
        } catch (err) {
            const code = err?.message || 'unavailable';
            const map = {
                denied: modal.dataset.locationDenied,
                timeout: modal.dataset.locationTimeout,
                invalid: modal.dataset.locationInvalid,
                unavailable: modal.dataset.locationUnavailable,
            };
            setError(map[code] || map.unavailable || 'Location unavailable');
            deviceBtn.disabled = false;
        }
    });

    form?.addEventListener('submit', (e) => {
        const state = loadDiscoveryState();
        const km = Number(distanceSelect?.value || 25);
        if (state.mode === 'device') {
            e.preventDefault();
            const next = saveDiscoveryState({ ...state, distanceKm: km });
            window.location.assign(
                buildDiscoverUrl(modal.dataset.discoverUrl || '/events', next).toString()
            );
            return;
        }
        if (state.mode === 'nationwide') {
            e.preventDefault();
            const next = saveDiscoveryState({ mode: 'nationwide', distanceKm: km });
            window.location.assign(
                buildDiscoverUrl(modal.dataset.discoverUrl || '/events', next).toString()
            );
            return;
        }
        // Manual: city id OR place coordinates
        if (!cityIdInput?.value && (state.lat == null || state.lng == null)) {
            e.preventDefault();
            setError(modal.dataset.pickCity || 'Pick a city from the search results.');
            return;
        }
        saveDiscoveryState({
            mode: 'manual',
            cityId: cityIdInput?.value ? Number(cityIdInput.value) : null,
            cityName: cityQ?.value || state.cityName,
            lat: state.lat,
            lng: state.lng,
            distanceKm: km,
        });
        const redirect = form.querySelector('[name="redirect_to"]');
        if (redirect) {
            redirect.value = buildDiscoverUrl(
                modal.dataset.discoverUrl || '/events',
                loadDiscoveryState()
            ).toString();
        }
    });
}

function initDiscoverFilters(labels, reverseUrl) {
    const root = document.querySelector('[data-discover-city-search]');
    if (root) {
        const input = root.querySelector('[data-discover-city-input]');
        const hidden = root.querySelector('#city_id');
        const results = root.querySelector('[data-discover-city-results]');
        const searchUrl = root.dataset.citiesSearchUrl;
        const placesMode = root.dataset.placesSearch === '1';
        const form = root.closest('form');
        let t = null;
        let ac = null;

        const render = (items) => {
            results.innerHTML = '';
            if (!items.length) {
                results.hidden = true;
                return;
            }
            items.forEach((item) => {
                const li = document.createElement('li');
                li.className = 'cursor-pointer px-3 py-2 hover:bg-indigo-50';
                li.innerHTML =
                    '<div class="font-semibold text-slate-900"></div><div class="text-xs text-slate-500"></div>';
                const name = item.name || item.label || '';
                li.children[0].textContent = name;
                li.children[1].textContent =
                    item.subtitle ||
                    [item.parent, item.country].filter(Boolean).join(', ') ||
                    '';
                li.addEventListener('click', () => {
                    const km = Number(document.getElementById('distance_km')?.value || 25);
                    const lat = item.latitude ?? item.lat ?? null;
                    const lng = item.longitude ?? item.lng ?? null;
                    const resolvedCityId =
                        item.city_id ??
                        (typeof item.id === 'number'
                            ? item.id
                            : String(item.id || '').startsWith('city:')
                              ? Number(String(item.id).slice(5))
                              : null);
                    const next = saveDiscoveryState({
                        mode: 'manual',
                        cityId: resolvedCityId,
                        cityName: name,
                        lat,
                        lng,
                        distanceKm: km,
                    });
                    if (hidden) {
                        if (resolvedCityId) {
                            hidden.disabled = false;
                            hidden.value = String(resolvedCityId);
                        } else {
                            hidden.disabled = true;
                            hidden.value = '';
                        }
                    }
                    input.value = name;
                    results.hidden = true;
                    form?.querySelectorAll(
                        'input[name="latitude"], input[name="longitude"], input[name="accuracy_m"]'
                    ).forEach((el) => el.remove());
                    if (lat != null && lng != null && form) {
                        const mk = (n, v) => {
                            const el = document.createElement('input');
                            el.type = 'hidden';
                            el.name = n;
                            el.value = String(v);
                            form.appendChild(el);
                        };
                        mk('latitude', lat);
                        mk('longitude', lng);
                    }
                    let modeEl = form?.querySelector('input[name="discovery_mode"]');
                    if (!modeEl && form) {
                        modeEl = document.createElement('input');
                        modeEl.type = 'hidden';
                        modeEl.name = 'discovery_mode';
                        form.appendChild(modeEl);
                    }
                    if (modeEl) modeEl.value = 'manual';
                    void next;
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
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                    signal: ac.signal,
                    credentials: 'same-origin',
                });
                if (!res.ok) throw new Error('fail');
                const payload = await res.json();
                let rows = Array.isArray(payload.data) ? payload.data : [];
                // Normalize places API shape { label, latitude, longitude }
                if (placesMode) {
                    rows = rows.map((r) => ({
                        id: r.id ?? null,
                        name: r.name || r.label,
                        subtitle: r.subtitle || r.parent || '',
                        latitude: r.latitude,
                        longitude: r.longitude,
                    }));
                }
                render(rows);
            } catch (e) {
                if (e && e.name === 'AbortError') return;
                render([]);
            }
        };

        input?.addEventListener('input', () => {
            if (hidden) hidden.value = '';
            clearTimeout(t);
            t = setTimeout(run, 280);
        });
        input?.addEventListener('focus', () => {
            if (!results.children.length) run();
        });
    }

    const bindGps = (gpsBtn) => {
        gpsBtn?.addEventListener('click', async () => {
            gpsBtn.disabled = true;
            paintLocationStatus({}, labels, { detecting: true });
            try {
                const pos = await requestDevicePosition();
                const geo = await reverseGeocodeLabel(pos.lat, pos.lng, reverseUrl);
                const km = Number(document.getElementById('distance_km')?.value || 25);
                const next = saveDiscoveryState({
                    mode: 'device',
                    lat: pos.lat,
                    lng: pos.lng,
                    accuracyM: pos.accuracyM,
                    locationLabel: geo?.display_name || null,
                    shortLabel: geo?.short_name || null,
                    distanceKm: km,
                });
                paintLocationStatus(next, labels, { lowAccuracy: pos.approximate });
                window.location.assign(buildDiscoverUrl(window.location.href, next).toString());
            } catch (err) {
                gpsBtn.disabled = false;
                const code = err?.message || 'unavailable';
                const msg =
                    code === 'denied'
                        ? gpsBtn.dataset.locationDenied
                        : code === 'timeout'
                          ? gpsBtn.dataset.locationTimeout
                          : gpsBtn.dataset.locationUnavailable;
                window.alert(msg || 'Location unavailable');
                const panel = document.querySelector('[data-discover-location-status]');
                panel?.classList.add('hidden');
            }
        });
    };

    document.querySelectorAll('[data-discover-use-gps]').forEach(bindGps);
}
