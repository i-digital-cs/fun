/**
 * Living Map V3.1 — cinematic visual fidelity upgrade (preview only).
 * Production Home / Discover remain on Leaflet.
 */
import { loadFunnityStyle } from './living-map/funnity-style.js';

const EGYPT_BOUNDS = [
    [24.7, 21.8],
    [35.2, 31.9],
];

const CATEGORY_THEME = {
    football: { color: '#FF762E', emoji: '⚽', soft: '#FFE8D6', glow: 'rgba(255,118,46,.45)' },
    basketball: { color: '#F97316', emoji: '🏀', soft: '#FFEDD5', glow: 'rgba(249,115,22,.4)' },
    tennis: { color: '#22C55E', emoji: '🎾', soft: '#DCFCE7', glow: 'rgba(34,197,94,.4)' },
    running: { color: '#14B8A6', emoji: '🏃', soft: '#CCFBF1', glow: 'rgba(20,184,166,.4)' },
    outdoor: { color: '#10B981', emoji: '🌳', soft: '#D1FAE5', glow: 'rgba(16,185,129,.4)' },
    gaming: { color: '#3B82F6', emoji: '🎮', soft: '#DBEAFE', glow: 'rgba(59,130,246,.45)' },
    playstation: { color: '#2563EB', emoji: '🎮', soft: '#DBEAFE', glow: 'rgba(37,99,235,.45)' },
    'board-games': { color: '#6366F1', emoji: '♟️', soft: '#E0E7FF', glow: 'rgba(99,102,241,.4)' },
    cinema: { color: '#F43F5E', emoji: '🎬', soft: '#FFE4E6', glow: 'rgba(244,63,94,.45)' },
    social: { color: '#EC4899', emoji: '🤝', soft: '#FCE7F3', glow: 'rgba(236,72,153,.45)' },
    music: { color: '#7138F5', emoji: '🎵', soft: '#F1EBFF', glow: 'rgba(113,56,245,.45)' },
    food: { color: '#F59E0B', emoji: '☕', soft: '#FEF3C7', glow: 'rgba(245,158,11,.4)' },
    default: { color: '#7138F5', emoji: '✨', soft: '#F1EBFF', glow: 'rgba(113,56,245,.4)' },
};

