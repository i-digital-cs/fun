/**
 * Mapbox Standard 3D provider trial — ready-made basemap + Discovery API pins.
 * No Three.js, no procedural meshes, no illustrated plates.
 */

const CITIES = [
    {
        key: 'cairo',
        cityId: 2,
        nameEn: 'Cairo',
        nameAr: 'القاهرة',
        lat: 30.0444,
        lng: 31.2357,
        zoom: 15.2,
        pitch: 60,
        bearing: -28,
    },
    {
        key: 'alexandria',
        cityId: 5,
        nameEn: 'Alexandria',
        nameAr: 'الإسكندرية',
        lat: 31.2001,
        lng: 29.9187,
        zoom: 15.0,
        pitch: 58,
        bearing: -22,
    },
    {
        key: 'port-said',
        cityId: 11,
        nameEn: 'Port Said',
        nameAr: 'بورسعيد',
        lat: 31.2653,
        lng: 32.3019,
        zoom: 14.8,
        pitch: 56,
        bearing: -18,
    },
];

const CATEGORY_THEME = {
    football: { color: '#FF762E', emoji: '⚽' },
    tennis: { color: '#22C55E', emoji: '🎾' },
    running: { color: '#14B8A6', emoji: '🏃' },
    outdoor: { color: '#10B981', emoji: '🌳' },
    gaming: { color: '#3B82F6', emoji: '🎮' },
    playstation: { color: '#2563EB', emoji: '🎮' },
    cinema: { color: '#F43F5E', emoji: '🎬' },
    social: { color: '#EC4899', emoji: '🤝' },
    music: { color: '#7138F5', emoji: '🎵' },
    food: { color: '#F59E0B', emoji: '☕' },
    default: { color: '#7138F5', emoji: '✨' },
};

