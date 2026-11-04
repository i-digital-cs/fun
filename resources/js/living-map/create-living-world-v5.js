/**
 * Living World V5 — real geographic cinematic MapLibre feasibility prototype.
 * Continuous pan/zoom Egypt geography. No static city plates.
 */
import { loadFunnityStyleV5, VISUAL_HEIGHT_FALLBACK_M } from './funnity-style-v5.js';
import { V5_CITIES } from './v5/cities.js';

const RTL_PLUGIN_URL =
    'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js';

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

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

async function ensureRtlTextPlugin() {
    const gl = window.maplibregl;
    if (!gl?.setRTLTextPlugin) return 'missing';
    try {
        const status = gl.getRTLTextPluginStatus?.() || 'unknown';
        if (status === 'loaded' || status === 'unavailable') return status;
        const pending = gl.setRTLTextPlugin(RTL_PLUGIN_URL, false);
        if (pending?.then) await pending.catch(() => {});
        return gl.getRTLTextPluginStatus?.() || 'unknown';
    } catch {
        return 'error';
    }
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

function summarizeBuildings(features) {
    const heights = [];
    let withHeight = 0;
    let zeroOrMissing = 0;
    let colourTagged = 0;
    const unique = new Set();

    features.forEach((f) => {
        const p = f.properties || {};
        const id = f.id ?? `${p.render_height}-${JSON.stringify(f.geometry?.coordinates?.[0]?.[0] || '')}`;
        unique.add(String(id));
        const h = Number(p.render_height);
        if (Number.isFinite(h) && h > 0) {
            withHeight += 1;
            heights.push(h);
        } else {
            zeroOrMissing += 1;
        }
        if (p.colour) colourTagged += 1;
    });

    heights.sort((a, b) => a - b);
    const pct = (n) => (heights.length ? heights[Math.min(heights.length - 1, Math.floor(n * (heights.length - 1)))] : null);
    const mean = heights.length ? heights.reduce((a, b) => a + b, 0) / heights.length : null;
    const distinctHeights = new Set(heights.map((h) => Math.round(h * 10) / 10)).size;

    return {
        renderedFeatures: features.length,
        uniqueApprox: unique.size,
        withRenderHeight: withHeight,
        missingOrZeroHeight: zeroOrMissing,
        colourTagged,
        heightMin: heights[0] ?? null,
        heightP50: pct(0.5),
        heightP90: pct(0.9),
        heightMax: heights[heights.length - 1] ?? null,
        heightMean: mean != null ? Math.round(mean * 10) / 10 : null,
        distinctHeightValues: distinctHeights,
        visualFallbackM: VISUAL_HEIGHT_FALLBACK_M,
        note:
            distinctHeights <= 2 && withHeight > 0
                ? 'Heights look heavily defaulted — treat as visualization massing, not surveyed elevations.'
                : withHeight === 0
                  ? 'No render_height in viewport — extrusion uses visualization fallback only.'
                  : 'Mixed OSM-derived render_height present in viewport.',
    };
}

/**
 * @param {HTMLElement} root
 */
export async function createLivingWorldV5(root) {
    if (!root || root.dataset.ready === '1') return null;
    root.dataset.ready = '1';

    const mapEl = root.querySelector('[data-lw-map]');
    const statusEl = root.querySelector('[data-lw-status]');
    const metricsEl = root.querySelector('[data-lw-metrics]');
    const spotlightEl = root.querySelector('[data-lw-spotlight]');
    const cityNoteEl = root.querySelector('[data-lw-city-note]');
    const locale = root.dataset.locale || 'en';
    const isAr = String(locale).startsWith('ar');

    let city = V5_CITIES.find((c) => c.key === root.dataset.city) || V5_CITIES[0];
    let map = null;
    const markerObjs = [];
    let lastMetrics = null;
    let perf = { styleMs: null, idleMs: null, fpsSample: null };

    const setStatus = (t) => {
        if (statusEl) statusEl.textContent = t || '';
    };

    const renderMetrics = () => {
        if (!metricsEl || !lastMetrics) return;
        const m = lastMetrics;
        metricsEl.innerHTML = `
            <h3>Viewport building sample</h3>
            <dl>
              <div><dt>Rendered building feats</dt><dd>${m.renderedFeatures}</dd></div>
              <div><dt>With render_height &gt; 0</dt><dd>${m.withRenderHeight}</dd></div>
              <div><dt>Missing / zero height</dt><dd>${m.missingOrZeroHeight}</dd></div>
              <div><dt>Height min / p50 / p90 / max</dt><dd>${[m.heightMin, m.heightP50, m.heightP90, m.heightMax].map((v) => (v == null ? '—' : v)).join(' / ')} m</dd></div>
              <div><dt>Distinct height values</dt><dd>${m.distinctHeightValues}</dd></div>
              <div><dt>Visual fallback</dt><dd>${m.visualFallbackM} m (not surveyed)</dd></div>
              <div><dt>Style load</dt><dd>${perf.styleMs != null ? `${perf.styleMs} ms` : '—'}</dd></div>
              <div><dt>Map idle</dt><dd>${perf.idleMs != null ? `${perf.idleMs} ms` : '—'}</dd></div>
            </dl>
            <p class="lw5-metrics__note">${escapeHtml(m.note)}</p>
        `;
    };

    const sampleBuildings = () => {
        if (!map) return;
        try {
            const feats = map.queryRenderedFeatures({ layers: ['building-3d', 'building'] });
            lastMetrics = summarizeBuildings(feats);
            root.dataset.buildingCount = String(lastMetrics.renderedFeatures);
            root.dataset.withHeight = String(lastMetrics.withRenderHeight);
            root.dataset.metricsJson = JSON.stringify({ ...lastMetrics, city: city.key, perf });
            renderMetrics();
        } catch (e) {
            console.warn('building sample', e);
        }
    };

    const clearMarkers = () => {
        markerObjs.forEach((m) => m.remove());
        markerObjs.length = 0;
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
            <div class="lw5-spotlight__media" style="--lw-pin:${theme.color}">
              ${imageUrl ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" width="72" height="72">` : `<span aria-hidden="true">${theme.emoji}</span>`}
            </div>
            <div class="lw5-spotlight__body min-w-0">
              <span class="lw5-spotlight__cat" style="color:${theme.color}">${escapeHtml(item.category_name || item.category || '')}</span>
              <strong dir="auto">${escapeHtml(item.title || '')}</strong>
              <span class="lw5-spotlight__meta" dir="auto">${escapeHtml([when, going].filter(Boolean).join(' · '))}</span>
              <span class="lw5-spotlight__note" dir="auto">${escapeHtml(approx)}</span>
              <a class="lw5-spotlight__cta" href="${escapeHtml(href)}">View Event</a>
            </div>
        `;
        spotlightEl.hidden = false;
    };

    const paintEvents = async () => {
        clearMarkers();
        if (!map || !window.maplibregl) return;
        setStatus('Loading events…');
        try {
            const payload = await fetchDiscovery(city);
            const markers = Array.isArray(payload.markers) ? payload.markers : [];
            const aggs = Array.isArray(payload.city_aggregates) ? payload.city_aggregates : [];
            let first = null;

            aggs.forEach((c) => {
                if (c.latitude == null || c.longitude == null) return;
                if (c.type === 'city_cluster') return;
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'lw5-pin lw5-pin--agg';
                el.textContent = String(c.count || '');
                el.title = `${c.name || c.city_name || 'City'} · Approximate city locations`;
                el.setAttribute(
                    'aria-label',
                    `${c.count || 0} events in ${c.name || c.city_name || 'city'} · Approximate locations`
                );
                const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([c.longitude, c.latitude])
                    .addTo(map);
                markerObjs.push(m);
            });

            markers.forEach((item) => {
                if (item.latitude == null || item.longitude == null) return;
                const theme = themeFor(item.category_name || item.category);
                const el = document.createElement('button');
                el.type = 'button';
                el.className = 'lw5-pin lw5-pin--event';
                el.style.setProperty('--lw-pin', theme.color);
                el.innerHTML = `<span class="lw5-pin__emoji">${theme.emoji}</span>`;
                el.setAttribute(
                    'aria-label',
                    `${item.title || 'Event'} · ${
                        item.location_precision === 'exact' ? 'Exact venue' : 'Approximate city location'
                    }`
                );
                el.addEventListener('click', () => renderSpotlight(item));
                const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                    .setLngLat([item.longitude, item.latitude])
                    .addTo(map);
                markerObjs.push(m);
                if (!first) first = item;
            });

            if (first) renderSpotlight(first);
            else if (spotlightEl) spotlightEl.hidden = true;
            setStatus(`Ready · ${city.nameEn}`);
            root.dataset.eventMarkerCount = String(markerObjs.length);
        } catch (e) {
            console.warn(e);
            setStatus('Discovery API unavailable');
        }
    };

    const applyCinematicLight = () => {
        if (!map?.setLight) return;
        try {
            map.setLight({
                anchor: 'map',
                color: '#fff5eb',
                intensity: 0.42,
                position: [1.35, 210, 35],
            });
        } catch {
            /* ignore */
        }
        try {
            if (typeof map.setSky === 'function') {
                map.setSky({
                    'sky-color': '#c8dcff',
                    'sky-horizon-blend': 0.35,
                    'horizon-color': '#ffe8d2',
                    'horizon-fog-blend': 0.7,
                    'fog-color': '#e8e0ff',
                    'fog-ground-blend': 0.45,
                });
            }
        } catch {
            /* optional */
        }
    };

    const flyToCity = async (next, { animate = true } = {}) => {
        city = next;
        root.dataset.city = city.key;
        root.querySelectorAll('[data-lw-city]').forEach((btn) => {
            btn.setAttribute('aria-pressed', btn.dataset.lwCity === city.key ? 'true' : 'false');
        });
        if (cityNoteEl) {
            cityNoteEl.textContent = city.note;
        }
        const label = root.querySelector('[data-lw-city-label]');
        if (label) label.textContent = isAr ? city.nameAr : city.nameEn;

        const cam = {
            center: [city.lng, city.lat],
            zoom: city.zoom,
            pitch: prefersReducedMotion() ? 0 : city.pitch,
            bearing: prefersReducedMotion() ? 0 : city.bearing,
        };
        if (!animate || prefersReducedMotion()) {
            map.jumpTo(cam);
        } else {
            map.easeTo({ ...cam, duration: 1600, easing: (t) => 1 - (1 - t) ** 3 });
        }
        await new Promise((r) => map.once('idle', r));
        sampleBuildings();
        await paintEvents();
    };

    if (!mapEl || !window.maplibregl) {
        setStatus('MapLibre failed to load');
        return null;
    }

    setStatus('Loading RTL…');
    await ensureRtlTextPlugin();

    const t0 = performance.now();
    setStatus('Loading cinematic style…');
    let style;
    try {
        style = await loadFunnityStyleV5(root.dataset.styleUrl);
    } catch (e) {
        console.warn(e);
        style = root.dataset.styleUrl;
    }
    perf.styleMs = Math.round(performance.now() - t0);

    const t1 = performance.now();
    map = new window.maplibregl.Map({
        container: mapEl,
        style,
        center: [city.lng, city.lat],
        zoom: city.zoom,
        pitch: prefersReducedMotion() ? 0 : city.pitch,
        bearing: prefersReducedMotion() ? 0 : city.bearing,
        maxPitch: 72,
        attributionControl: true,
        hash: false,
    });

    map.addControl(new window.maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');

    await new Promise((resolve) => map.once('load', resolve));
    applyCinematicLight();
    await new Promise((resolve) => map.once('idle', resolve));
    perf.idleMs = Math.round(performance.now() - t1);

    map.on('moveend', () => {
        // Debounced sample after user pans/zooms.
        clearTimeout(map.__lwSampleTimer);
        map.__lwSampleTimer = setTimeout(sampleBuildings, 280);
    });

    root.querySelectorAll('[data-lw-city]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = V5_CITIES.find((c) => c.key === btn.dataset.lwCity);
            if (next) flyToCity(next).catch((e) => console.warn(e));
        });
    });

    root.querySelector('[data-lw-zoom-in]')?.addEventListener('click', () => map.zoomIn({ duration: 300 }));
    root.querySelector('[data-lw-zoom-out]')?.addEventListener('click', () => map.zoomOut({ duration: 300 }));
    root.querySelector('[data-lw-reset]')?.addEventListener('click', () => {
        flyToCity(city).catch((e) => console.warn(e));
    });

    sampleBuildings();
    await paintEvents();
    setStatus(`Ready · ${city.nameEn}`);

    // Expose for QA capture
    root._livingWorldV5 = {
        map,
        getCity: () => city,
        sampleBuildings,
        getMetrics: () => ({ ...lastMetrics, perf, city: city.key }),
        flyToCity: (key) => {
            const next = V5_CITIES.find((c) => c.key === key);
            return next ? flyToCity(next) : Promise.resolve();
        },
    };

    return root._livingWorldV5;
}