function themeFor(name = '', slug = '') {
    const key = String(slug || name)
        .toLowerCase()
        .replace(/\s+/g, '-');
    if (CATEGORY_THEME[key]) return CATEGORY_THEME[key];
    if (/music|concert/.test(key)) return CATEGORY_THEME.music;
    if (/food|cafe|coffee/.test(key)) return CATEGORY_THEME.food;
    return CATEGORY_THEME.default;
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function setStatus(root, msg) {
    const el = root.querySelector('[data-lm3-status]');
    if (el) el.textContent = msg || '';
}

function setFocus(root, title, sub) {
    const t = root.querySelector('[data-lm3-focus-label]');
    const s = root.querySelector('[data-lm3-focus-sub]');
    if (t) t.textContent = title;
    if (s) s.textContent = sub;
}

function uid(prefix = 'g') {
    return `${prefix}${Math.random().toString(16).slice(2, 8)}`;
}

function markerSvg(theme, selected = false) {
    const gid = uid('mg');
    const glow = selected ? 0.65 : 0.35;
    return `<svg viewBox="0 0 48 60" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <defs>
        <radialGradient id="${gid}" cx="50%" cy="32%" r="60%">
          <stop offset="0%" stop-color="#fff" stop-opacity=".7"/>
          <stop offset="55%" stop-color="${theme.color}" stop-opacity="1"/>
          <stop offset="100%" stop-color="${theme.color}" stop-opacity="1"/>
        </radialGradient>
        <filter id="${gid}b" x="-40%" y="-20%" width="180%" height="160%">
          <feDropShadow dx="0" dy="6" stdDeviation="4" flood-color="${theme.color}" flood-opacity="${glow}"/>
        </filter>
      </defs>
      <ellipse cx="24" cy="55" rx="12" ry="3.5" fill="${theme.color}" opacity=".28"/>
      <path filter="url(#${gid}b)" d="M24 2c-10.5 0-19 8.2-19 18.4 0 12.6 14.6 29.4 17.9 32.9a1.4 1.4 0 0 0 2.1 0C28.4 49.8 43 33 43 20.4 43 10.2 34.5 2 24 2z" fill="url(#${gid})" stroke="#fff" stroke-width="2.4"/>
      <circle cx="24" cy="20" r="10.2" fill="#fff"/>
      <text x="24" y="24.2" text-anchor="middle" font-size="12">${theme.emoji}</text>
    </svg>`;
}

function createPinEl(item, theme, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'lm3-marker';
    el.style.setProperty('--lm3-glow', theme.glow);
    el.innerHTML = markerSvg(theme);
    el.title = item.title || 'Event';
    el.dataset.eventId = String(item.id || '');
    el.addEventListener('click', (e) => {
        e.stopPropagation();
        onClick(item, el);
    });
    return el;
}

function aggregateDisplayName(item) {
    if (item.type === 'city_cluster') {
        const n = Number(item.city_count) || (item.cities || []).length || 0;
        return n > 0 ? `${n} cities` : 'City cluster';
    }
    return item.city_name || (item.label || '').split('·')[0].trim() || 'City';
}

function createAggEl(item, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = item.type === 'city_cluster' ? 'lm3-agg lm3-agg--cluster' : 'lm3-agg';
    const name = aggregateDisplayName(item);
    const short = name.length > 10 ? `${name.slice(0, 9)}…` : name;
    const count = item.count ?? item.city_count ?? '?';
    el.innerHTML = `<span class="lm3-agg__count">${count}</span><span class="lm3-agg__name">${short}</span>`;
    el.title = item.label || name;
    el.dataset.cityId = String(item.city_id || item.id || '');
    el.addEventListener('click', (e) => {
        e.stopPropagation();
        onClick(item, el);
    });
    return el;
}

function placeCard(root, stage, point) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card || !stage) return;
    const rect = stage.getBoundingClientRect();
    const w = Math.min(300, rect.width - 24);
    const x = Math.min(Math.max(point.x - w / 2, 12), rect.width - w - 12);
    const y = Math.min(Math.max(point.y - 230, 56), rect.height - 250);
    card.style.width = `${w}px`;
    card.style.left = `${x}px`;
    card.style.top = `${y}px`;
    card.style.right = 'auto';
}

function openEventCard(root, item, theme, mode = 'event') {
    const card = root.querySelector('[data-lm3-card]');
    if (!card) return;
    card.hidden = false;
    card.dataset.mode = mode;
    const media = card.querySelector('[data-lm3-card-media]');
    const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
    const fallbackEmoji = mode === 'city' ? '📍' : theme.emoji;
    const paintFallback = () => {
        media.innerHTML = `<div class="lm3-card__fallback" style="--c:${theme.color};--s:${theme.soft}">${fallbackEmoji}</div>`;
    };
    if (imageUrl) {
        media.innerHTML = `<img src="${imageUrl}" alt="" loading="lazy">`;
        const img = media.querySelector('img');
        if (img) img.addEventListener('error', paintFallback, { once: true });
    } else {
        paintFallback();
    }
    const cat = card.querySelector('[data-lm3-card-cat]');
    if (mode === 'city') {
        const cityLabel = aggregateDisplayName(item);
        cat.textContent =
            item.type === 'city_cluster'
                ? `📍 ${cityLabel} · clustered city-level`
                : `📍 ${cityLabel} · city-level`;
        cat.style.background = '#F1EBFF';
        cat.style.color = '#7138F5';
    } else {
        cat.textContent = `${theme.emoji} ${item.category_name || 'Event'}`;
        cat.style.background = theme.soft;
        cat.style.color = theme.color;
    }
    card.querySelector('[data-lm3-card-title]').textContent =
        mode === 'city'
            ? `${aggregateDisplayName(item)} · ${item.count || 0} events`
            : item.title || 'Event';
    const when = item.starts_at
        ? new Date(item.starts_at).toLocaleString(undefined, {
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
          })
        : '';
    const bits = [];
    if (mode === 'city') {
        bits.push(
            item.type === 'city_cluster'
                ? 'Zoom to expand clustered cities'
                : 'Approximate city locations'
        );
        bits.push(`${item.count || 0} eligible events`);
    } else {
        if (when) bits.push(when);
        if (item.venue_label) bits.push(item.venue_label);
        if (item.location_precision !== 'exact') bits.push('City-level');
        if (item.members_count != null) bits.push(`${item.members_count} going`);
    }
    card.querySelector('[data-lm3-card-meta]').textContent = bits.join(' · ');
    const link = card.querySelector('[data-lm3-card-link]');
    if (mode === 'city') {
        link.textContent = 'Browse city events';
        link.href = `/events?city_id=${encodeURIComponent(item.city_id || '')}`;
    } else {
        link.textContent = 'View Event';
        link.href = item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events';
    }
    requestAnimationFrame(() => card.classList.add('is-open'));
}

