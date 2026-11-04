/**
 * Living Map V3.4/V3.5 — reusable MapLibre factory (Home / Discover / Public / Preview).
 * V3.5 premium skin activates only when root[data-variant="v35"] (isolated preview).
 * Home/public: living_map.replace_home_mini_map (V3.4 chrome)
 * Discover: living_map.replace_discover_map (mode=discover, map-only + Search This Area)
 */
import { loadFunnityStyle } from './funnity-style.js';
import { loadFunnityStyleV35 } from './funnity-style-v35.js';
import { fillTemplate, readI18n } from './i18n.js';
import {
    requestDevicePosition,
    reverseGeocodeLabel,
    saveDiscoveryState,
} from '../discovery-location.js';

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

function animateCamera(map, to, duration = 2200, onStart) {
    return new Promise((resolve) => {
        if (!map) return resolve();
        if (typeof onStart === 'function') onStart(duration);
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

function categoryIconPath(theme, ink = null) {
    const c = ink || theme?.color || '#7138F5';
    const key = String(theme?.emoji || '');
    // Compact monochrome icon paths (viewBox local 0..24).
    if (key.includes('⚽') || key.includes('🏀') || key.includes('🎾') || /sport/i.test(theme?.color || '')) {
        return `<circle cx="12" cy="12" r="7.2" fill="none" stroke="${c}" stroke-width="2"/><path d="M12 5v14M5 12h14" stroke="${c}" stroke-width="1.4"/>`;
    }
    if (key.includes('🎮')) {
        return `<rect x="4" y="8" width="16" height="10" rx="3" fill="none" stroke="${c}" stroke-width="2"/><circle cx="9" cy="13" r="1.2" fill="${c}"/><circle cx="15" cy="13" r="1.2" fill="${c}"/>`;
    }
    if (key.includes('🎵')) {
        return `<path d="M10 6v10.5a2.5 2.5 0 1 1-1.6-2.3V8.2L18 6v8.7a2.5 2.5 0 1 1-1.6-2.3V7.1L10 6z" fill="${c}"/>`;
    }
    if (key.includes('☕') || key.includes('🍕')) {
        return `<path d="M7 9h9a3 3 0 0 1 0 6H7V9z" fill="none" stroke="${c}" stroke-width="2"/><path d="M7 15c0 2.5 2.2 4 5 4s5-1.5 5-4" stroke="${c}" stroke-width="1.6" fill="none"/>`;
    }
    if (key.includes('🌳') || key.includes('🏃')) {
        return `<path d="M12 19V11M8 19h8" stroke="${c}" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="8" r="4.5" fill="${c}"/>`;
    }
    if (key.includes('🎬')) {
        return `<rect x="5" y="7" width="14" height="11" rx="2" fill="none" stroke="${c}" stroke-width="2"/><path d="M10 10l5 2.5-5 2.5V10z" fill="${c}"/>`;
    }
    return `<path d="M12 6.2l1.6 3.4 3.7.4-2.8 2.5.8 3.6L12 14.5 8.7 16.1l.8-3.6-2.8-2.5 3.7-.4L12 6.2z" fill="${c}"/>`;
}

/** V3.5.3 — premium teardrop (category icon OR count). Never a flat disc. */
function premiumTeardropSvg({
    color = '#7138F5',
    selected = false,
    iconHtml = '',
    count = null,
}) {
    const gid = uid('mg');
    const glow = selected ? 0.95 : 0.62;
    const tip = selected
        ? 'M28 3C15.3 3 5 13.2 5 25.4c0 16.2 18.2 36.8 21.6 40.6a1.7 1.7 0 0 0 2.8 0C32.8 62.2 51 41.6 51 25.4 51 13.2 40.7 3 28 3z'
        : 'M28 3.5C15.8 3.5 6 13.4 6 25.2c0 15.4 17.4 35.2 20.6 38.8a1.6 1.6 0 0 0 2.6 0C32.4 60.4 50 40.6 50 25.2 50 13.4 40.2 3.5 28 3.5z';
    // Count digits are painted via HTML overlay (.lm-marker__count) for crisp rendering.
    const face =
        count != null
            ? `<circle cx="28" cy="23" r="12.2" fill="#fff"/>`
            : `<circle cx="28" cy="23" r="12.2" fill="#fff"/><g transform="translate(16 11)">${iconHtml}</g>`;
    return `<svg viewBox="0 0 56 72" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <defs>
        <linearGradient id="${gid}g" x1="18%" y1="8%" x2="82%" y2="92%">
          <stop offset="0%" stop-color="#fff" stop-opacity=".55"/>
          <stop offset="32%" stop-color="${color}" stop-opacity="1"/>
          <stop offset="100%" stop-color="${color}" stop-opacity="1"/>
        </linearGradient>
        <radialGradient id="${gid}h" cx="34%" cy="22%" r="55%">
          <stop offset="0%" stop-color="#fff" stop-opacity=".85"/>
          <stop offset="55%" stop-color="#fff" stop-opacity="0"/>
        </radialGradient>
        <filter id="${gid}b" x="-60%" y="-40%" width="220%" height="200%">
          <feDropShadow dx="0" dy="10" stdDeviation="7.5" flood-color="${color}" flood-opacity="${glow}"/>
          <feDropShadow dx="0" dy="3" stdDeviation="2" flood-color="#1f1840" flood-opacity=".22"/>
        </filter>
      </defs>
      <ellipse cx="28" cy="66" rx="${selected ? 16 : 13}" ry="${selected ? 5 : 4}" fill="${color}" opacity=".32"/>
      <path filter="url(#${gid}b)" d="${tip}" fill="url(#${gid}g)" stroke="#fff" stroke-width="2.9"/>
      <path d="${tip}" fill="url(#${gid}h)" opacity=".9"/>
      ${face}
    </svg>`;
}

/** Inner visual wrapper — CSS may transform this; MapLibre owns the marker root transform. */
function wrapMarkerVisual(innerHtml) {
    return `<span class="lm-marker__visual">${innerHtml}</span>`;
}

/** Parse trailing city from venue labels like "Community Sports Club · Port Said". */
function cityNameFromVenue(venue) {
    const raw = String(venue || '').trim();
    if (!raw) return '';
    const parts = raw.split('·').map((s) => s.trim()).filter(Boolean);
    return parts.length >= 2 ? parts[parts.length - 1] : '';
}

/**
 * Verified Egypt city-table centroids (preview UX only — never invent venue coords).
 * Used when discovery markerPayload omits city_id on approximate points.
 */
const VERIFIED_CITY_CENTROIDS = Object.freeze({
    '31.2653,32.3019': { id: 11, name: 'Port Said' },
    '31.4165,31.8133': { id: 14, name: 'Damietta' },
    '31.0409,31.3785': { id: 8, name: 'Mansoura' },
});

function verifiedCityAtCentroid(lat, lng) {
    const key = `${Number(lat).toFixed(4)},${Number(lng).toFixed(4)}`;
    return VERIFIED_CITY_CENTROIDS[key] || null;
}

function formatEventWhen(item) {
    if (!item?.starts_at) return '';
    try {
        return new Date(item.starts_at).toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
}

function markerSvg(theme, selected = false, premium = false) {
    const gid = uid('mg');
    const glow = selected ? 0.65 : 0.35;
    if (!premium) {
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
    return premiumTeardropSvg({
        color: theme.color,
        selected,
        iconHtml: categoryIconPath(theme),
    });
}

function isEventLive(item) {
    if (!item?.starts_at) return false;
    const start = new Date(item.starts_at).getTime();
    const now = Date.now();
    if (!Number.isFinite(start) || start > now) return false;
    if (item.ends_at) {
        const end = new Date(item.ends_at).getTime();
        return Number.isFinite(end) && end > now;
    }
    // Without ends_at: only treat as live within 3h after start.
    return now - start < 3 * 60 * 60 * 1000;
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
    const rawKm = root?.dataset?.distanceKm;
    const km = rawKm === undefined || rawKm === '' ? 25 : Number(rawKm);
    const mode = root?.dataset?.discoveryMode || 'nationwide';
    const cityIdRaw = root?.dataset?.cityId;
    const cityId =
        cityIdRaw !== undefined && cityIdRaw !== '' && Number.isFinite(Number(cityIdRaw))
            ? Number(cityIdRaw)
            : null;
    const hasOrigin =
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        mode !== 'nationwide' &&
        Math.abs(lat) <= 90 &&
        Math.abs(lng) <= 180;
    return {
        lat: Number.isFinite(lat) ? lat : null,
        lng: Number.isFinite(lng) ? lng : null,
        km: Number.isFinite(km) ? km : 25,
        mode,
        hasOrigin,
        label: root?.dataset?.locationLabel || '',
        cityId,
    };
}

function zoomForRadius(km) {
    const n = Number(km);
    if (!Number.isFinite(n) || n <= 0) return 6.8;
    if (n <= 5) return 12.2;
    if (n <= 10) return 11.4;
    if (n <= 25) return 10.6;
    if (n <= 50) return 9.8;
    if (n <= 100) return 8.8;
    return 7.6;
}

/** Honest haversine km between origin and event coordinates. */
function distanceKmBetween(lat1, lng1, lat2, lng2) {
    if (![lat1, lng1, lat2, lng2].every((n) => Number.isFinite(Number(n)))) return null;
    const toRad = (d) => (Number(d) * Math.PI) / 180;
    const R = 6371;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function formatDistanceLabel(km, i18n) {
    if (km == null || !Number.isFinite(km)) return '';
    // Never surface negative or near-zero noise from bad/centroid coords.
    if (km < 0.15) return '';
    const rounded = km < 10 ? km.toFixed(1) : String(Math.round(km));
    return fillTemplate(i18n.distanceApprox || '~:km km', { km: rounded });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
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

function readDiscoverFilters() {
    const q = document.querySelector('#q')?.value?.trim() || '';
    const categoryId = document.querySelector('#category_id')?.value || '';
    return { q, categoryId };
}

/**
 * @param {maplibregl.LngLatBoundsLike|{getNorth:Function}} bounds
 * @param {number} zoom
 * @param {ReturnType<typeof readDiscoveryOrigin>} origin
 * @param {{ includeApproximate?: boolean, viewportOnly?: boolean, signal?: AbortSignal }} [opts]
 */
async function fetchMapPayload(bounds, zoom, origin, opts = {}) {
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
    const includeApproximate = opts.includeApproximate !== false;

    const params = new URLSearchParams({
        north: String(north),
        south: String(south),
        east: String(east),
        west: String(west),
        zoom: String(Math.max(5, Math.round(zoom || 6))),
        include_approximate: includeApproximate ? '1' : '0',
        limit: origin?.hasOrigin ? '60' : '100',
    });
    // Viewport search (Search This Area) keeps active filters but does not rewrite saved origin.
    if (!opts.viewportOnly && origin?.hasOrigin) {
        params.set('discovery_mode', origin.mode === 'manual' ? 'manual' : 'device');
        params.set('latitude', String(origin.lat));
        params.set('longitude', String(origin.lng));
        params.set('distance_km', String(origin.km));
    } else if (!opts.viewportOnly) {
        params.set('discovery_mode', 'nationwide');
    }
    const filters = readDiscoverFilters();
    if (filters.q) params.set('q', filters.q);
    if (filters.categoryId) params.set('category_id', filters.categoryId);

    const res = await fetch(`/api/discovery/map?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
        signal: opts.signal,
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
    const isV35 = String(root.dataset.variant || '').toLowerCase() === 'v35';
    // V3.5.4: lower pitch so Egypt land/events dominate (less empty Mediterranean).
    const cinematicPitch = prefersReducedMotion() ? 0 : isV35 ? 36 : 44;
    let origin = readDiscoveryOrigin(root);
    const mapEl = root.querySelector('[data-lm-map]');
    const stage = root.querySelector('[data-lm-stage]');
    const listEl = root.querySelector('[data-lm-list]');
    const listHeading = root.querySelector('[data-lm-list-heading]');
    const tourBtn = root.querySelector('[data-lm-tour]');
    const prevBtn = root.querySelector('[data-lm-prev]');
    const nextBtn = root.querySelector('[data-lm-next]');
    const cityPanel = root.querySelector('[data-lm-city-panel]');
    const statusEl = root.querySelector('[data-lm-status]');
    const radiusSelect = root.querySelector('[data-lm-radius]');
    const radiusWrap = root.querySelector('[data-lm-radius-wrap]');
    const radiusChips = () => root.querySelectorAll('[data-lm-radius-chip]');
    const radiusSlider = root.querySelector('[data-lm-radius-slider]');
    const useLocationBtn = root.querySelector('[data-lm-use-location]');
    const railNextBtn = root.querySelector('[data-lm-rail-next]');
    const embedded = mode === 'home' || mode === 'public';
    const isDiscover = mode === 'discover';
    let viewMode = 'map'; // map | list | nearby
    let radiusOptions = [];
    try {
        radiusOptions = JSON.parse(root.dataset.radiusOptions || '[]');
        if (!Array.isArray(radiusOptions)) radiusOptions = [];
        radiusOptions = radiusOptions.map((n) => Number(n)).filter((n) => Number.isFinite(n));
    } catch {
        radiusOptions = [5, 10, 25, 50, 100, 0];
    }
    const searchAreaBtn =
        root.closest('[data-discover-living-map-host]')?.querySelector('[data-discover-map-search-area]') ||
        document.querySelector('[data-discover-map-search-area]');
    let programmaticMoveUntil = 0;
    let searchAreaAbort = null;
    let searchAreaSeq = 0;

    const markProgrammaticMove = (ms = 1400) => {
        programmaticMoveUntil = Date.now() + ms;
        if (searchAreaBtn) searchAreaBtn.hidden = true;
    };

    const highlightDiscoverCard = (eventId) => {
        if (!isDiscover) return;
        document.querySelectorAll('[data-discover-result-card]').forEach((el) => {
            el.classList.toggle('is-active', String(el.dataset.eventId) === String(eventId));
        });
        const active = document.querySelector(
            `[data-discover-result-card][data-event-id="${eventId}"]`
        );
        active?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    };

    const loadPayload = (bounds, zoom, opts = {}) =>
        fetchMapPayload(bounds, zoom, origin, opts);

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
        style = isV35
            ? await loadFunnityStyleV35(root.dataset.styleUrl)
            : await loadFunnityStyle(root.dataset.styleUrl);
    } catch (e) {
        console.warn(e);
        style = root.dataset.styleUrl;
        setStatus(i18n.styleFallback);
    }

    const initialCenter = origin.hasOrigin ? [origin.lng, origin.lat] : [31.24, 30.05];
    const initialZoom = origin.hasOrigin
        ? zoomForRadius(origin.km)
        : isV35
          ? 8.2
          : 7.4;
    const map = new window.maplibregl.Map({
        container: mapEl,
        style,
        center: initialCenter,
        zoom: initialZoom,
        pitch: cinematicPitch,
        bearing: prefersReducedMotion() ? 0 : isV35 ? -14 : -8,
        maxBounds: EGYPT_BOUNDS,
        attributionControl: { compact: true },
        cooperativeGestures: true,
    });
    // Custom HUD owns zoom/recenter — avoid duplicate MapLibre nav chrome.

    const ORIGIN_SRC = 'lm-home-origin';
    const clearOriginLayers = () => {
        ['lm-home-origin-dot', 'lm-home-radius-line', 'lm-home-radius-fill'].forEach((id) => {
            if (map.getLayer(id)) map.removeLayer(id);
        });
        if (map.getSource(ORIGIN_SRC)) map.removeSource(ORIGIN_SRC);
    };

    const ensureOriginLayers = () => {
        clearOriginLayers();
        if (!origin.hasOrigin) return;
        const features = [
            {
                type: 'Feature',
                properties: { kind: 'origin' },
                geometry: {
                    type: 'Point',
                    coordinates: [origin.lng, origin.lat],
                },
            },
        ];
        if (origin.km > 0) {
            features.unshift(circlePolygon(origin.lng, origin.lat, origin.km));
        }
        map.addSource(ORIGIN_SRC, {
            type: 'geojson',
            data: { type: 'FeatureCollection', features },
        });
        if (origin.km > 0) {
            map.addLayer({
                id: 'lm-home-radius-fill',
                type: 'fill',
                source: ORIGIN_SRC,
                filter: ['==', ['geometry-type'], 'Polygon'],
                paint: { 'fill-color': '#7138F5', 'fill-opacity': 0.06 },
            });
            map.addLayer({
                id: 'lm-home-radius-line',
                type: 'line',
                source: ORIGIN_SRC,
                filter: ['==', ['geometry-type'], 'Polygon'],
                paint: {
                    'line-color': '#7138F5',
                    'line-width': 1.5,
                    'line-opacity': 0.45,
                },
            });
        }
        map.addLayer({
            id: 'lm-home-origin-dot',
            type: 'circle',
            source: ORIGIN_SRC,
            filter: ['==', ['get', 'kind'], 'origin'],
            paint: {
                'circle-radius': 7,
                'circle-color': '#2563EB',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff',
            },
        });
    };

    const syncRadiusUi = () => {
        const show = root.dataset.showRadius === '1' && origin.hasOrigin;
        if (radiusWrap) radiusWrap.hidden = !show;
        if (radiusSelect) {
            radiusSelect.disabled = !show;
            if (show && String(radiusSelect.value) !== String(origin.km)) {
                radiusSelect.value = String(origin.km);
            }
        }
        const idx = Math.max(0, radiusOptions.findIndex((v) => Number(v) === Number(origin.km)));
        if (radiusSlider) {
            radiusSlider.disabled = !show;
            if (show && String(radiusSlider.value) !== String(idx >= 0 ? idx : 0)) {
                radiusSlider.value = String(idx >= 0 ? idx : 0);
            }
        }
        root.querySelectorAll('.lm-slider__labels span').forEach((span, i) => {
            span.classList.toggle('is-active', show && i === idx);
        });
        const distReadout = root.querySelector('[data-lm-distance-readout]');
        if (distReadout) {
            distReadout.textContent = show
                ? origin.km > 0
                    ? fillTemplate(i18n.withinKm || 'within :km km', { km: origin.km })
                    : i18n.allDistances || 'All distances'
                : '';
            distReadout.hidden = !show;
        }
        radiusChips().forEach((chip) => {
            chip.disabled = !show;
            chip.setAttribute(
                'aria-pressed',
                show && String(chip.value) === String(origin.km) ? 'true' : 'false'
            );
        });
    };

    const focusSubForOrigin = () => {
        const city = origin.label || i18n.featuredRegion;
        if (isV35 && origin.hasOrigin) {
            return fillTemplate(
                i18n.eventsAround || 'Events around :city · Approximate locations',
                { city }
            );
        }
        return origin.km > 0
            ? fillTemplate(i18n.withinKm || 'within :km km', { km: origin.km })
            : root.dataset.i18nAllDistances || i18n.allDistances || 'All distances';
    };

    const setViewMode = (next) => {
        const allowed = ['map', 'list', 'nearby'];
        viewMode = allowed.includes(next) ? next : 'map';
        root.classList.remove('is-view-map', 'is-view-list', 'is-view-nearby');
        root.classList.add(`is-view-${viewMode}`);
        root.querySelectorAll('[data-lm-view]').forEach((btn) => {
            btn.setAttribute('aria-pressed', btn.dataset.lmView === viewMode ? 'true' : 'false');
        });
        if (viewMode !== 'list') {
            requestAnimationFrame(() => {
                try {
                    map.resize();
                } catch {
                    /* ignore */
                }
            });
        }
        if (viewMode === 'nearby' && origin.hasOrigin) {
            refreshFromOrigin({ animate: true }).catch((e) => console.warn(e));
        } else if (payloadCache) {
            paintData(payloadCache);
        } else if (listEvents.length) {
            renderList(listEvents);
        }
    };

    const eventDistanceKm = (item) => {
        if (!origin.hasOrigin) return null;
        if (item?.latitude == null || item?.longitude == null) return null;
        return distanceKmBetween(origin.lat, origin.lng, item.latitude, item.longitude);
    };

    const writeOriginDataset = () => {
        if (origin.hasOrigin) {
            root.dataset.lat = String(origin.lat);
            root.dataset.lng = String(origin.lng);
            root.dataset.discoveryMode = origin.mode;
        } else {
            delete root.dataset.lat;
            delete root.dataset.lng;
            root.dataset.discoveryMode = 'nationwide';
        }
        root.dataset.distanceKm = String(origin.km);
        if (origin.label) root.dataset.locationLabel = origin.label;
        if (origin.cityId != null) root.dataset.cityId = String(origin.cityId);
        else delete root.dataset.cityId;
        syncRadiusUi();
    };

    const markerObjs = [];
    const pinByEventId = new Map();
    const pinByCityId = new Map();
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
    let pendingAggregateFrame = false;
    let spotlightEvent = null;
    let spotlightMarkerEl = null;
    let spotlightDismissed = false;
    try {
        spotlightDismissed = sessionStorage.getItem('lm-v35-spotlight-dismissed') === '1';
    } catch {
        spotlightDismissed = false;
    }
    const spotlightEl = root.querySelector('[data-lm-spotlight]');

    const clearMarkers = () => {
        markerObjs.forEach((m) => m.remove());
        markerObjs.length = 0;
        pinByEventId.clear();
        pinByCityId.clear();
        selectedEl = null;
        spotlightMarkerEl = null;
    };

    const closeCard = () => {
        const card = root.querySelector('[data-lm-card]');
        if (!card) return;
        card.classList.remove('is-open');
        setTimeout(() => {
            card.hidden = true;
        }, 240);
    };

    const markerInStage = (el) => {
        if (!el || !stage) return false;
        const r = el.getBoundingClientRect();
        const s = stage.getBoundingClientRect();
        const tipX = r.left + r.width / 2;
        const tipY = r.bottom;
        return tipX >= s.left && tipX <= s.right && tipY >= s.top && tipY <= s.bottom;
    };

    const restoreAggVisual = (el, selected = false) => {
        if (!el?.classList.contains('lm-marker--agg')) return;
        const color = el.style.getPropertyValue('--lm-pin') || '#7138F5';
        const glow = el.style.getPropertyValue('--lm-glow') || 'rgba(113,56,245,.45)';
        const count = el.dataset.count || '';
        if (!count) return;
        el.innerHTML = wrapMarkerVisual(
            premiumTeardropSvg({ color, count, selected }) +
                `<span class="lm-marker__count" style="color:${color}">${escapeHtml(String(count))}</span>` +
                `<span class="lm-marker-halo" style="--lm-glow:${selected ? 'rgba(255,118,46,.55)' : glow}"></span>`
        );
    };

    const clearSpotlightMarker = () => {
        if (!spotlightMarkerEl) return;
        spotlightMarkerEl.classList.remove('is-spotlight');
        if (
            spotlightMarkerEl.classList.contains('lm-marker--agg') &&
            !spotlightMarkerEl.classList.contains('is-selected')
        ) {
            restoreAggVisual(spotlightMarkerEl, false);
        }
        spotlightMarkerEl = null;
    };

    const findAggElForEvent = (item) => {
        if (!item) return null;
        if (item.city_id != null && pinByCityId.has(String(item.city_id))) {
            return pinByCityId.get(String(item.city_id));
        }
        const verified = verifiedCityAtCentroid(item.latitude, item.longitude);
        if (verified && pinByCityId.has(String(verified.id))) {
            return pinByCityId.get(String(verified.id));
        }
        const cityFromVenue = cityNameFromVenue(item.venue_label);
        if (cityFromVenue) {
            for (const el of pinByCityId.values()) {
                const label = el.getAttribute('aria-label') || el.getAttribute('title') || '';
                if (label.toLowerCase().includes(cityFromVenue.toLowerCase())) return el;
            }
        }
        return null;
    };

    const highlightSpotlightMarker = (item) => {
        clearSpotlightMarker();
        if (!item || !isV35) return;
        const exact =
            item.location_precision === 'exact' || item.has_exact_coordinates === true;
        const el = exact
            ? pinByEventId.get(String(item.id)) || null
            : findAggElForEvent(item);
        // Never invent an on-screen marker when the associated pin is off-canvas.
        if (!el || !markerInStage(el)) return;
        spotlightMarkerEl = el;
        el.classList.add('is-spotlight');
        if (el.classList.contains('lm-marker--agg')) {
            restoreAggVisual(el, true);
        }
    };

    const hideSpotlight = ({ persist = false } = {}) => {
        if (!spotlightEl) return;
        spotlightEl.hidden = true;
        spotlightEvent = null;
        clearSpotlightMarker();
        if (persist) {
            spotlightDismissed = true;
            try {
                sessionStorage.setItem('lm-v35-spotlight-dismissed', '1');
            } catch {
                /* ignore */
            }
        }
    };

    const pickSpotlightEvent = (events) => {
        const rows = (events || []).filter((e) => e && e.id);
        if (!rows.length) return null;
        const focus = String(origin.label || '')
            .toLowerCase()
            .trim();
        const scored = rows.map((e) => {
            let score = 0;
            if (typeof e.image_url === 'string' && e.image_url.trim()) score += 5;
            const venue = String(e.venue_label || '').toLowerCase();
            if (focus && venue.includes(focus)) score += 4;
            if (e.location_precision === 'exact' || e.has_exact_coordinates) score += 2;
            // Prefer events whose aggregate/exact pin is currently on-canvas.
            const exact =
                e.location_precision === 'exact' || e.has_exact_coordinates === true;
            const pin = exact
                ? pinByEventId.get(String(e.id))
                : findAggElForEvent(e);
            if (pin && markerInStage(pin)) score += 6;
            const t = e.starts_at ? new Date(e.starts_at).getTime() : Number.POSITIVE_INFINITY;
            return { e, score, t: Number.isFinite(t) ? t : Number.POSITIVE_INFINITY };
        });
        scored.sort((a, b) => b.score - a.score || a.t - b.t);
        return scored[0]?.e || null;
    };

    const renderSpotlight = (item) => {
        if (!isV35 || !spotlightEl || !item || spotlightDismissed) {
            hideSpotlight();
            return;
        }
        const theme = themeFor(item.category_name);
        const media = spotlightEl.querySelector('[data-lm-spotlight-media]');
        const cat = spotlightEl.querySelector('[data-lm-spotlight-cat]');
        const title = spotlightEl.querySelector('[data-lm-spotlight-title]');
        const meta = spotlightEl.querySelector('[data-lm-spotlight-meta]');
        const place = spotlightEl.querySelector('[data-lm-spotlight-place]');
        const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
        if (media) {
            if (imageUrl) {
                media.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" decoding="async">`;
                media.querySelector('img')?.addEventListener(
                    'error',
                    () => {
                        media.innerHTML = `<div class="lm-spotlight__fallback" style="--s:${theme.soft}">${theme.emoji}</div>`;
                    },
                    { once: true }
                );
            } else {
                media.innerHTML = `<div class="lm-spotlight__fallback" style="--s:${theme.soft}">${theme.emoji}</div>`;
            }
        }
        if (cat) {
            cat.textContent = `${theme.emoji} ${item.category_name || ''}`.trim();
            cat.style.background = theme.soft;
            cat.style.color = theme.color;
        }
        if (title) title.textContent = item.title || '';
        const when = formatEventWhen(item);
        if (meta) meta.textContent = when;
        const approx =
            item.location_precision &&
            item.location_precision !== 'exact' &&
            !item.has_exact_coordinates;
        if (place) {
            place.textContent = approx
                ? i18n.spotlightApproxNote || i18n.approximateCityLocation
                : item.venue_label || i18n.exactVenue || '';
        }
        spotlightEvent = item;
        spotlightEl.hidden = false;
        // Connect spotlight to its real map marker when that marker is on-screen.
        // Defer one frame so aggregate fitBounds / paint layout settle.
        requestAnimationFrame(() => highlightSpotlightMarker(item));
    };

    const frameVisibleAggregates = (aggs, { animate = true } = {}) => {
        if (!isV35 || !map || !Array.isArray(aggs) || !aggs.length) return;
        const bounds = new window.maplibregl.LngLatBounds();
        let n = 0;
        aggs.forEach((a) => {
            if (a?.latitude == null || a?.longitude == null) return;
            bounds.extend([Number(a.longitude), Number(a.latitude)]);
            n += 1;
        });
        if (origin.hasOrigin) {
            bounds.extend([origin.lng, origin.lat]);
            n += 1;
        }
        if (n < 1) return;
        const narrow = typeof window !== 'undefined' && window.matchMedia('(max-width: 639px)').matches;
        const padding = narrow
            ? { top: 72, bottom: 110, left: 36, right: 56 }
            : { top: 96, bottom: 130, left: 220, right: 96 };
        markProgrammaticMove(animate ? 1200 : 200);
        map.fitBounds(bounds, {
            padding,
            maxZoom: Math.min(9.85, zoomForRadius(origin.km > 0 ? Math.max(origin.km, 80) : 100)),
            duration: animate && !prefersReducedMotion() ? 900 : 0,
            pitch: prefersReducedMotion() ? 0 : 34,
            bearing: prefersReducedMotion() ? 0 : -6,
        });
    };

    const placeCard = (point) => {
        const card = root.querySelector('[data-lm-card]');
        if (!card || !stage) return;
        const rect = stage.getBoundingClientRect();
        const w = Math.min(isV35 ? 368 : 280, rect.width - 24);
        const lift = isV35 ? 150 : 220;
        const floorPad = isV35 ? 36 : 240;
        let x = Math.min(Math.max(point.x - w / 2, 12), rect.width - w - 12);
        let y = Math.min(Math.max(point.y - lift, 56), rect.height - floorPad);
        // Keep card above pin; if clipped at top, flip below.
        if (y < 56 && isV35) {
            y = Math.min(point.y + 18, rect.height - 140);
        }
        // V3.5: keep card clear of the location filter (top-start).
        if (isV35 && rect.width >= 640) {
            const loc = root.querySelector('[data-lm-loc-panel]');
            const locW = (loc?.offsetWidth || 280) + 16;
            const locH = (loc?.offsetHeight || 220) + 12;
            if (x < locW && y < locH) {
                // Prefer right of filter; else sit below it near the pin.
                const preferRight = point.x > locW * 0.55 || rect.width - locW > w + 40;
                if (preferRight) {
                    x = Math.min(Math.max(Math.max(point.x - w / 2, locW + 10), 12), rect.width - w - 12);
                    if (x < locW && y < locH) y = Math.min(Math.max(locH + 8, point.y - 40), rect.height - 140);
                } else {
                    y = Math.min(Math.max(locH + 8, point.y - 40), rect.height - 140);
                }
            }
        }
        card.style.width = `${w}px`;
        card.style.left = `${x}px`;
        card.style.top = `${y}px`;
        card.style.right = 'auto';
    };

    const openEventCard = (item, theme) => {
        const card = root.querySelector('[data-lm-card]');
        if (!card) return;
        card.hidden = false;
        const media = card.querySelector('[data-lm-card-media]');
        const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
        const paintFallback = () => {
            media.innerHTML = `<div class="lm-card__fallback" style="--c:${theme.color};--s:${theme.soft}">${theme.emoji}</div>`;
        };
        if (imageUrl) {
            media.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" decoding="async">`;
            const img = media.querySelector('img');
            if (img) img.addEventListener('error', paintFallback, { once: true });
        } else paintFallback();

        const cat = card.querySelector('[data-lm-card-cat]');
        if (cat) {
            cat.textContent = `${theme.emoji} ${item.category_name || ''}`.trim();
            cat.style.background = theme.soft;
            cat.style.color = theme.color;
        }
        const titleEl = card.querySelector('[data-lm-card-title]');
        titleEl.textContent = item.title || '';
        titleEl.setAttribute('dir', 'auto');

        const liveEl = card.querySelector('[data-lm-card-live]');
        if (liveEl) {
            const live = isEventLive(item);
            liveEl.hidden = !live;
            if (live) liveEl.textContent = i18n.liveNow || 'Live Now';
        }

        const distKm = eventDistanceKm(item);
        const distLabel = formatDistanceLabel(distKm, i18n);
        const placeEl = card.querySelector('[data-lm-card-place]');
        if (placeEl) {
            const placeBits = [];
            if (item.venue_label) placeBits.push(item.venue_label);
            else if (origin.label) placeBits.push(origin.label);
            if (distLabel) placeBits.push(distLabel);
            if (item.location_precision && item.location_precision !== 'exact') {
                placeBits.push(i18n.approximateCityLocation);
            }
            const when = formatEventWhen(item);
            if (when) placeBits.push(when);
            const placeText = placeBits.join(' · ');
            placeEl.innerHTML = placeText
                ? `<svg class="lm-card__place-ico" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true"><path d="M12 22s7-7.2 7-12.2A7 7 0 1 0 5 9.8C5 14.8 12 22 12 22z" fill="#7138F5"/><circle cx="12" cy="9.5" r="2.4" fill="#fff"/></svg><span dir="auto">${escapeHtml(placeText)}</span>`
                : '';
        }

        const peopleEl = card.querySelector('[data-lm-card-people]');
        if (peopleEl) {
            // Avatars only when API provides them — never invent faces.
            const avatars = Array.isArray(item.member_avatars) ? item.member_avatars.filter(Boolean) : [];
            const count = Number(item.members_count) || 0;
            if (avatars.length) {
                peopleEl.innerHTML = `${avatars
                    .slice(0, 3)
                    .map(
                        (src) =>
                            `<img src="${escapeHtml(src)}" alt="" width="22" height="22" loading="lazy" style="border-radius:9999px;object-fit:cover;border:2px solid #fff;margin-inline-end:-6px">`
                    )
                    .join('')}${
                    count > 0
                        ? `<span style="margin-inline-start:.45rem">${escapeHtml(
                              fillTemplate(i18n.going, { count })
                          )}</span>`
                        : ''
                }`;
            } else {
                peopleEl.textContent =
                    count > 0 ? fillTemplate(i18n.going, { count }) : '';
            }
        }

        const when = item.starts_at
            ? new Date(item.starts_at).toLocaleString(undefined, {
                  month: 'short',
                  day: 'numeric',
                  hour: 'numeric',
                  minute: '2-digit',
              })
            : '';
        const bits = [];
        if (item.venue_label) bits.push(item.venue_label);
        if (distLabel) bits.push(distLabel);
        if (when) bits.push(when);
        if (item.location_precision && item.location_precision !== 'exact') {
            bits.push(i18n.approximateCityLocation);
        }
        if (item.members_count != null && Number(item.members_count) > 0) {
            bits.push(fillTemplate(i18n.going, { count: item.members_count }));
        }
        const meta = card.querySelector('[data-lm-card-meta]');
        if (meta) {
            meta.textContent = bits.join(' · ');
            meta.setAttribute('dir', 'auto');
        }
        const link = card.querySelector('[data-lm-card-link]');
        if (link) {
            if (!isV35) link.textContent = i18n.viewEvent;
            link.href = item.url || (item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events');
            link.setAttribute('aria-label', i18n.viewEvent);
        }
        requestAnimationFrame(() => card.classList.add('is-open'));
    };

    const highlightList = (eventId) => {
        listEl?.querySelectorAll('[data-lm-list-event]').forEach((row) => {
            const on = String(row.dataset.lmListEvent) === String(eventId);
            row.classList.toggle('is-active', on);
            if (on) {
                row.scrollIntoView({
                    inline: 'nearest',
                    block: 'nearest',
                    behavior: prefersReducedMotion() ? 'auto' : 'smooth',
                });
            }
        });
        cityPanel?.querySelectorAll('[data-city-event-id]').forEach((btn) => {
            btn.classList.toggle('is-active', String(btn.dataset.cityEventId) === String(eventId));
        });
    };

    const renderList = (events) => {
        if (!listEl) return;
        let rows = (events || []).filter((e) => e && e.id);
        if (viewMode === 'nearby' && origin.hasOrigin) {
            rows = [...rows].sort((a, b) => {
                const da = eventDistanceKm(a);
                const db = eventDistanceKm(b);
                if (da == null && db == null) return 0;
                if (da == null) return 1;
                if (db == null) return -1;
                return da - db;
            });
        }
        listEvents = rows;
        if (!listEvents.length) {
            listEl.innerHTML = `<p class="lm-rail__empty">${escapeHtml(i18n.listEmpty)}</p>`;
            return;
        }
        const isRtl = (root.dataset.locale || document.documentElement.lang || '') === 'ar'
            || document.documentElement.dir === 'rtl';
        listEl.innerHTML = listEvents
            .map((item) => {
                const theme = themeFor(item.category_name);
                const approx =
                    item.location_precision &&
                    item.location_precision !== 'exact' &&
                    !item.has_exact_coordinates;
                const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
                const media = imageUrl
                    ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" decoding="async" class="lm-rail__img">`
                    : `<span class="lm-rail__fallback" style="--s:${theme.soft}">${theme.emoji}</span>`;
                const when = item.starts_at
                    ? new Date(item.starts_at).toLocaleString(undefined, {
                          month: 'short',
                          day: 'numeric',
                          hour: 'numeric',
                          minute: '2-digit',
                      })
                    : '';
                const distKm = eventDistanceKm(item);
                const distLabel = distKm != null ? formatDistanceLabel(distKm, i18n) : '';
                const place = approx
                    ? i18n.approximateCityLocation
                    : item.venue_label || '';
                const metaBits = [place, when].filter(Boolean);
                const going =
                    item.members_count != null && Number(item.members_count) > 0
                        ? fillTemplate(i18n.going, { count: item.members_count })
                        : '';
                const href = item.url || (item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events');
                return `<article class="lm-rail__card" role="listitem" data-lm-list-event="${escapeHtml(
                    String(item.id)
                )}" tabindex="0">
                  <div class="lm-rail__media">
                    ${media}
                    <span class="lm-rail__badge" style="color:${theme.color}">${escapeHtml(
                        `${theme.emoji} ${item.category_name || ''}`
                    )}</span>
                    ${
                        distLabel
                            ? `<span class="lm-rail__dist">${escapeHtml(distLabel)}</span>`
                            : ''
                    }
                  </div>
                  <div class="lm-rail__body">
                    <strong dir="auto">${escapeHtml(item.title || '')}</strong>
                    <span class="lm-rail__meta" dir="auto">${escapeHtml(metaBits.join(' · '))}</span>
                    <div class="lm-rail__foot">
                      <span class="lm-rail__going">${escapeHtml(going)}</span>
                      <a class="lm-rail__go" href="${escapeHtml(href)}" aria-label="${escapeHtml(
                          i18n.viewEvent
                      )}">${isRtl ? '‹' : '›'}</a>
                    </div>
                  </div>
                </article>`;
            })
            .join('');
        listEl.querySelectorAll('[data-lm-list-event]').forEach((card) => {
            const img = card.querySelector('img');
            if (img) {
                img.addEventListener(
                    'error',
                    () => {
                        const theme = themeFor(
                            listEvents.find((e) => String(e.id) === card.dataset.lmListEvent)
                                ?.category_name
                        );
                        const fallback = document.createElement('span');
                        fallback.className = 'lm-rail__fallback';
                        fallback.style.setProperty('--s', theme.soft);
                        fallback.textContent = theme.emoji;
                        img.replaceWith(fallback);
                    },
                    { once: true }
                );
            }
            const activate = () => {
                const item = listEvents.find((e) => String(e.id) === card.dataset.lmListEvent);
                if (!item) return;
                if (viewMode === 'list') setViewMode('map');
                selectEvent(item);
            };
            card.addEventListener('click', (e) => {
                if (e.target.closest('a.lm-rail__go')) return;
                activate();
            });
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    activate();
                }
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
                const when = formatEventWhen(ev);
                const members =
                    Number(ev.members_count) > 0
                        ? fillTemplate(i18n.going, { count: ev.members_count })
                        : '';
                const meta = [
                    ev.category_name,
                    approx ? i18n.approximateCityLocation : ev.venue_label,
                    when,
                    members,
                ]
                    .filter(Boolean)
                    .join(' · ');
                return `<button type="button" class="lm-city-panel__row" data-city-event-id="${escapeHtml(
                    String(ev.id)
                )}" aria-label="${escapeHtml(ev.title || '')}">
                    <span class="lm-city-panel__thumb">${thumb}</span>
                    <span class="lm-city-panel__body">
                      <strong dir="auto">${escapeHtml(ev.title || '')}</strong>
                      <em dir="auto">${escapeHtml(meta)}</em>
                    </span>
                  </button>`;
            })
            .join('');
        cityPanel.innerHTML = `
          <div class="lm-city-panel__head">
            <div>
              <p>${escapeHtml(i18n.eventsInLabel)}</p>
              <strong dir="auto">${escapeHtml(cityName || '')}</strong>
              <span>${escapeHtml(
                  fillTemplate(i18n.aggregateEventsInCity, {
                      count: activeCityEvents.length,
                      city: cityName || '',
                  })
              )}</span>
            </div>
            <button type="button" data-lm-city-close aria-label="${escapeHtml(i18n.closeCity)}">✕</button>
          </div>
          <div class="lm-city-panel__list fancy-scroll">${
              rows || `<p class="lm-city-panel__empty">${escapeHtml(i18n.noEligibleEvents)}</p>`
          }</div>`;
        cityPanel.hidden = false;
        cityPanel.setAttribute('role', 'dialog');
        cityPanel.setAttribute('aria-label', `${i18n.eventsInLabel} ${cityName || ''}`.trim());
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

    const selectEvent = async (item, el = null, opts = {}) => {
        stopTour(false);
        selectedEventId = item.id;
        const theme = themeFor(item.category_name);
        const exact =
            item.location_precision === 'exact' || item.has_exact_coordinates === true;
        let pin = el || pinByEventId.get(String(item.id)) || null;
        // Approximate events share a city aggregate teardrop — highlight that marker.
        if (!pin && isV35 && !exact) {
            pin = findAggElForEvent(item);
        }
        // Keep city aggregate selected when opening an approx event from the city list (no exact pin).
        const keepAgg =
            isV35 &&
            !pin &&
            selectedEl?.classList.contains('lm-marker--agg') &&
            (activeCityEvents.some((e) => String(e.id) === String(item.id)) ||
                (item.city_id != null && selectedEl.dataset.cityId === String(item.city_id)));
        if (selectedEl && selectedEl !== pin && !keepAgg) {
            selectedEl.classList.remove('is-selected', 'is-pulse');
            if (selectedEl.classList.contains('lm-marker') && !selectedEl.classList.contains('lm-marker--agg')) {
                const t = themeFor(selectedEl.dataset.cat);
                selectedEl.style.setProperty('--lm-glow', t.glow);
                const body =
                    markerSvg(t, false, isV35) +
                    (isV35 ? `<span class="lm-marker-halo" style="--lm-glow:${t.glow}"></span>` : '');
                selectedEl.innerHTML = wrapMarkerVisual(body);
            } else if (selectedEl.classList.contains('lm-marker--agg')) {
                const color = selectedEl.style.getPropertyValue('--lm-pin') || '#7138F5';
                const glow = selectedEl.style.getPropertyValue('--lm-glow') || 'rgba(113,56,245,.45)';
                const countLabel = selectedEl.getAttribute('data-count') || '';
                if (countLabel) {
                    selectedEl.innerHTML = wrapMarkerVisual(
                        premiumTeardropSvg({ color, count: countLabel, selected: false }) +
                            `<span class="lm-marker__count" style="color:${color}">${escapeHtml(String(countLabel))}</span>` +
                            `<span class="lm-marker-halo" style="--lm-glow:${glow}"></span>`
                    );
                }
            }
        }
        if (!keepAgg) selectedEl = pin;
        if (pin?.classList.contains('lm-marker') && !pin.classList.contains('lm-marker--agg')) {
            pin.dataset.cat = item.category_name || '';
            pin.style.color = theme.color;
            pin.style.setProperty('--lm-glow', theme.glow);
            pin.style.setProperty('--lm-pin', theme.color);
            pin.classList.add('is-selected', 'is-float', 'is-pulse');
            const body =
                markerSvg(theme, true, isV35) +
                (isV35 ? `<span class="lm-marker-halo" style="--lm-glow:${theme.glow}"></span>` : '');
            pin.innerHTML = wrapMarkerVisual(body);
        } else if (pin?.classList.contains('lm-marker--agg')) {
            pin.classList.add('is-selected', 'is-pulse');
            const color = pin.style.getPropertyValue('--lm-pin') || '#7138F5';
            const count = pin.dataset.count || '';
            const glow = pin.style.getPropertyValue('--lm-glow') || 'rgba(113,56,245,.45)';
            if (count) {
                pin.innerHTML = wrapMarkerVisual(
                    premiumTeardropSvg({ color, count, selected: true }) +
                        `<span class="lm-marker__count" style="color:${color}">${escapeHtml(String(count))}</span>` +
                        `<span class="lm-marker-halo" style="--lm-glow:${glow}"></span>`
                );
            }
        }
        // Spotlight yields to the interactive floating card.
        if (isV35) hideSpotlight();
        openEventCard(item, theme);
        highlightList(item.id);
        if (isDiscover) highlightDiscoverCard(item.id);
        // V3.5 location filter owns city label — do not overwrite with event title.
        if (!isV35) {
            setFocus(item.title || '', exact ? i18n.exactVenue : i18n.approximateCityLocation);
        } else if (statusEl) {
            statusEl.textContent = item.title || '';
        }
        const skipCamera = opts.skipCamera === true;
        if (!skipCamera && exact && item.latitude != null && item.longitude != null) {
            markProgrammaticMove(1700);
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: Math.max(map.getZoom(), 12.6),
                    pitch: prefersReducedMotion() ? 0 : isV35 ? 44 : 52,
                    bearing: (map.getBearing() + 14) % 360,
                },
                1500
            );
            placeCard(map.project([item.longitude, item.latitude]));
        } else if (item.latitude != null && item.longitude != null) {
            // Approximate pins stay at city scale — still anchor the floating card to the pin.
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
            markProgrammaticMove(1600);
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
        const cityName = item.city_name || aggregateDisplayName(item, i18n);
        const around = fillTemplate(i18n.eventsAround || i18n.livingCitySub || 'Events around :city · Approximate locations', {
            city: cityName,
        });
        setFocus(cityName, around);
        if (isV35 && el) {
            el.classList.add('is-selected', 'is-pulse');
            const color = el.style.getPropertyValue('--lm-pin') || '#7138F5';
            const count = item.count ?? item.city_count ?? el.dataset.count ?? '';
            const glow = el.style.getPropertyValue('--lm-glow') || 'rgba(113,56,245,.45)';
            el.innerHTML = wrapMarkerVisual(
                premiumTeardropSvg({ color, count: count || '·', selected: true }) +
                    `<span class="lm-marker__count" style="color:${color}">${escapeHtml(String(count || '·'))}</span>` +
                    `<span class="lm-marker-halo" style="--lm-glow:${glow}"></span>`
            );
        }
        // Aggregate click → show city list first (do not auto-open an event card).
        closeCard();
        if (isV35) hideSpotlight();
        setStatus(fillTemplate(i18n.loadingCityEvents, { city: cityName }));
        markProgrammaticMove(1800);
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: isV35 ? 11.8 : 11.4,
                pitch: prefersReducedMotion() ? 0 : isV35 ? 38 : 50,
                bearing: isV35 ? -8 : -12,
            },
            1600
        );
        try {
            let events = [];
            let name = cityName;
            let resolvedCityId = item.city_id;
            if (item.city_id) {
                const payload = await fetchCityEvents(item.city_id);
                events = payload.events || [];
                name = payload.city?.name || cityName;
                resolvedCityId = payload.city?.id ?? item.city_id;
            } else if (Array.isArray(item.sample_event_ids) && item.sample_event_ids.length) {
                // Fallback when API omitted city_id on approx markers — use payload cache events.
                const idSet = new Set(item.sample_event_ids.map(String));
                events = (payloadCache?.markers || []).filter((e) => idSet.has(String(e.id)));
            }
            renderCityPanel(name, events, resolvedCityId);
            const headingTpl = root.dataset.i18nEventsInCity;
            setListHeading(
                headingTpl ? fillTemplate(headingTpl, { city: name }) : `${i18n.eventsInLabel} ${name}`
            );
            renderList(events);
            setStatus(
                fillTemplate(i18n.aggregateEventsInCity || ':count events in :city · Approximate locations', {
                    count: events.length,
                    city: name,
                })
            );
        } catch (e) {
            console.warn(e);
            setStatus(i18n.cityEventsFailed);
        }
    };

    const createPinEl = (item, theme) => {
        const el = document.createElement('button');
        el.type = 'button';
        el.className = isV35 ? 'lm-marker lm-marker--teardrop is-float' : 'lm-marker';
        el.style.setProperty('--lm-glow', theme.glow);
        el.style.setProperty('--lm-pin', theme.color);
        const body =
            markerSvg(theme, false, isV35) +
            (isV35 ? `<span class="lm-marker-halo" style="--lm-glow:${theme.glow}"></span>` : '');
        // Always wrap — stage CSS animates .lm-marker__visual so MapLibre root transform stays intact.
        el.innerHTML = wrapMarkerVisual(body);
        const exactLabel = item.title
            ? fillTemplate(i18n.exactEventPin || ':title · Exact venue', { title: item.title })
            : i18n.exactVenue || 'Exact venue';
        el.title = exactLabel;
        el.dataset.eventId = String(item.id || '');
        el.dataset.cat = item.category_name || '';
        el.dataset.markerKind = 'exact-event';
        el.setAttribute('aria-label', exactLabel);
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            selectEvent(item, el);
        });
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectEvent(item, el);
            }
        });
        return el;
    };

    const createAggEl = (item) => {
        const el = document.createElement('button');
        el.type = 'button';
        const count = item.count ?? item.city_count ?? 0;
        const isCluster = item.type === 'city_cluster';
        const name = aggregateDisplayName(item, i18n);
        if (isV35) {
            // City-level / cluster = ONE premium teardrop with count (never overlapping discs).
            const color = isCluster ? (count >= 8 ? '#FF762E' : '#2563EB') : count >= 8 ? '#FF762E' : '#7138F5';
            const glow = isCluster ? 'rgba(37,99,235,.45)' : 'rgba(113,56,245,.45)';
            el.className = 'lm-marker lm-marker--teardrop lm-marker--agg is-float';
            el.style.setProperty('--lm-glow', glow);
            el.style.setProperty('--lm-pin', color);
            const displayCount = count || name;
            const cityLabel = item.city_name || name;
            const aria = isCluster
                ? fillTemplate(i18n.cityClusterCount || ':count cities', { count: displayCount })
                : fillTemplate(
                      i18n.aggregateEventsInCity ||
                          ':count events in :city · Approximate locations',
                      { count: displayCount, city: cityLabel }
                  );
            el.innerHTML = wrapMarkerVisual(
                premiumTeardropSvg({ color, count: displayCount, selected: false }) +
                    `<span class="lm-marker__count" style="color:${color}">${escapeHtml(String(displayCount))}</span>` +
                    `<span class="lm-marker-halo" style="--lm-glow:${glow}"></span>`
            );
            el.title = aria;
            el.setAttribute('aria-label', aria);
            el.dataset.cityId = item.city_id != null ? String(item.city_id) : '';
            el.dataset.count = String(count || '');
            el.dataset.markerKind = isCluster ? 'cluster' : 'city-aggregate';
        } else if (isCluster) {
            el.className = 'lm-cluster-pin';
            el.innerHTML = `<span>${escapeHtml(String(count))}</span>`;
            el.title = item.label || i18n.cityCluster;
            el.style.setProperty('--lm-cluster', count >= 8 ? '#FF762E' : '#3B82F6');
        } else {
            el.className = 'lm-collection';
            el.innerHTML = `
              <span class="lm-collection__name" dir="auto">${escapeHtml(name)}</span>
              <span class="lm-collection__meta" dir="ltr"><strong>${escapeHtml(String(count))}</strong> ${escapeHtml(
                  i18n.eventsWord
              )}</span>
              <span class="lm-collection__cta">${escapeHtml(i18n.open)}</span>`;
            el.title = item.label || name;
        }
        el.addEventListener('click', (e) => {
            e.stopPropagation();
            enterCity(item, el);
        });
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                enterCity(item, el);
            }
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
            const aria = el.getAttribute('aria-label') || '';
            const m = new window.maplibregl.Marker({
                element: el,
                anchor: 'bottom',
                ariaLabel: aria || undefined,
            })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            // MapLibre 4.x may still stamp a generic label — re-assert ours.
            if (aria) el.setAttribute('aria-label', aria);
            markerObjs.push(m);
            if (item.id) pinByEventId.set(String(item.id), el);
        });

        // V3.5.5 — approximate events never get individual pins (no invented fan-out).
        // ONE premium aggregate teardrop per verified city centroid.
        const collections = (payload.city_aggregates || []).filter(
            (c) => c.latitude != null && c.longitude != null
        );
        const named = collections.filter((c) => c.type !== 'city_cluster').slice(0, 14);
        const clusters = collections.filter((c) => c.type === 'city_cluster').slice(0, 3);

        if (isV35 && approx.length) {
            // API markerPayload omits city_id — group by shared city-centroid coordinates
            // (DiscoverMapService places approx points at cities.latitude/longitude).
            const byCentroid = new Map();
            approx.forEach((item) => {
                const key = `${Number(item.latitude).toFixed(4)},${Number(item.longitude).toFixed(4)}`;
                if (!byCentroid.has(key)) byCentroid.set(key, []);
                byCentroid.get(key).push(item);
            });
            byCentroid.forEach((items) => {
                const first = items[0];
                const verified = verifiedCityAtCentroid(first.latitude, first.longitude);
                const cityName =
                    first.city_name ||
                    cityNameFromVenue(first.venue_label) ||
                    verified?.name ||
                    '';
                // Unresolvable city → list-only (no unreliable map pin).
                if (!cityName) return;
                const resolvedCityId = first.city_id ?? verified?.id ?? null;
                const twin = named.find((c) => {
                    if (resolvedCityId != null && c.city_id != null) {
                        return String(c.city_id) === String(resolvedCityId);
                    }
                    const d = distanceKmBetween(c.latitude, c.longitude, first.latitude, first.longitude);
                    const sameName =
                        String(c.city_name || '')
                            .toLowerCase()
                            .trim() === cityName.toLowerCase();
                    return sameName || (d != null && d < 2);
                });
                const ids = items.map((e) => e.id).filter(Boolean);
                if (twin) {
                    twin.count = Math.max(Number(twin.count) || 0, items.length);
                    twin.city_name = twin.city_name || cityName;
                    if (twin.city_id == null && resolvedCityId != null) twin.city_id = resolvedCityId;
                    if (!twin.sample_event_ids?.length) twin.sample_event_ids = ids;
                    return;
                }
                named.push({
                    type: 'city_collection',
                    city_id: resolvedCityId,
                    city_name: cityName,
                    latitude: first.latitude,
                    longitude: first.longitude,
                    count: items.length,
                    label: `${cityName} · ${items.length}`,
                    location_precision: 'city_level',
                    sample_event_ids: ids,
                });
            });
        }

        // Deduplicate near-identical / same-name city pins — keep highest count + real city_id.
        const deduped = [];
        named.forEach((agg) => {
            const twin = deduped.find((c) => {
                const sameId =
                    agg.city_id != null && c.city_id != null && String(agg.city_id) === String(c.city_id);
                const d = distanceKmBetween(c.latitude, c.longitude, agg.latitude, agg.longitude);
                const samePlace = d != null && d < 3;
                const sameName =
                    (c.city_name || '').toLowerCase().trim() === (agg.city_name || '').toLowerCase().trim() &&
                    (c.city_name || '').length > 0;
                return sameId || (sameName && samePlace) || samePlace;
            });
            if (!twin) {
                deduped.push({ ...agg });
                return;
            }
            twin.count = Math.max(Number(twin.count) || 0, Number(agg.count) || 0);
            if (twin.city_id == null && agg.city_id != null) {
                twin.city_id = agg.city_id;
                twin.latitude = agg.latitude;
                twin.longitude = agg.longitude;
                twin.city_name = agg.city_name || twin.city_name;
            }
            if (!twin.sample_event_ids?.length && agg.sample_event_ids?.length) {
                twin.sample_event_ids = agg.sample_event_ids;
            }
        });

        [...deduped, ...clusters].forEach((item) => {
            const el = createAggEl(item);
            const aria = el.getAttribute('aria-label') || '';
            const m = new window.maplibregl.Marker({
                element: el,
                anchor: isV35 ? 'bottom' : 'center',
                ariaLabel: aria || undefined,
            })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            if (aria) el.setAttribute('aria-label', aria);
            markerObjs.push(m);
            if (item.city_id != null) pinByCityId.set(String(item.city_id), el);
        });

        const listRows = [...exact, ...approx];
        renderList(listRows);
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

        // V3.5.6/7 — widen framing to include painted city aggregates (no coordinate invents).
        // Spotlight waits until framing settles so marker↔spotlight connection uses on-canvas pins.
        const offerSpotlight = () => {
            if (!isV35) return;
            const pick = pickSpotlightEvent(listRows);
            if (pick) renderSpotlight(pick);
            else hideSpotlight();
        };

        if (pendingAggregateFrame && isV35 && deduped.length >= 2) {
            pendingAggregateFrame = false;
            frameVisibleAggregates(deduped, { animate: !prefersReducedMotion() });
            let offered = false;
            const once = () => {
                if (offered) return;
                offered = true;
                requestAnimationFrame(offerSpotlight);
            };
            map.once('moveend', once);
            setTimeout(once, prefersReducedMotion() ? 80 : 1100);
        } else {
            pendingAggregateFrame = false;
            offerSpotlight();
        }
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
            map.setFog(
                isV35
                    ? {
                          color: 'rgb(210, 220, 255)',
                          'high-color': 'rgb(176, 198, 255)',
                          'horizon-blend': 0.14,
                          'space-color': 'rgb(236, 230, 255)',
                          'star-intensity': 0,
                      }
                    : {
                          color: 'rgb(214, 228, 255)',
                          'high-color': 'rgb(186, 210, 255)',
                          'horizon-blend': 0.1,
                          'space-color': 'rgb(245, 240, 255)',
                          'star-intensity': 0,
                      }
            );
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

        syncRadiusUi();
        setStatus(i18n.composing);
        try {
            await refreshFromOrigin({ animate: !prefersReducedMotion() });
        } catch (e) {
            console.warn(e);
            setStatus(i18n.discoveryUnavailable);
        }
    });

    async function frameOrigin({ animate = true } = {}) {
        if (!origin.hasOrigin) return;
        setFocus(origin.label || i18n.featuredRegion, focusSubForOrigin());
        ensureOriginLayers();
        markProgrammaticMove(animate ? 1200 : 200);
        if (searchAreaBtn) searchAreaBtn.hidden = true;
        // V3.5.4 — Egypt-first framing: less empty sea, room for loc filter, still pan-anywhere.
        const pitch = prefersReducedMotion() ? 0 : isV35 ? 36 : cinematicPitch;
        const bearing = prefersReducedMotion() ? 0 : -6;
        const padding = isV35
            ? { top: 88, bottom: 64, left: 210, right: 72 }
            : 48;
        const maxZoom = isV35
            ? Math.min(zoomForRadius(origin.km > 0 ? origin.km : 100) + 0.35, 12.4)
            : zoomForRadius(origin.km > 0 ? origin.km : 100);
        await new Promise((resolve) => {
            const opts = {
                padding,
                maxZoom,
                duration: animate ? 900 : 0,
                pitch,
                bearing,
            };
            if (origin.km > 0) {
                // Slightly tighter fit so land/events dominate over empty Mediterranean.
                const viewKm = isV35 ? Math.max(5, origin.km * 0.82) : origin.km;
                map.fitBounds(radiusLngLatBounds(origin.lat, origin.lng, viewKm), opts);
            } else {
                map.easeTo({
                    center: [origin.lng, origin.lat],
                    zoom: isV35 ? 7.6 : 7.2,
                    ...opts,
                });
            }
            map.once('moveend', () => resolve());
            setTimeout(resolve, animate ? 1100 : 50);
        });
    }

    async function refreshFromOrigin({ animate = true } = {}) {
        writeOriginDataset();
        stopTour(false);
        closeCard();
        hideCityPanel();
        setStatus(i18n.composing);
        let payload;
        pendingAggregateFrame = isV35;
        if (origin.hasOrigin) {
            await frameOrigin({ animate });
            // Prefetch with a wider query envelope so Damietta/Mansoura approx points
            // remain available before the post-paint aggregate fitBounds.
            if (isV35 && origin.km > 0) {
                const wide = radiusLngLatBounds(origin.lat, origin.lng, Math.max(origin.km, 95));
                payload = await loadPayload(
                    {
                        getNorth: () => wide[1][1],
                        getSouth: () => wide[0][1],
                        getEast: () => wide[1][0],
                        getWest: () => wide[0][0],
                    },
                    Math.max(7.5, map.getZoom() - 0.6)
                );
            } else {
                payload = await loadPayload(map.getBounds(), map.getZoom());
            }
        } else {
            clearOriginLayers();
            setFocus(i18n.featuredRegion, i18n.featuredRegionSub);
            const egypt = overviewBounds();
            payload = await loadPayload(egypt, 6);
            const focus = computeFeaturedFocus(payload);
            markProgrammaticMove(1600);
            if (searchAreaBtn) searchAreaBtn.hidden = true;
            await animateCamera(
                map,
                {
                    center: focus.center,
                    zoom: focus.zoom,
                    pitch: focus.pitch,
                    bearing: focus.bearing,
                },
                animate && !prefersReducedMotion() ? 1400 : 0
            );
            try {
                payload = await loadPayload(map.getBounds(), map.getZoom());
            } catch {
                /* keep */
            }
        }
        paintData(payload);
        requestAnimationFrame(() => map.resize());
        return payload;
    }

    async function persistRadiusPreference(km) {
        if (!embedded || !origin.cityId) return;
        try {
            const body = new URLSearchParams();
            body.set('_token', csrfToken());
            body.set('_method', 'PUT');
            body.set('city_id', String(origin.cityId));
            body.set('distance_km', String(km));
            body.set('redirect_to', `${window.location.pathname}${window.location.search}`);
            await fetch('/settings/location', {
                method: 'POST',
                headers: {
                    Accept: 'text/html',
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body,
                redirect: 'manual',
            });
        } catch (e) {
            console.warn('persist radius', e);
        }
    }

    async function applyRadius(km) {
        const next = Number(km);
        if (!Number.isFinite(next)) return;
        origin = { ...origin, km: next };
        writeOriginDataset();
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('distance_km', String(next));
            window.history.replaceState({}, '', url.toString());
        } catch {
            /* ignore */
        }
        try {
            const raw = sessionStorage.getItem('funnity_discovery_v4');
            const prev = raw ? JSON.parse(raw) : {};
            sessionStorage.setItem(
                'funnity_discovery_v4',
                JSON.stringify({
                    ...prev,
                    distanceKm: next,
                    updatedAt: Date.now(),
                })
            );
        } catch {
            /* ignore */
        }
        await persistRadiusPreference(next);
        await refreshFromOrigin({ animate: true });
    }

    map.on('movestart', (e) => {
        if (e.originalEvent) stopTour();
    });
    map.on('moveend', () => {
        if (!isDiscover || !searchAreaBtn) return;
        if (Date.now() < programmaticMoveUntil) return;
        searchAreaBtn.hidden = false;
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

    const searchThisArea = async () => {
        if (!isDiscover) return;
        if (searchAreaAbort) searchAreaAbort.abort();
        searchAreaAbort = new AbortController();
        const seq = ++searchAreaSeq;
        if (searchAreaBtn) searchAreaBtn.hidden = true;
        setStatus(i18n.composing);
        try {
            const payload = await loadPayload(map.getBounds(), map.getZoom(), {
                viewportOnly: true,
                includeApproximate: true,
                signal: searchAreaAbort.signal,
            });
            if (seq !== searchAreaSeq) return;
            paintData(payload);
            if (
                !(payload.markers || []).length &&
                !(payload.city_aggregates || []).length &&
                !(payload.clusters || []).length
            ) {
                setStatus(
                    document
                        .querySelector('[data-discover-i18n-no-events-viewport]')
                        ?.getAttribute('data-discover-i18n-no-events-viewport') ||
                        i18n.noEligibleEvents
                );
            }
        } catch (e) {
            if (e?.name === 'AbortError') return;
            console.warn(e);
            setStatus(i18n.discoveryUnavailable);
            if (searchAreaBtn) searchAreaBtn.hidden = false;
        }
    };
    searchAreaBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        searchThisArea().catch((err) => console.warn(err));
    });

    const onDiscoverCardClick = (e) => {
        if (!isDiscover) return;
        const card = e.target.closest('[data-discover-result-card]');
        if (!card) return;
        const link = e.target.closest('a[href]');
        if (
            link &&
            /\/events\/\d+/.test(link.getAttribute('href') || '') &&
            link.textContent &&
            /view details|عرض التفاصيل/i.test(link.textContent.trim())
        ) {
            return;
        }
        if (link && !/\/events\/\d+/.test(link.getAttribute('href') || '')) return;
        if (link && /\/events\/\d+/.test(link.getAttribute('href') || '')) {
            e.preventDefault();
        }
        const id = card.dataset.eventId || card.dataset.id;
        if (!id) return;
        const fromList = listEvents.find((x) => String(x.id) === String(id));
        const fromPins = eventTourQueue.find((x) => String(x.id) === String(id));
        const item = fromList || fromPins;
        if (item) {
            selectEvent(item, pinByEventId.get(String(id))).catch(() => {});
            highlightDiscoverCard(id);
        }
    };
    document.addEventListener('click', onDiscoverCardClick);

    // Keep map markers in sync when Discover filters change (GET form / live search).
    const filterForm = document.querySelector('[data-discover-filters]');
    const onFilterChange = () => {
        if (!isDiscover) return;
        // Debounced soft refresh — full page navigation still owns list SSOT.
        window.clearTimeout(onFilterChange._t);
        onFilterChange._t = window.setTimeout(() => {
            loadPayload(map.getBounds(), map.getZoom())
                .then((payload) => paintData(payload))
                .catch((err) => console.warn(err));
        }, 350);
    };
    filterForm?.addEventListener('change', onFilterChange);

    tourBtn?.addEventListener('click', () => (touring ? stopTour() : startTour()));
    prevBtn?.addEventListener('click', () => stepManual(-1));
    nextBtn?.addEventListener('click', () => stepManual(1));
    root.querySelector('[data-lm-zoom-in]')?.addEventListener('click', () => {
        markProgrammaticMove(500);
        map.zoomIn({ duration: 400 });
    });
    root.querySelector('[data-lm-zoom-out]')?.addEventListener('click', () => {
        markProgrammaticMove(500);
        map.zoomOut({ duration: 400 });
    });
    root.querySelector('[data-lm-recenter]')?.addEventListener('click', async () => {
        try {
            await refreshFromOrigin({ animate: true });
        } catch (e) {
            console.warn(e);
        }
    });
    root.querySelector('[data-lm-reset]')?.addEventListener('click', async () => {
        try {
            await refreshFromOrigin({ animate: true });
        } catch {
            if (payloadCache) paintData(payloadCache);
        }
    });

    radiusSelect?.addEventListener('change', () => {
        applyRadius(radiusSelect.value).catch((e) => console.warn(e));
    });
    root.querySelectorAll('[data-lm-radius-chip]').forEach((chip) => {
        chip.addEventListener('click', () => {
            applyRadius(chip.value).catch((e) => console.warn(e));
        });
    });
    radiusSlider?.addEventListener('input', () => {
        const idx = Number(radiusSlider.value);
        const km = radiusOptions[idx];
        if (!Number.isFinite(km)) return;
        root.querySelectorAll('.lm-slider__labels span').forEach((span, i) => {
            span.classList.toggle('is-active', i === idx);
        });
        const distReadout = root.querySelector('[data-lm-distance-readout]');
        if (distReadout) {
            distReadout.hidden = false;
            distReadout.textContent =
                km > 0
                    ? fillTemplate(i18n.withinKm || 'within :km km', { km })
                    : i18n.allDistances || 'All distances';
        }
    });
    radiusSlider?.addEventListener('change', () => {
        const idx = Number(radiusSlider.value);
        const km = radiusOptions[idx];
        if (!Number.isFinite(km)) return;
        applyRadius(km).catch((e) => console.warn(e));
    });

    root.querySelectorAll('[data-lm-view]').forEach((btn) => {
        btn.addEventListener('click', () => setViewMode(btn.dataset.lmView || 'map'));
    });

    railNextBtn?.addEventListener('click', () => {
        if (!listEl) return;
        const dir = document.documentElement.dir === 'rtl' ? -1 : 1;
        listEl.scrollBy({
            left: dir * Math.min(320, listEl.clientWidth * 0.8),
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
    });

    listEl?.addEventListener('keydown', (e) => {
        if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
        const rtl = document.documentElement.dir === 'rtl';
        const forward =
            (e.key === 'ArrowRight' && !rtl) || (e.key === 'ArrowLeft' && rtl) ? 1 : -1;
        e.preventDefault();
        listEl.scrollBy({
            left: forward * Math.min(280, listEl.clientWidth * 0.7),
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });
    });

    spotlightEl?.querySelector('[data-lm-spotlight-dismiss]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        hideSpotlight({ persist: true });
    });
    spotlightEl?.querySelector('[data-lm-spotlight-open]')?.addEventListener('click', (e) => {
        e.stopPropagation();
        if (!spotlightEvent) return;
        // Open details without camera navigation (spotlight must not auto-fly).
        selectEvent(spotlightEvent, null, { skipCamera: true }).catch((err) => console.warn(err));
    });
    spotlightEl?.addEventListener('click', (e) => e.stopPropagation());

    const applyDeviceOrigin = async () => {
        if (!useLocationBtn) return;
        const original = useLocationBtn.textContent;
        useLocationBtn.disabled = true;
        useLocationBtn.textContent = i18n.detectingLocation;
        try {
            const pos = await requestDevicePosition();
            const geo = await reverseGeocodeLabel(
                pos.lat,
                pos.lng,
                root.dataset.reverseUrl || '/api/places/reverse'
            );
            const label =
                geo?.short_name ||
                geo?.display_name ||
                i18n.featuredRegion;
            const next = saveDiscoveryState({
                mode: 'device',
                lat: pos.lat,
                lng: pos.lng,
                accuracyM: pos.accuracyM,
                locationLabel: geo?.display_name || null,
                shortLabel: geo?.short_name || null,
                distanceKm: origin.km > 0 ? origin.km : 25,
            });
            origin = {
                lat: next.lat,
                lng: next.lng,
                km: next.distanceKm,
                mode: 'device',
                hasOrigin: true,
                label,
                cityId: null,
            };
            writeOriginDataset();
            setFocus(label, focusSubForOrigin());
            await refreshFromOrigin({ animate: true });
        } catch (err) {
            const code = err?.message || 'unavailable';
            window.alert(code === 'denied' ? i18n.gpsDenied : i18n.gpsUnavailable);
        } finally {
            useLocationBtn.disabled = false;
            useLocationBtn.textContent = original || i18n.useMyLocation;
        }
    };

    useLocationBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        applyDeviceOrigin().catch((err) => console.warn(err));
    });

    const onDiscoveryLocation = (ev) => {
        if (!embedded) return;
        const detail = ev?.detail || {};
        const nextMode = detail.mode || 'nationwide';
        const nextKm = Number(detail.distanceKm);
        const lat = Number(detail.lat);
        const lng = Number(detail.lng);
        const hasCoords = Number.isFinite(lat) && Number.isFinite(lng);
        origin = {
            lat: hasCoords ? lat : origin.lat,
            lng: hasCoords ? lng : origin.lng,
            km: Number.isFinite(nextKm) ? nextKm : origin.km,
            mode: nextMode,
            hasOrigin: nextMode !== 'nationwide' && hasCoords,
            label: detail.cityName || detail.shortLabel || detail.locationLabel || origin.label,
            cityId:
                detail.cityId != null && Number.isFinite(Number(detail.cityId))
                    ? Number(detail.cityId)
                    : origin.cityId,
        };
        refreshFromOrigin({ animate: true }).catch((e) => console.warn(e));
    };
    document.addEventListener('funnity:discovery-location', onDiscoveryLocation);

    const onResize = () => {
        try {
            map.resize();
        } catch {
            /* ignore */
        }
    };
    window.addEventListener('resize', onResize);

    const api = {
        map,
        destroy() {
            stopTour();
            clearMarkers();
            document.removeEventListener('funnity:discovery-location', onDiscoveryLocation);
            document.removeEventListener('click', onDiscoverCardClick);
            filterForm?.removeEventListener('change', onFilterChange);
            window.removeEventListener('resize', onResize);
            map.remove();
            root.dataset.ready = '0';
        },
        getRtlStatus: () => window.maplibregl?.getRTLTextPluginStatus?.() || 'unknown',
        getMode: () => mode,
        getOrigin: () => ({ ...origin }),
        refreshFromOrigin,
        applyRadius,
        searchThisArea,
    };
    root._livingMap = api;
    return api;
}
