/**
 * Living Map V3.3 — reusable MapLibre factory (Home / Discover / Public / Preview).
 * Home/public activation is gated by living_map.replace_home_mini_map.
 * Discover production remains Leaflet until a separate owner cutover.
 */
import { loadFunnityStyle } from './funnity-style.js';
import { fillTemplate, readI18n } from './i18n.js';

const EGYPT_BOUNDS = [
    [24.7, 21.8],
    [35.2, 31.9],
];
const RTL_PLUGIN_URL =
    'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js';

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

function themeFor(name = '') {
    const key = String(name).toLowerCase().replace(/\s+/g, '-');
    if (CATEGORY_THEME[key]) return CATEGORY_THEME[key];
    if (/music|concert/.test(key)) return CATEGORY_THEME.music;
    if (/food|cafe|coffee/.test(key)) return CATEGORY_THEME.food;
    if (/sport|padel|football|soccer/.test(key)) return CATEGORY_THEME.football;
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

function uid(prefix = 'g') {
    return `${prefix}${Math.random().toString(16).slice(2, 8)}`;
}

async function ensureRtlTextPlugin() {
    const gl = window.maplibregl;
    if (!gl?.setRTLTextPlugin) return 'missing';
    const statusOf = () => {
        try {
            return gl.getRTLTextPluginStatus?.() || 'unknown';
        } catch {
            return 'unknown';
        }
    };
    let status = statusOf();
    if (status === 'loaded' || status === 'unavailable') return status;

    const requestPlugin = async () => {
        try {
            const pending = gl.setRTLTextPlugin(RTL_PLUGIN_URL, false);
            if (pending?.then) {
                await Promise.race([
                    pending,
                    new Promise((_, rej) => setTimeout(() => rej(new Error('rtl timeout')), 20000)),
                ]).catch((e) => console.warn('RTL plugin wait', e));
            }
        } catch (e) {
            console.warn('setRTLTextPlugin failed', e);
        }
    };

    const waitUntilSettled = async (ms) => {
        const started = Date.now();
        while (Date.now() - started < ms) {
            status = statusOf();
            if (status === 'loaded' || status === 'unavailable' || status === 'error') return status;
            await new Promise((r) => setTimeout(r, 50));
        }
        return statusOf();
    };

    await requestPlugin();
    status = await waitUntilSettled(20000);
    if (status === 'loaded' || status === 'unavailable' || status === 'error') return status;
    await requestPlugin();
    return waitUntilSettled(15000);
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

function aggregateDisplayName(item, i18n) {
    if (item.type === 'city_cluster') {
        const n = Number(item.city_count) || (item.cities || []).length || 0;
        return n > 0 ? `${n}` : i18n.cityCluster;
    }
    return item.city_name || (item.label || '').split('·')[0].trim() || 'City';
}

function readDiscoveryOrigin(root) {
    const lat = Number(root?.dataset?.lat);
    const lng = Number(root?.dataset?.lng);
    const km = Number(root?.dataset?.distanceKm || 25);
    const mode = root?.dataset?.discoveryMode || 'nationwide';
    const hasOrigin =
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        mode !== 'nationwide' &&
        Math.abs(lat) <= 90 &&
        Math.abs(lng) <= 180;
    return {
        lat,
        lng,
        km: Number.isFinite(km) && km > 0 ? km : 25,
        mode,
        hasOrigin,
        label: root?.dataset?.locationLabel || '',
    };
}

function zoomForRadius(km) {
    const n = Number(km);
    if (!Number.isFinite(n) || n <= 0) return 6;
    if (n <= 5) return 13;
    if (n <= 10) return 12;
    if (n <= 25) return 11;
    if (n <= 50) return 10;
    if (n <= 100) return 9;
    return 8;
}

function radiusLngLatBounds(lat, lng, km) {
    const pad = Math.max(1, Number(km) || 25) * 1.15;
    const latPad = pad / 111;
    const lngPad = pad / (111 * Math.max(0.2, Math.cos((lat * Math.PI) / 180)));
    return [
        [lng - lngPad, lat - latPad],
        [lng + lngPad, lat + latPad],
    ];
}

function circlePolygon(lng, lat, radiusKm, steps = 64) {
    const coords = [];
    const latRad = (lat * Math.PI) / 180;
    const kmPerDegLat = 111;
    const kmPerDegLng = 111 * Math.max(0.2, Math.cos(latRad));
    for (let i = 0; i <= steps; i += 1) {
        const a = (i / steps) * Math.PI * 2;
        coords.push([
            lng + (Math.cos(a) * radiusKm) / kmPerDegLng,
            lat + (Math.sin(a) * radiusKm) / kmPerDegLat,
        ]);
    }
    return {
        type: 'Feature',
        properties: {},
        geometry: { type: 'Polygon', coordinates: [coords] },
    };
}

function overviewBounds() {
    return new window.maplibregl.LngLatBounds([24.9, 22.0], [35.0, 31.7]);
}

/**
 * @param {maplibregl.LngLatBoundsLike|{getNorth:Function}} bounds
 * @param {number} zoom
 * @param {ReturnType<typeof readDiscoveryOrigin>} origin
 */
async function fetchMapPayload(bounds, zoom, origin) {
    const get = (fn, fallback) => {
        try {
            if (bounds && typeof bounds[fn] === 'function') return bounds[fn]();
        } catch {
            /* ignore */
        }
        return fallback;
    };
    const north = get('getNorth', Array.isArray(bounds) ? bounds[1][1] : 31.7);
    const south = get('getSouth', Array.isArray(bounds) ? bounds[0][1] : 22.0);
    const east = get('getEast', Array.isArray(bounds) ? bounds[1][0] : 35.0);
    const west = get('getWest', Array.isArray(bounds) ? bounds[0][0] : 24.9);

    const params = new URLSearchParams({
        north: String(north),
        south: String(south),
        east: String(east),
        west: String(west),
        zoom: String(Math.max(5, Math.round(zoom || 6))),
        include_approximate: '1',
        limit: origin?.hasOrigin ? '60' : '100',
    });
    if (origin?.hasOrigin) {
        params.set('discovery_mode', origin.mode === 'manual' ? 'manual' : 'device');
        params.set('latitude', String(origin.lat));
        params.set('longitude', String(origin.lng));
        params.set('distance_km', String(origin.km));
    } else {
        params.set('discovery_mode', 'nationwide');
    }
    const res = await fetch(`/api/discovery/map?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`discovery map ${res.status}`);
    return res.json();
}

async function fetchCityEvents(cityId) {
    const params = new URLSearchParams({ city_id: String(cityId) });
    const res = await fetch(`/api/discovery/city-events?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`city-events ${res.status}`);
    return res.json();
}

function computeFeaturedFocus(payload) {
    const pts = [];
    (payload.city_aggregates || []).forEach((c) => {
        if (c.latitude == null || c.longitude == null || c.type === 'city_cluster') return;
        const w = Math.min(Math.max(1, Number(c.count) || 1), 8);
        for (let i = 0; i < w; i += 1) pts.push([c.longitude, c.latitude]);
    });
    (payload.markers || []).forEach((m) => {
        if (m.latitude == null || m.longitude == null) return;
        pts.push([m.longitude, m.latitude]);
        if (m.location_precision === 'exact') pts.push([m.longitude, m.latitude]);
    });
    if (!pts.length) {
        return { center: [31.24, 30.05], zoom: 7.2, pitch: 42, bearing: -8 };
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
    const span = Math.max(maxLng - minLng, maxLat - minLat);
    let zoom = 8.4;
    if (span > 6) zoom = 6.4;
    else if (span > 3) zoom = 7.2;
    else if (span > 1.2) zoom = 8.3;
    else if (span > 0.4) zoom = 9.5;
    else zoom = 10.8;
    return {
        center: [sx / pts.length, sy / pts.length],
        zoom,
        pitch: prefersReducedMotion() ? 0 : 46,
        bearing: prefersReducedMotion() ? 0 : -6,
    };
}

/**
 * @param {HTMLElement} root [data-living-map]
 */
export async function createLivingMap(root) {
    if (!root || root.dataset.ready === '1') return null;
    root.dataset.ready = '1';

    const i18n = readI18n(root);
    const mode = root.dataset.mode || 'preview';
    const origin = readDiscoveryOrigin(root);
    const mapEl = root.querySelector('[data-lm-map]');
    const stage = root.querySelector('[data-lm-stage]');
    const listEl = root.querySelector('[data-lm-list]');
    const listHeading = root.querySelector('[data-lm-list-heading]');
    const tourBtn = root.querySelector('[data-lm-tour]');
    const prevBtn = root.querySelector('[data-lm-prev]');
    const nextBtn = root.querySelector('[data-lm-next]');
    const cityPanel = root.querySelector('[data-lm-city-panel]');
    const statusEl = root.querySelector('[data-lm-status]');
    const compact = mode === 'home' || mode === 'public';

    const loadPayload = (bounds, zoom) => fetchMapPayload(bounds, zoom, origin);

    const setStatus = (msg) => {
        if (statusEl) statusEl.textContent = msg || '';
    };
    const setFocus = (title, sub) => {
        const t = root.querySelector('[data-lm-focus-label]');
        const s = root.querySelector('[data-lm-focus-sub]');
        if (t) {
            t.textContent = title || '';
            t.setAttribute('dir', 'auto');
        }
        if (s) {
            s.textContent = sub || '';
            s.setAttribute('dir', 'auto');
        }
    };
    const setListHeading = (text) => {
        if (listHeading) {
            listHeading.textContent = text;
            listHeading.setAttribute('dir', 'auto');
        }
    };

    if (!mapEl || !window.maplibregl) {
        setStatus(i18n.maplibreFailed);
        return null;
    }

    setStatus(i18n.loadingRtl);
    const rtlStatus = await ensureRtlTextPlugin();
    if (rtlStatus !== 'loaded' && rtlStatus !== 'unavailable') {
        console.warn('RTL plugin not fully loaded:', rtlStatus);
    }

    setStatus(i18n.loadingStyle);
    let style;
    try {
        style = await loadFunnityStyle(root.dataset.styleUrl);
    } catch (e) {
        console.warn(e);
        style = root.dataset.styleUrl;
        setStatus(i18n.styleFallback);
    }

    const initialCenter = origin.hasOrigin ? [origin.lng, origin.lat] : [31.24, 30.05];
    const initialZoom = origin.hasOrigin
        ? zoomForRadius(origin.km)
        : compact
          ? 7.0
          : 7.4;
    const map = new window.maplibregl.Map({
        container: mapEl,
        style,
        center: initialCenter,
        zoom: initialZoom,
        pitch: prefersReducedMotion() ? 0 : compact ? (origin.hasOrigin ? 42 : 38) : 44,
        bearing: prefersReducedMotion() ? 0 : -8,
        maxBounds: EGYPT_BOUNDS,
        attributionControl: { compact: true },
        cooperativeGestures: true,
    });
    map.addControl(
        new window.maplibregl.NavigationControl({ visualizePitch: !compact }),
        'bottom-right'
    );

    const ensureOriginLayers = () => {
        if (!origin.hasOrigin) return;
        const srcId = 'lm-home-origin';
        if (map.getSource(srcId)) return;
        map.addSource(srcId, {
            type: 'geojson',
            data: {
                type: 'FeatureCollection',
                features: [
                    circlePolygon(origin.lng, origin.lat, origin.km),
                    {
                        type: 'Feature',
                        properties: { kind: 'origin' },
                        geometry: {
                            type: 'Point',
                            coordinates: [origin.lng, origin.lat],
                        },
                    },
                ],
            },
        });
        map.addLayer({
            id: 'lm-home-radius-fill',
            type: 'fill',
            source: srcId,
            filter: ['==', ['geometry-type'], 'Polygon'],
            paint: {
                'fill-color': '#7138F5',
                'fill-opacity': 0.06,
            },
        });
        map.addLayer({
            id: 'lm-home-radius-line',
            type: 'line',
            source: srcId,
            filter: ['==', ['geometry-type'], 'Polygon'],
            paint: {
                'line-color': '#7138F5',
                'line-width': 1.5,
                'line-opacity': 0.45,
            },
        });
        map.addLayer({
            id: 'lm-home-origin-dot',
            type: 'circle',
            source: srcId,
            filter: ['==', ['get', 'kind'], 'origin'],
            paint: {
                'circle-radius': 7,
                'circle-color': '#2563EB',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff',
            },
        });
    };

    const markerObjs = [];
    const pinByEventId = new Map();
    let selectedEl = null;
    let selectedEventId = null;
    let touring = false;
    let tourTimer = null;
    let tourIndex = 0;
    let eventTourQueue = [];
    let cityTour = [];
    let tourMode = 'exact';
    let payloadCache = null;
    let activeCityId = null;
    let activeCityEvents = [];
    let cityEventCursor = 0;
    let listEvents = [];

    const clearMarkers = () => {
        markerObjs.forEach((m) => m.remove());
        markerObjs.length = 0;
        pinByEventId.clear();
        selectedEl = null;
    };

    const closeCard = () => {
        const card = root.querySelector('[data-lm-card]');
        if (!card) return;
        card.classList.remove('is-open');
        setTimeout(() => {
            card.hidden = true;
        }, 240);
    };

    const placeCard = (point) => {
        const card = root.querySelector('[data-lm-card]');
        if (!card || !stage || compact) return;
        const rect = stage.getBoundingClientRect();
        const w = Math.min(280, rect.width - 24);
        const x = Math.min(Math.max(point.x - w / 2, 12), rect.width - w - 12);
        const y = Math.min(Math.max(point.y - 220, 48), rect.height - 240);
        card.style.width = `${w}px`;
        card.style.left = `${x}px`;
        card.style.top = `${y}px`;
        card.style.right = 'auto';
    };

    const openEventCard = (item, theme) => {
        const card = root.querySelector('[data-lm-card]');
        if (!card || compact) return;
        card.hidden = false;
        const media = card.querySelector('[data-lm-card-media]');
        const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
        const paintFallback = () => {
            media.innerHTML = `<div class="lm-card__fallback" style="--c:${theme.color};--s:${theme.soft}">${theme.emoji}</div>`;
        };
        if (imageUrl) {
            media.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy">`;
            const img = media.querySelector('img');
            if (img) img.addEventListener('error', paintFallback, { once: true });
        } else paintFallback();

        const cat = card.querySelector('[data-lm-card-cat]');
        cat.textContent = `${theme.emoji} ${item.category_name || ''}`;
        cat.style.background = theme.soft;
        cat.style.color = theme.color;
        const titleEl = card.querySelector('[data-lm-card-title]');
        titleEl.textContent = item.title || '';
        titleEl.setAttribute('dir', 'auto');
        const when = item.starts_at
            ? new Date(item.starts_at).toLocaleString(undefined, {
                  month: 'short',
                  day: 'numeric',
                  hour: 'numeric',
                  minute: '2-digit',
              })
            : '';
        const bits = [];
        if (when) bits.push(when);
        if (item.venue_label) bits.push(item.venue_label);
        if (item.location_precision && item.location_precision !== 'exact') {
            bits.push(i18n.approximateCityLocation);
        }
        if (item.members_count != null && Number(item.members_count) > 0) {
            bits.push(fillTemplate(i18n.going, { count: item.members_count }));
        }
        const meta = card.querySelector('[data-lm-card-meta]');
        meta.textContent = bits.join(' · ');
        meta.setAttribute('dir', 'auto');
        const link = card.querySelector('[data-lm-card-link]');
        link.textContent = i18n.viewEvent;
        link.href = item.url || (item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events');
        requestAnimationFrame(() => card.classList.add('is-open'));
    };

    const highlightList = (eventId) => {
        listEl?.querySelectorAll('[data-lm-list-event]').forEach((row) => {
            const on = String(row.dataset.lmListEvent) === String(eventId);
            row.classList.toggle('is-active', on);
            if (on) row.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
        cityPanel?.querySelectorAll('[data-city-event-id]').forEach((btn) => {
            btn.classList.toggle('is-active', String(btn.dataset.cityEventId) === String(eventId));
        });
    };

    const renderList = (events) => {
        if (!listEl) return;
        listEvents = (events || []).filter((e) => e && e.id);
        if (!listEvents.length) {
            listEl.innerHTML = `<p class="lm-list__empty">${escapeHtml(i18n.listEmpty)}</p>`;
            return;
        }
        listEl.innerHTML = listEvents
            .map((item) => {
                const theme = themeFor(item.category_name);
                const approx =
                    item.location_precision &&
                    item.location_precision !== 'exact' &&
                    !item.has_exact_coordinates;
                const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
                const media = imageUrl
                    ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" class="lm-list__img">`
                    : `<span class="lm-list__fallback" style="--s:${theme.soft}">${theme.emoji}</span>`;
                const when = item.starts_at
                    ? new Date(item.starts_at).toLocaleString(undefined, {
                          month: 'short',
                          day: 'numeric',
                          hour: 'numeric',
                          minute: '2-digit',
                      })
                    : '';
                const metaBits = [
                    item.category_name,
                    when,
                    approx ? i18n.approximateCityLocation : item.venue_label,
                ].filter(Boolean);
                return `<button type="button" class="lm-list__card" data-lm-list-event="${escapeHtml(
                    String(item.id)
                )}">
                  <span class="lm-list__media">${media}</span>
                  <span class="lm-list__body">
                    <span class="lm-list__cat" style="color:${theme.color}">${escapeHtml(
                        item.category_name || ''
                    )}</span>
                    <strong dir="auto">${escapeHtml(item.title || '')}</strong>
                    <em dir="auto">${escapeHtml(metaBits.join(' · '))}</em>
                  </span>
                </button>`;
            })
            .join('');
        listEl.querySelectorAll('[data-lm-list-event]').forEach((btn) => {
            const img = btn.querySelector('img');
            if (img) {
                img.addEventListener(
                    'error',
                    () => {
                        const theme = themeFor(
                            listEvents.find((e) => String(e.id) === btn.dataset.lmListEvent)
                                ?.category_name
                        );
                        img.replaceWith(
                            Object.assign(document.createElement('span'), {
                                className: 'lm-list__fallback',
                                textContent: theme.emoji,
                            })
                        );
                    },
                    { once: true }
                );
            }
            btn.addEventListener('click', () => {
                const item = listEvents.find((e) => String(e.id) === btn.dataset.lmListEvent);
                if (item) selectEvent(item);
            });
        });
        if (selectedEventId) highlightList(selectedEventId);
    };

    const hideCityPanel = () => {
        if (!cityPanel) return;
        cityPanel.hidden = true;
        cityPanel.innerHTML = '';
        activeCityId = null;
        activeCityEvents = [];
        cityEventCursor = 0;
    };

    const renderCityPanel = (cityName, events, cityId) => {
        if (!cityPanel) return;
        activeCityId = cityId;
        activeCityEvents = events || [];
        cityEventCursor = 0;
        const rows = activeCityEvents
            .map((ev) => {
                const theme = themeFor(ev.category_name);
                const approx = ev.location_precision !== 'exact' && !ev.has_exact_coordinates;
                const imageUrl = typeof ev.image_url === 'string' ? ev.image_url.trim() : '';
                const thumb = imageUrl
                    ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy">`
                    : `<span>${theme.emoji}</span>`;
                return `<button type="button" class="lm-city-panel__row" data-city-event-id="${escapeHtml(
                    String(ev.id)
                )}">
                    <span class="lm-city-panel__thumb">${thumb}</span>
                    <span class="lm-city-panel__body">
                      <strong dir="auto">${escapeHtml(ev.title || '')}</strong>
                      <em dir="auto">${escapeHtml(
                          [ev.category_name, approx ? i18n.approximateCityLocation : ev.venue_label]
                              .filter(Boolean)
                              .join(' · ')
                      )}</em>
                    </span>
                  </button>`;
            })
            .join('');
        cityPanel.innerHTML = `
          <div class="lm-city-panel__head">
            <div>
              <p>${escapeHtml(i18n.eventsInLabel)}</p>
              <strong dir="auto">${escapeHtml(cityName || '')}</strong>
              <span>${escapeHtml(String(activeCityEvents.length))} · ${escapeHtml(
                  i18n.approximateCityLocations
              )}</span>
            </div>
            <button type="button" data-lm-city-close aria-label="${escapeHtml(i18n.closeCity)}">✕</button>
          </div>
          <div class="lm-city-panel__list fancy-scroll">${
              rows || `<p class="lm-city-panel__empty">${escapeHtml(i18n.noEligibleEvents)}</p>`
          }</div>`;
        cityPanel.hidden = false;
        cityPanel.querySelector('[data-lm-city-close]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            hideCityPanel();
            closeCard();
            setListHeading(i18n.listHeading);
            if (payloadCache) paintData(payloadCache);
        });
        cityPanel.querySelectorAll('[data-city-event-id]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const ev = activeCityEvents.find((x) => String(x.id) === btn.dataset.cityEventId);
                if (ev) selectEvent(ev);
            });
        });
    };

    const stopTour = (updateButton = true) => {
        touring = false;
        if (tourTimer) clearTimeout(tourTimer);
        if (updateButton) {
            tourBtn?.setAttribute('aria-pressed', 'false');
            if (tourBtn) tourBtn.textContent = '▶';
        }
    };

    const selectEvent = async (item, el = null) => {
        stopTour(false);
        selectedEventId = item.id;
        if (selectedEl) {
            selectedEl.classList.remove('is-selected', 'is-float', 'is-pulse');
            if (selectedEl.classList.contains('lm-marker')) {
                selectedEl.innerHTML = markerSvg(themeFor(selectedEl.dataset.cat));
            }
        }
        const theme = themeFor(item.category_name);
        const pin = el || pinByEventId.get(String(item.id)) || null;
        selectedEl = pin;
        if (pin?.classList.contains('lm-marker')) {
            pin.dataset.cat = item.category_name || '';
            pin.classList.add('is-selected', 'is-float');
            pin.innerHTML = markerSvg(theme, true);
        }
        openEventCard(item, theme);
        highlightList(item.id);
        const exact =
            item.location_precision === 'exact' || item.has_exact_coordinates === true;
        setFocus(item.title || '', exact ? i18n.exactVenue : i18n.approximateCityLocation);
        if (exact && item.latitude != null && item.longitude != null) {
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: Math.max(map.getZoom(), compact ? 11.8 : 12.6),
                    pitch: prefersReducedMotion() ? 0 : 52,
                    bearing: (map.getBearing() + 14) % 360,
                },
                1500
            );
            placeCard(map.project([item.longitude, item.latitude]));
        } else if (activeCityId && payloadCache) {
            const city = (payloadCache.city_aggregates || []).find(
                (c) => String(c.city_id) === String(activeCityId)
            );
            if (city?.latitude != null) placeCard(map.project([city.longitude, city.latitude]));
        }
        const idx = activeCityEvents.findIndex((e) => String(e.id) === String(item.id));
        if (idx >= 0) cityEventCursor = idx;
    };

    const enterCity = async (item, el) => {
        stopTour(false);
        if (item.type === 'city_cluster') {
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: Math.min(11.4, Number(item.expansion_zoom) || 10.4),
                    pitch: prefersReducedMotion() ? 0 : 46,
                    bearing: -10,
                },
                1400
            );
            try {
                paintData(await loadPayload(map.getBounds(), map.getZoom()));
            } catch {
                /* keep */
            }
            return;
        }
        if (selectedEl) selectedEl.classList.remove('is-selected', 'is-pulse');
        selectedEl = el || null;
        el?.classList.add('is-selected', 'is-pulse');
        const cityName = aggregateDisplayName(item, i18n);
        setFocus(cityName, i18n.livingCitySub);
        setStatus(fillTemplate(i18n.loadingCityEvents, { city: cityName }));
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: compact ? 10.8 : 11.4,
                pitch: prefersReducedMotion() ? 0 : 50,
                bearing: -12,
            },
            1600
        );
        try {
            const payload = await fetchCityEvents(item.city_id);
            const events = payload.events || [];
            const name = payload.city?.name || cityName;
            renderCityPanel(name, events, item.city_id);
            setListHeading(fillTemplate(root.dataset.i18nEventsInCity || ':city', { city: name }));
            // Prefer localized template from data attribute if present
            const headingTpl = root.dataset.i18nEventsInCity;
            setListHeading(
                headingTpl ? fillTemplate(headingTpl, { city: name }) : `${i18n.eventsInLabel} ${name}`
            );
            renderList(events);
            setStatus(`${events.length}`);
            if (events[0]) await selectEvent(events[0]);
            else closeCard();
        } catch (e) {
            console.warn(e);
            setStatus(i18n.cityEventsFailed);
        }
    };

    const createPinEl = (item, theme) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = 'lm-marker';
        el.innerHTML = markerSvg(theme);
        el.title = item.title || '';
        el.dataset.eventId = String(item.id || '');
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectEvent(item, el);
        });
        return el;
    };

    const createAggEl = (item) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className =
            item.type === 'city_cluster' ? 'lm-collection lm-collection--cluster' : 'lm-collection';
        const name = aggregateDisplayName(item, i18n);
        const count = item.count ?? item.city_count ?? 0;
        el.innerHTML = `
          <span class="lm-collection__name" dir="auto">${escapeHtml(name)}</span>
          <span class="lm-collection__meta" dir="ltr"><strong>${escapeHtml(String(count))}</strong> ${escapeHtml(
              i18n.eventsWord
          )}</span>
          <span class="lm-collection__cta">${escapeHtml(i18n.open)}</span>`;
        el.title = item.label || name;
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            enterCity(item, el);
        });
        return el;
    };

    const paintData = (payload) => {
        clearMarkers();
        payloadCache = payload;
        const exact = [];
        const approx = [];
        (payload.markers || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact') exact.push(item);
            else approx.push(item);
        });
        (payload.clusters || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact' || Number(item.count) === 1) {
                exact.push({ ...item, location_precision: item.location_precision || 'exact' });
            }
        });
        eventTourQueue = exact.filter((e) => e.id && e.latitude != null);
        cityTour = (payload.city_aggregates || [])
            .filter(
                (c) =>
                    c.type !== 'city_cluster' &&
                    c.city_name &&
                    c.city_id &&
                    c.latitude != null &&
                    Number(c.count) > 0
            )
            .sort((a, b) => Number(b.count) - Number(a.count));

        exact.forEach((item) => {
            const theme = themeFor(item.category_name);
            const el = createPinEl(item, theme);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
            if (item.id) pinByEventId.set(String(item.id), el);
        });

        const collections = (payload.city_aggregates || []).filter(
            (c) => c.latitude != null && c.longitude != null
        );
        const named = collections.filter((c) => c.type !== 'city_cluster').slice(0, compact ? 10 : 14);
        const clusters = collections.filter((c) => c.type === 'city_cluster').slice(0, 3);
        [...named, ...clusters].forEach((item) => {
            const el = createAggEl(item);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'center' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
        });

        renderList([...exact, ...approx]);
        setListHeading(i18n.listHeading);
        tourMode =
            eventTourQueue.length >= 2 ? 'exact' : cityTour.length >= 1 ? 'city-events' : 'ambient';
        setStatus(
            tourMode === 'exact'
                ? i18n.readyEventTour
                : tourMode === 'city-events'
                  ? i18n.readyDiscoveryTour
                  : i18n.ready
        );
    };

    const runTourStep = async () => {
        if (!touring || prefersReducedMotion() || document.hidden) return;
        if (tourMode === 'exact' && eventTourQueue.length >= 2) {
            const item = eventTourQueue[tourIndex % eventTourQueue.length];
            tourIndex += 1;
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: 12.4,
                    pitch: 54,
                    bearing: (map.getBearing() + 22) % 360,
                },
                2200
            );
            await selectEvent(item, pinByEventId.get(String(item.id)));
        } else if (tourMode === 'city-events' && cityTour.length >= 1) {
            const city = cityTour[tourIndex % cityTour.length];
            await animateCamera(
                map,
                {
                    center: [city.longitude, city.latitude],
                    zoom: 11.2,
                    pitch: 48,
                    bearing: -14 + (tourIndex % 3) * 8,
                },
                2000
            );
            let events = activeCityId === city.city_id ? activeCityEvents : [];
            if (!events.length) {
                try {
                    const payload = await fetchCityEvents(city.city_id);
                    events = payload.events || [];
                    renderCityPanel(payload.city?.name || city.city_name, events, city.city_id);
                    setListHeading(
                        fillTemplate(root.dataset.i18nEventsInCity || ':city', {
                            city: payload.city?.name || city.city_name,
                        })
                    );
                    renderList(events);
                } catch {
                    events = [];
                }
            }
            if (!events.length) {
                tourIndex += 1;
                cityEventCursor = 0;
            } else {
                const ev = events[cityEventCursor % events.length];
                cityEventCursor += 1;
                await selectEvent(ev);
                if (cityEventCursor >= Math.min(events.length, 3)) {
                    cityEventCursor = 0;
                    tourIndex += 1;
                    activeCityId = null;
                    activeCityEvents = [];
                }
            }
        } else {
            stopTour();
            return;
        }
        if (!touring) return;
        tourTimer = setTimeout(runTourStep, 3200);
    };

    const startTour = () => {
        if (prefersReducedMotion()) {
            setStatus(i18n.reducedMotionTour);
            return;
        }
        if (tourMode === 'ambient') {
            setStatus(i18n.notEnoughForTour);
            return;
        }
        touring = true;
        tourBtn?.setAttribute('aria-pressed', 'true');
        if (tourBtn) tourBtn.textContent = '❚❚';
        runTourStep();
    };

    const stepManual = async (dir) => {
        stopTour(true);
        if (activeCityEvents.length) {
            cityEventCursor =
                (cityEventCursor + dir + activeCityEvents.length) % activeCityEvents.length;
            await selectEvent(activeCityEvents[cityEventCursor]);
            return;
        }
        const pool = listEvents.length ? listEvents : eventTourQueue;
        if (!pool.length) return;
        const idx = Math.max(
            0,
            pool.findIndex((e) => String(e.id) === String(selectedEventId))
        );
        const next = pool[(idx + dir + pool.length) % pool.length];
        await selectEvent(next, pinByEventId.get(String(next.id)));
    };

    map.on('load', async () => {
        try {
            map.setFog({
                color: 'rgb(214, 228, 255)',
                'high-color': 'rgb(186, 210, 255)',
                'horizon-blend': 0.1,
                'space-color': 'rgb(245, 240, 255)',
                'star-intensity': 0,
            });
        } catch {
            /* optional */
        }

        const reshapeIfRtlReady = () => {
            try {
                if (window.maplibregl.getRTLTextPluginStatus?.() !== 'loaded') return false;
                map.setStyle(map.getStyle());
                return true;
            } catch {
                return false;
            }
        };
        if (!reshapeIfRtlReady()) {
            const started = Date.now();
            const pollRtl = () => {
                if (reshapeIfRtlReady() || Date.now() - started > 20000) return;
                setTimeout(pollRtl, 250);
            };
            setTimeout(pollRtl, 250);
        }

        map.on('style.load', () => {
            try {
                ensureOriginLayers();
            } catch (e) {
                console.warn('origin layers', e);
            }
        });
        try {
            ensureOriginLayers();
        } catch (e) {
            console.warn('origin layers', e);
        }

        setStatus(i18n.composing);
        let payload;
        try {
            if (origin.hasOrigin) {
                setFocus(origin.label || i18n.featuredRegion, `${origin.km} km`);
                await new Promise((resolve) => {
                    map.fitBounds(radiusLngLatBounds(origin.lat, origin.lng, origin.km), {
                        padding: compact ? 36 : 48,
                        maxZoom: zoomForRadius(origin.km),
                        duration: prefersReducedMotion() ? 0 : 900,
                        pitch: prefersReducedMotion() ? 0 : 42,
                        bearing: prefersReducedMotion() ? 0 : -6,
                    });
                    map.once('moveend', () => resolve());
                    setTimeout(resolve, 1100);
                });
                payload = await loadPayload(map.getBounds(), map.getZoom());
            } else {
                setFocus(i18n.featuredRegion, i18n.featuredRegionSub);
                const egypt = overviewBounds();
                payload = await loadPayload(egypt, 6);
                if (!compact) {
                    const focus = computeFeaturedFocus(payload);
                    await animateCamera(
                        map,
                        {
                            center: focus.center,
                            zoom: focus.zoom,
                            pitch: focus.pitch,
                            bearing: focus.bearing,
                        },
                        prefersReducedMotion() ? 0 : 1400
                    );
                    try {
                        payload = await loadPayload(map.getBounds(), map.getZoom());
                    } catch {
                        /* keep first payload */
                    }
                } else {
                    await new Promise((resolve) => {
                        map.fitBounds(
                            [
                                [25.0, 22.2],
                                [34.9, 31.65],
                            ],
                            {
                                padding: 28,
                                maxZoom: 7,
                                duration: prefersReducedMotion() ? 0 : 800,
                            }
                        );
                        map.once('moveend', () => resolve());
                        setTimeout(resolve, 1000);
                    });
                    try {
                        payload = await loadPayload(map.getBounds(), map.getZoom());
                    } catch {
                        /* keep */
                    }
                }
            }
        } catch (e) {
            console.warn(e);
            setStatus(i18n.discoveryUnavailable);
            return;
        }
        paintData(payload);
    });

    map.on('movestart', (e) => {
        if (e.originalEvent) stopTour();
    });
    map.on('click', () => closeCard());
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopTour();
    });
    if (stage) {
        const io = new IntersectionObserver((entries) => {
            if (!entries.some((en) => en.isIntersecting)) stopTour();
        });
        io.observe(stage);
    }

    tourBtn?.addEventListener('click', () => (touring ? stopTour() : startTour()));
    prevBtn?.addEventListener('click', () => stepManual(-1));
    nextBtn?.addEventListener('click', () => stepManual(1));
    root.querySelector('[data-lm-zoom-in]')?.addEventListener('click', () =>
        map.zoomIn({ duration: 400 })
    );
    root.querySelector('[data-lm-zoom-out]')?.addEventListener('click', () =>
        map.zoomOut({ duration: 400 })
    );
    root.querySelector('[data-lm-reset]')?.addEventListener('click', async () => {
        stopTour();
        closeCard();
        hideCityPanel();
        if (origin.hasOrigin) {
            setFocus(origin.label || i18n.featuredRegion, `${origin.km} km`);
            await new Promise((resolve) => {
                map.fitBounds(radiusLngLatBounds(origin.lat, origin.lng, origin.km), {
                    padding: compact ? 36 : 48,
                    maxZoom: zoomForRadius(origin.km),
                    duration: prefersReducedMotion() ? 0 : 800,
                });
                map.once('moveend', () => resolve());
                setTimeout(resolve, 1000);
            });
            try {
                paintData(await loadPayload(map.getBounds(), map.getZoom()));
            } catch {
                if (payloadCache) paintData(payloadCache);
            }
            return;
        }
        if (!payloadCache) return;
        const focus = computeFeaturedFocus(payloadCache);
        setFocus(i18n.featuredRegion, i18n.featuredRegionSub);
        await animateCamera(map, {
            center: focus.center,
            zoom: focus.zoom,
            pitch: focus.pitch,
            bearing: focus.bearing,
        });
        paintData(payloadCache);
    });

    const api = {
        map,
        destroy() {
            stopTour();
            clearMarkers();
            map.remove();
            root.dataset.ready = '0';
        },
        getRtlStatus: () => window.maplibregl?.getRTLTextPluginStatus?.() || 'unknown',
        getMode: () => mode,
        getOrigin: () => ({ ...origin }),
    };
    root._livingMap = api;
    return api;
}