function closeCard(root) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card) return;
    card.classList.remove('is-open');
    setTimeout(() => {
        card.hidden = true;
    }, 240);
}

function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2;
}

function animateCamera(map, to, duration = 2200) {
    return new Promise((resolve) => {
        if (!map) return resolve();
        if (prefersReducedMotion()) {
            map.jumpTo(to);
            return resolve();
        }
        const from = {
            center: map.getCenter(),
            zoom: map.getZoom(),
            pitch: map.getPitch(),
            bearing: map.getBearing(),
        };
        const start = performance.now();
        const step = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const e = easeInOutCubic(t);
            map.jumpTo({
                center: [
                    from.center.lng + (to.center[0] - from.center.lng) * e,
                    from.center.lat + (to.center[1] - from.center.lat) * e,
                ],
                zoom: from.zoom + (to.zoom - from.zoom) * e,
                pitch: from.pitch + ((to.pitch ?? from.pitch) - from.pitch) * e,
                bearing: from.bearing + ((to.bearing ?? from.bearing) - from.bearing) * e,
            });
            if (t < 1) requestAnimationFrame(step);
            else resolve();
        };
        requestAnimationFrame(step);
    });
}

function ensureArcLayer(map) {
    if (map.getSource('lm3-arc')) return;
    map.addSource('lm3-arc', {
        type: 'geojson',
        data: { type: 'FeatureCollection', features: [] },
    });
    map.addLayer({
        id: 'lm3-arc-glow',
        type: 'line',
        source: 'lm3-arc',
        paint: {
            'line-color': '#A78BFA',
            'line-width': 8,
            'line-opacity': 0.25,
            'line-blur': 2,
        },
    });
    map.addLayer({
        id: 'lm3-arc-line',
        type: 'line',
        source: 'lm3-arc',
        paint: {
            'line-color': '#7138F5',
            'line-width': 2.5,
            'line-opacity': 0.75,
        },
    });
}

function drawArc(map, a, b) {
    ensureArcLayer(map);
    const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2 + Math.max(0.12, Math.abs(a[1] - b[1]) * 0.35)];
    map.getSource('lm3-arc').setData({
        type: 'FeatureCollection',
        features: [
            {
                type: 'Feature',
                geometry: { type: 'LineString', coordinates: [a, mid, b] },
            },
        ],
    });
    setTimeout(() => {
        if (map.getSource('lm3-arc')) {
            map.getSource('lm3-arc').setData({ type: 'FeatureCollection', features: [] });
        }
    }, 2600);
}