function themeFor(name = '') {
    const key = String(name).toLowerCase().replace(/\s+/g, '-');
    if (CATEGORY_THEME[key]) return CATEGORY_THEME[key];
    if (/music|concert/.test(key)) return CATEGORY_THEME.music;
    if (/food|cafe|coffee/.test(key)) return CATEGORY_THEME.food;
    if (/sport|padel|football|soccer/.test(key)) return CATEGORY_THEME.football;
    if (/game|play/.test(key)) return CATEGORY_THEME.gaming;
    if (/cinema|movie|film/.test(key)) return CATEGORY_THEME.cinema;
    if (/tennis/.test(key)) return CATEGORY_THEME.tennis;
    return CATEGORY_THEME.default;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

async function fetchDiscovery(city) {
    const pad = 0.12;
    const params = new URLSearchParams({
        north: String(city.lat + pad),
        south: String(city.lat - pad),
        east: String(city.lng + pad),
        west: String(city.lng - pad),
        zoom: '13',
        include_approximate: '1',
        limit: '60',
        discovery_mode: 'manual',
        latitude: String(city.lat),
        longitude: String(city.lng),
        distance_km: '25',
    });
    const res = await fetch(`/api/discovery/map?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`discovery ${res.status}`);
    return res.json();
}

/**
 * @param {HTMLElement} root
 */
export async function createMapboxStandardTrial(root) {
    if (!root || root.dataset.ready === '1') return null;
    root.dataset.ready = '1';

    const statusEl = root.querySelector('[data-mbx-status]');
    const metricsEl = root.querySelector('[data-mbx-metrics]');
    const spotlightEl = root.querySelector('[data-mbx-spotlight]');
    const mapEl = root.querySelector('[data-mbx-map]');
    const gateEl = root.querySelector('[data-mbx-token-gate]');
    const locale = root.dataset.locale || 'en';
    const isAr = String(locale).startsWith('ar');

    const setStatus = (t) => {
        if (statusEl) statusEl.textContent = t || '';
    };

    const token = String(root.dataset.mapboxToken || '').trim();
    if (!token || !token.startsWith('pk.') || !window.mapboxgl) {
        if (gateEl) gateEl.hidden = false;
        setStatus('Mapbox public token required');
        root.dataset.metricsJson = JSON.stringify({
            blocked: true,
            reason: !window.mapboxgl
                ? 'mapbox-gl failed to load'
                : 'MAPBOX_ACCESS_TOKEN (pk.*) not configured',
        });
        return null;
    }
    if (gateEl) gateEl.hidden = true;

    window.mapboxgl.accessToken = token;

    let city = CITIES.find((c) => c.key === root.dataset.city) || CITIES[0];
    const markers = [];
    const perf = { mapIdleMs: null, fps: null, fpsSamples: [], jsHeapMb: null };

    const renderMetrics = (extra = {}) => {
        if (!metricsEl) return;
        const mem = performance.memory
            ? Math.round((performance.memory.usedJSHeapSize / 1048576) * 10) / 10
            : null;
        perf.jsHeapMb = mem;
        metricsEl.innerHTML = `
          <h3>Trial metrics</h3>
          <dl>
            <div><dt>City</dt><dd>${escapeHtml(city.nameEn)}</dd></div>
            <div><dt>Style</dt><dd>mapbox://styles/mapbox/standard</dd></div>
            <div><dt>Event markers</dt><dd>${extra.markerCount ?? markers.length}</dd></div>
            <div><dt>Map idle</dt><dd>${perf.mapIdleMs != null ? `${perf.mapIdleMs} ms` : '—'}</dd></div>
            <div><dt>FPS (1s)</dt><dd>${perf.fps ?? '—'}</dd></div>
            <div><dt>JS heap</dt><dd>${mem != null ? `${mem} MB` : 'n/a'}</dd></div>
            <div><dt>3D config</dt><dd>show3dObjects on</dd></div>
          </dl>
          <p class="mbx-metrics__note">3D buildings/landmarks/trees are Mapbox Standard content — coverage varies by city. Event pins come only from Discovery API.</p>
        `;
        root.dataset.metricsJson = JSON.stringify({
            city: city.key,
            style: 'mapbox://styles/mapbox/standard',
            markerCount: extra.markerCount ?? markers.length,
            perf,
            camera: {
                lng: city.lng,
                lat: city.lat,
                zoom: city.zoom,
                pitch: city.pitch,
                bearing: city.bearing,
            },
            blocked: false,
        });
    };

    const clearMarkers = () => {
        markers.forEach((m) => m.remove());
        markers.length = 0;
    };

    const renderSpotlight = (item) => {
        if (!spotlightEl || !item) {
            if (spotlightEl) spotlightEl.hidden = true;
            return;
        }
        const theme = themeFor(item.category_name || item.category);
        const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
        const approx =
            item.location_precision === 'exact'
                ? 'Exact venue coordinates'
                : 'Approximate city location · not an exact venue pin';
        const href = item.url || (item.id ? `/events/${item.id}` : '/events');
        let when = '';
        try {
            if (item.starts_at) {
                when = new Date(item.starts_at).toLocaleString(undefined, {
                    month: 'short',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                });
            }
        } catch {
            /* ignore */
        }
        const going =
            item.members_count != null && Number(item.members_count) > 0
                ? `${item.members_count} going`
                : '';

        spotlightEl.innerHTML = `
            <div class="mbx-spotlight__media">
              ${
                  imageUrl
                      ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" width="72" height="72">`
                      : `<span aria-hidden="true">${theme.emoji}</span>`
              }
            </div>
            <div class="min-w-0">
              <span class="mbx-spotlight__cat" style="color:${theme.color}">${escapeHtml(item.category_name || item.category || '')}</span>
              <strong dir="auto">${escapeHtml(item.title || '')}</strong>
              <span class="mbx-spotlight__meta" dir="auto">${escapeHtml([when, going].filter(Boolean).join(' · '))}</span>
              <span class="mbx-spotlight__note" dir="auto">${escapeHtml(approx)}</span>
              <a class="mbx-spotlight__cta" href="${escapeHtml(href)}">View Event</a>
            </div>
        `;
        spotlightEl.hidden = false;
    };

    const paintEvents = async () => {
        clearMarkers();
        setStatus('Loading events…');
        try {
            const payload = await fetchDiscovery(city);
            const list = Array.isArray(payload.markers) ? payload.markers : [];
            const aggs = Array.isArray(payload.city_aggregates) ? payload.city_aggregates : [];
            let first = null;

            aggs.forEach((c) => {
                if (c.latitude == null || c.longitude == null || c.type === 'city_cluster') return;
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'mbx-pin mbx-pin--agg';
                el.textContent = String(c.count || '');
                el.setAttribute(
                    'aria-label',
                    `${c.count || 0} events in ${c.name || c.city_name || 'city'} · Approximate locations`
                );
                const m = new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([c.longitude, c.latitude])
                    .addTo(map);
                markers.push(m);
            });

            list.forEach((item) => {
                if (item.latitude == null || item.longitude == null) return;
                const theme = themeFor(item.category_name || item.category);
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'mbx-pin mbx-pin--event';
                el.style.setProperty('--mbx-pin', theme.color);
                el.innerHTML = `<span>${theme.emoji}</span>`;
                el.setAttribute(
                    'aria-label',
                    `${item.title || 'Event'} · ${
                        item.location_precision === 'exact'
                            ? 'Exact venue'
                            : 'Approximate city location'
                    }`
                );
                el.addEventListener('click', () => renderSpotlight(item));
                const m = new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([item.longitude, item.latitude])
                    .addTo(map);
                markers.push(m);
                if (!first) first = item;
            });

            if (first) renderSpotlight(first);
            else if (spotlightEl) spotlightEl.hidden = true;
            setStatus(`Ready · Mapbox Standard · ${city.nameEn}`);
            renderMetrics({ markerCount: markers.length });
        } catch (e) {
            console.warn(e);
            setStatus('Discovery API unavailable');
            renderMetrics({ markerCount: 0 });
        }
    };

    const t0 = performance.now();
    setStatus('Loading Mapbox Standard…');
    const map = new window.mapboxgl.Map({
        container: mapEl,
        style: 'mapbox://styles/mapbox/standard',
        center: [city.lng, city.lat],
        zoom: city.zoom,
        pitch: prefersReducedMotion() ? 0 : city.pitch,
        bearing: prefersReducedMotion() ? 0 : city.bearing,
        attributionControl: true,
    });
    map.addControl(new window.mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');

    const applyStandardConfig = () => {
        try {
            // Mapbox Standard configuration (GL JS v3).
            map.setConfigProperty('basemap', 'lightPreset', 'day');
            map.setConfigProperty('basemap', 'show3dObjects', true);
            // Prefer granular flags when available (newer Standard builds).
            try {
                map.setConfigProperty('basemap', 'show3dBuildings', true);
                map.setConfigProperty('basemap', 'show3dLandmarks', true);
                map.setConfigProperty('basemap', 'show3dTrees', true);
                map.setConfigProperty('basemap', 'show3dFacades', true);
            } catch {
                /* older Standard may only support show3dObjects */
            }
        } catch (e) {
            console.warn('Standard config', e);
        }
    };

    map.on('style.load', applyStandardConfig);
    await new Promise((resolve) => map.once('load', resolve));
    applyStandardConfig();
    await new Promise((resolve) => map.once('idle', resolve));
    perf.mapIdleMs = Math.round(performance.now() - t0);

    let frames = 0;
    let windowStart = performance.now();
    const tick = (now) => {
        frames += 1;
        if (now - windowStart >= 1000) {
            perf.fps = Math.round((frames * 1000) / (now - windowStart));
            perf.fpsSamples.push(perf.fps);
            if (perf.fpsSamples.length > 8) perf.fpsSamples.shift();
            frames = 0;
            windowStart = now;
            renderMetrics();
        }
        requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    const flyToCity = async (next) => {
        city = next;
        root.dataset.city = city.key;
        root.querySelectorAll('[data-mbx-city]').forEach((btn) => {
            btn.setAttribute('aria-pressed', btn.dataset.mbxCity === city.key ? 'true' : 'false');
        });
        const label = root.querySelector('[data-mbx-city-label]');
        if (label) label.textContent = isAr ? city.nameAr : city.nameEn;

        map.easeTo({
            center: [city.lng, city.lat],
            zoom: city.zoom,
            pitch: prefersReducedMotion() ? 0 : city.pitch,
            bearing: prefersReducedMotion() ? 0 : city.bearing,
            duration: prefersReducedMotion() ? 0 : 1400,
        });
        await new Promise((r) => map.once('idle', r));
        await paintEvents();
    };

    root.querySelectorAll('[data-mbx-city]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = CITIES.find((c) => c.key === btn.dataset.mbxCity);
            if (next) flyToCity(next).catch((e) => console.warn(e));
        });
    });

    await paintEvents();

    root._mapboxStandardTrial = {
        map,
        flyToCity: (key) => {
            const next = CITIES.find((c) => c.key === key);
            return next ? flyToCity(next) : Promise.resolve();
        },
        getMetrics: () => {
            try {
                return JSON.parse(root.dataset.metricsJson || '{}');
            } catch {
                return {};
            }
        },
    };

    return root._mapboxStandardTrial;
}