function computeFeaturedFocus(payload) {
    const pts = [];
    (payload.city_aggregates || []).forEach((c) => {
        if (c.latitude == null || c.longitude == null) return;
        const w = Math.max(1, Number(c.count) || 1);
        for (let i = 0; i < w; i += 1) pts.push([c.longitude, c.latitude]);
    });
    (payload.markers || []).forEach((m) => {
        if (m.latitude == null || m.longitude == null) return;
        pts.push([m.longitude, m.latitude]);
    });
    (payload.clusters || []).forEach((m) => {
        if (m.latitude == null || m.longitude == null) return;
        pts.push([m.longitude, m.latitude]);
    });
    if (pts.length === 0) {
        return {
            center: [31.24, 30.05],
            zoom: 7.2,
            pitch: 42,
            bearing: -8,
            label: 'Featured activity region',
            sub: 'Greater Cairo corridor · activity-weighted',
        };
    }
    let minLng = Infinity;
    let maxLng = -Infinity;
    let minLat = Infinity;
    let maxLat = -Infinity;
    let sx = 0;
    let sy = 0;
    pts.forEach(([lng, lat]) => {
        minLng = Math.min(minLng, lng);
        maxLng = Math.max(maxLng, lng);
        minLat = Math.min(minLat, lat);
        maxLat = Math.max(maxLat, lat);
        sx += lng;
        sy += lat;
    });
    const center = [sx / pts.length, sy / pts.length];
    const span = Math.max(maxLng - minLng, maxLat - minLat);
    let zoom = 8.4;
    if (span > 6) zoom = 6.2;
    else if (span > 3) zoom = 7.1;
    else if (span > 1.2) zoom = 8.2;
    else if (span > 0.4) zoom = 9.4;
    else zoom = 10.6;
    return {
        center,
        zoom,
        pitch: prefersReducedMotion() ? 0 : 48,
        bearing: prefersReducedMotion() ? 0 : -6,
        label: 'Featured activity region',
        sub: 'Weighted by live eligible events · not your GPS',
        bounds: [
            [minLng, minLat],
            [maxLng, maxLat],
        ],
    };
}

async function fetchMapPayload(bounds, zoom) {
    const params = new URLSearchParams({
        north: String(bounds.getNorth()),
        south: String(bounds.getSouth()),
        east: String(bounds.getEast()),
        west: String(bounds.getWest()),
        zoom: String(Math.round(zoom)),
        include_approximate: '1',
        limit: '100',
        discovery_mode: 'nationwide',
    });
    const res = await fetch(`/api/discovery/map?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`discovery map ${res.status}`);
    return res.json();
}

async function initLivingMapV3(root = document.querySelector('[data-living-map-v3]')) {
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const mapEl = root.querySelector('[data-lm3-map]');
    const stage = root.querySelector('[data-lm3-stage]');
    const tourBtn = root.querySelector('[data-lm3-tour]');
    const strip = root.querySelector('[data-lm3-strip]');
    if (!mapEl || !window.maplibregl) {
        setStatus(root, 'MapLibre failed to load');
        return;
    }

    setStatus(root, 'Loading Funnity style…');
    let style;
    try {
        style = await loadFunnityStyle(root.dataset.styleUrl);
    } catch (e) {
        console.warn(e);
        style = root.dataset.styleUrl;
        setStatus(root, 'Style fallback · Liberty');
    }

    const map = new window.maplibregl.Map({
        container: mapEl,
        style,
        center: [31.24, 30.05],
        zoom: 7.4,
        pitch: prefersReducedMotion() ? 0 : 46,
        bearing: prefersReducedMotion() ? 0 : -8,
        maxBounds: EGYPT_BOUNDS,
        attributionControl: { compact: true },
        cooperativeGestures: true,
    });
    map.addControl(new window.maplibregl.NavigationControl({ visualizePitch: true }), 'bottom-right');

    const markerObjs = [];
    let selectedEl = null;
    let touring = false;
    let tourTimer = null;
    let tourIndex = 0;
    let exactTour = [];
    let cityTour = [];
    let tourMode = 'exact';
    let payloadCache = null;
    let cityEventsByCity = new Map();

    const clearMarkers = () => {
        markerObjs.forEach((m) => m.remove());
        markerObjs.length = 0;
        selectedEl = null;
    };

    const highlightStrip = (key) => {
        strip?.querySelectorAll('[data-strip-key]').forEach((a) => {
            a.classList.toggle('is-active', a.dataset.stripKey === key);
        });
    };

    const selectEvent = async (item, el) => {
        stopTour(false);
        if (selectedEl) {
            selectedEl.classList.remove('is-selected', 'is-float');
            const prevTheme = themeFor(selectedEl.dataset.cat);
            selectedEl.innerHTML = markerSvg(prevTheme);
        }
        selectedEl = el || null;
        const theme = themeFor(item.category_name);
        if (el) {
            el.dataset.cat = item.category_name || '';
            el.classList.add('is-selected', 'is-float');
            el.innerHTML = markerSvg(theme, true);
        }
        openEventCard(root, item, theme, 'event');
        highlightStrip(`e-${item.id}`);
        setFocus(root, item.title || 'Event', item.location_precision === 'exact' ? 'Exact venue' : 'City-level location');
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: Math.max(map.getZoom(), 12.4),
                pitch: prefersReducedMotion() ? 0 : 52,
                bearing: (map.getBearing() + 16) % 360,
            },
            1700
        );
        placeCard(root, stage, map.project([item.longitude, item.latitude]));
    };

    const selectCity = async (item, el) => {
        stopTour(false);
        if (selectedEl) selectedEl.classList.remove('is-selected', 'is-pulse');
        selectedEl = el || null;
        el?.classList.add('is-selected', 'is-pulse');
        openEventCard(root, item, CATEGORY_THEME.default, 'city');
        highlightStrip(`c-${item.city_id || item.id}`);
        const isCluster = item.type === 'city_cluster';
        setFocus(
            root,
            aggregateDisplayName(item),
            isCluster
                ? `${item.count || 0} events across nearby cities · approximate`
                : `${item.count || 0} city-level events · approximate locations`
        );
        const cityEvents = cityEventsByCity.get(String(item.city_id || item.id)) || [];
        renderStrip(cityEvents.length ? cityEvents : [item], item.city_id || item.id);
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: isCluster
                    ? Math.min(11.2, Number(item.expansion_zoom) || 10.2)
                    : Math.min(11.8, (item.expansion_zoom || 10) + 1.4),
                pitch: prefersReducedMotion() ? 0 : 50,
                bearing: -12,
            },
            1600
        );
        placeCard(root, stage, map.project([item.longitude, item.latitude]));
    };

    const renderStrip = (items, cityFilter = null) => {
        if (!strip) return;
        strip.innerHTML = '';
        items.slice(0, 10).forEach((item) => {
            const isCity = item.type === 'city_aggregate' || item.type === 'city_cluster';
            const theme = themeFor(item.category_name);
            const a = document.createElement('button');
            a.type = 'button';
            a.className = 'lm3-strip__card';
            a.dataset.stripKey = isCity ? `c-${item.city_id || item.id}` : `e-${item.id}`;
            a.innerHTML = `
              <div class="lm3-strip__media" style="--s:${theme.soft}"><span>${
                  isCity ? '📍' : theme.emoji
              }</span></div>
              <div class="pad">
                <span class="lm3-strip__cat" style="color:${theme.color}">${
                    isCity
                        ? item.type === 'city_cluster'
                            ? 'City cluster'
                            : 'City aggregate'
                        : `${theme.emoji} ${item.category_name || 'Event'}`
                }</span>
                <h4>${isCity ? aggregateDisplayName(item) : item.title || ''}</h4>
                <p>${isCity ? `${item.count || 0} events · approximate` : item.venue_label || ''}</p>
              </div>`;
            a.addEventListener('click', () => {
                if (isCity) selectCity(item);
                else if (item.latitude != null) selectEvent(item);
            });
            strip.appendChild(a);
        });
        if (cityFilter) highlightStrip(`c-${cityFilter}`);
    };

    const paintData = (payload) => {
        clearMarkers();
        payloadCache = payload;
        cityEventsByCity = new Map();
        const exact = [];
        const approx = [];

        (payload.markers || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact') exact.push(item);
            else {
                approx.push(item);
                const cid = String(item.city_id || '');
                // Best-effort city grouping via venue label city suffix is unreliable;
                // attach later from aggregates when selecting cities.
            }
        });
        (payload.clusters || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact' || Number(item.count) === 1) exact.push(item);
        });

        // Index approximate markers near each city aggregate for city cards.
        (payload.city_aggregates || []).forEach((city) => {
            if (city.latitude == null) return;
            const near = approx.filter((m) => {
                const dlat = Math.abs(m.latitude - city.latitude);
                const dlng = Math.abs(m.longitude - city.longitude);
                return dlat < 0.35 && dlng < 0.35;
            });
            cityEventsByCity.set(String(city.city_id || city.id), near.length ? near : approx.slice(0, 4));
        });

        exactTour = exact.filter((e) => e.id && e.latitude != null);
        // City Discovery Tour uses named city aggregates only (not zoom clusters).
        cityTour = (payload.city_aggregates || [])
            .filter(
                (c) =>
                    c.type !== 'city_cluster' &&
                    c.city_name &&
                    c.latitude != null &&
                    c.longitude != null &&
                    Number(c.count) > 0
            )
            .sort((a, b) => Number(b.count) - Number(a.count));

        exact.forEach((item) => {
            const theme = themeFor(item.category_name);
            const el = createPinEl(item, theme, selectEvent);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
        });

        (payload.city_aggregates || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            const el = createAggEl(item, selectCity);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'center' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
        });

        renderStrip([...exact, ...approx]);
        tourMode = exactTour.length >= 2 ? 'exact' : cityTour.length >= 2 ? 'city' : 'ambient';
        setStatus(
            root,
            tourMode === 'exact'
                ? `${exactTour.length} exact · ${cityTour.length} cities · ▶ event tour`
                : tourMode === 'city'
                  ? `${cityTour.length} cities · ▶ city discovery tour`
                  : 'Ambient showcase'
        );
    };

    const stopTour = (updateButton = true) => {
        touring = false;
        if (tourTimer) clearTimeout(tourTimer);
        if (updateButton) {
            tourBtn?.setAttribute('aria-pressed', 'false');
            if (tourBtn) tourBtn.textContent = '▶';
        }
    };

    const runTourStep = async () => {
        if (!touring || prefersReducedMotion() || document.hidden) return;
        if (tourMode === 'exact' && exactTour.length >= 2) {
            const item = exactTour[tourIndex % exactTour.length];
            const next = exactTour[(tourIndex + 1) % exactTour.length];
            tourIndex += 1;
            const theme = themeFor(item.category_name);
            openEventCard(root, item, theme, 'event');
            highlightStrip(`e-${item.id}`);
            placeCard(root, stage, map.project([item.longitude, item.latitude]));
            if (next) drawArc(map, [item.longitude, item.latitude], [next.longitude, next.latitude]);
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: 12.2,
                    pitch: 54,
                    bearing: (map.getBearing() + 24) % 360,
                },
                2400
            );
            placeCard(root, stage, map.project([item.longitude, item.latitude]));
        } else if (tourMode === 'city' && cityTour.length >= 2) {
            const city = cityTour[tourIndex % cityTour.length];
            tourIndex += 1;
            openEventCard(root, city, CATEGORY_THEME.default, 'city');
            const events = cityEventsByCity.get(String(city.city_id || city.id)) || [];
            renderStrip(events.length ? events : [city], city.city_id);
            setFocus(root, city.city_name || 'City', 'City Discovery Tour · approximate locations');
            const next = cityTour[tourIndex % cityTour.length];
            if (next) drawArc(map, [city.longitude, city.latitude], [next.longitude, next.latitude]);
            await animateCamera(
                map,
                {
                    center: [city.longitude, city.latitude],
                    zoom: 10.6,
                    pitch: 48,
                    bearing: -14 + (tourIndex % 3) * 8,
                },
                2300
            );
            placeCard(root, stage, map.project([city.longitude, city.latitude]));
        } else {
            stopTour();
            return;
        }
        if (!touring) return;
        tourTimer = setTimeout(runTourStep, 3000);
    };

    const startTour = () => {
        if (prefersReducedMotion()) {
            setStatus(root, 'Reduced motion — tour disabled');
            return;
        }
        if (tourMode === 'ambient') {
            setStatus(root, 'Ambient map — not enough regions for a tour');
            return;
        }
        touring = true;
        tourBtn?.setAttribute('aria-pressed', 'true');
        if (tourBtn) tourBtn.textContent = '❚❚';
        runTourStep();
    };

    map.on('load', async () => {
        try {
            map.setFog({
                color: 'rgb(214, 228, 255)',
                'high-color': 'rgb(186, 210, 255)',
                'horizon-blend': 0.12,
                'space-color': 'rgb(245, 240, 255)',
                'star-intensity': 0,
            });
        } catch {
            /* optional */
        }

        // Overview query across Egypt to choose featured activity region.
        setStatus(root, 'Composing featured region…');
        const overviewBounds = new window.maplibregl.LngLatBounds(
            [24.9, 22.0],
            [35.0, 31.7]
        );
        let payload;
        try {
            payload = await fetchMapPayload(overviewBounds, 6);
        } catch (e) {
            console.warn(e);
            setStatus(root, 'Discovery API unavailable');
            return;
        }

        const focus = computeFeaturedFocus(payload);
        setFocus(root, focus.label, focus.sub);
        await animateCamera(
            map,
            {
                center: focus.center,
                zoom: focus.zoom,
                pitch: focus.pitch,
                bearing: focus.bearing,
            },
            prefersReducedMotion() ? 0 : 1600
        );

        // Refetch for the framed viewport so markers match composition.
        try {
            payload = await fetchMapPayload(map.getBounds(), map.getZoom());
        } catch {
            /* keep overview payload */
        }
        paintData(payload);
        // Auto-open strongest city or exact as focal event.
        if (exactTour[0]) {
            // Don't auto-select; keep composition clean. User/tour starts focus.
        } else if (cityTour[0]) {
            // subtle: leave aggregates visible
        }
        setStatus(
            root,
            tourMode === 'city'
                ? 'Ready · ▶ City Discovery Tour'
                : tourMode === 'exact'
                  ? 'Ready · ▶ Event tour'
                  : 'Ready'
        );
    });

    map.on('movestart', (e) => {
        if (e.originalEvent) stopTour();
    });
    map.on('click', () => closeCard(root));

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopTour();
    });
    const io = new IntersectionObserver((entries) => {
        if (!entries.some((en) => en.isIntersecting)) stopTour();
    });
    io.observe(stage);

    tourBtn?.addEventListener('click', () => (touring ? stopTour() : startTour()));
    root.querySelector('[data-lm3-zoom-in]')?.addEventListener('click', () => map.zoomIn({ duration: 400 }));
    root.querySelector('[data-lm3-zoom-out]')?.addEventListener('click', () => map.zoomOut({ duration: 400 }));
    root.querySelector('[data-lm3-reset]')?.addEventListener('click', async () => {
        stopTour();
        closeCard(root);
        if (!payloadCache) return;
        const focus = computeFeaturedFocus(payloadCache);
        setFocus(root, focus.label, focus.sub);
        await animateCamera(map, {
            center: focus.center,
            zoom: focus.zoom,
            pitch: focus.pitch,
            bearing: focus.bearing,
        });
        paintData(payloadCache);
    });

    root._lm3 = {
        map,
        getTourMode: () => tourMode,
        getExactCount: () => exactTour.length,
        getCityCount: () => cityTour.length,
    };
}

function boot() {
    initLivingMapV3();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { initLivingMapV3 };
