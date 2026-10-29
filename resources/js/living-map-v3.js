/**
 * Living Map V3.2 — event-first cinematic preview + MapLibre RTL shaping.
 * Production Home / Discover remain on Leaflet.
 */
import { loadFunnityStyle } from './living-map/funnity-style.js';

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

function themeFor(name = '', slug = '') {
    const key = String(slug || name)
        .toLowerCase()
        .replace(/\s+/g, '-');
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

function setStatus(root, msg) {
    const el = root.querySelector('[data-lm3-status]');
    if (el) el.textContent = msg || '';
}

function setFocus(root, title, sub) {
    const t = root.querySelector('[data-lm3-focus-label]');
    const s = root.querySelector('[data-lm3-focus-sub]');
    if (t) {
        t.textContent = '';
        t.appendChild(document.createTextNode(title || ''));
        t.setAttribute('dir', 'auto');
    }
    if (s) {
        s.textContent = '';
        s.appendChild(document.createTextNode(sub || ''));
        s.setAttribute('dir', 'auto');
    }
}

function uid(prefix = 'g') {
    return `${prefix}${Math.random().toString(16).slice(2, 8)}`;
}

/**
 * MapLibre does not shape Arabic/Hebrew glyphs until the RTL text plugin loads.
 * Without it, vector-tile Arabic labels appear letter-reversed.
 */
/**
 * MapLibre paints Arabic labels letter-reversed until the RTL text plugin is
 * fully `loaded`. Do not create the map before that, or labels stay broken.
 *
 * MapLibre GL JS 4.7 exposes: setRTLTextPlugin(url, lazy) → Promise
 * Passing a callback as the 2nd arg is treated as truthy `lazy` and never loads.
 */
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
            // lazy=false → download + register immediately (critical for Arabic).
            const pending = gl.setRTLTextPlugin(RTL_PLUGIN_URL, false);
            if (pending && typeof pending.then === 'function') {
                await Promise.race([
                    pending,
                    new Promise((_, reject) =>
                        setTimeout(() => reject(new Error('rtl plugin timeout')), 20000)
                    ),
                ]).catch((err) => console.warn('RTL plugin wait', err));
            }
        } catch (e) {
            console.warn('setRTLTextPlugin failed', e);
        }
    };

    await requestPlugin();

    const waitUntilSettled = async (ms) => {
        const started = Date.now();
        while (Date.now() - started < ms) {
            status = statusOf();
            if (status === 'loaded' || status === 'unavailable' || status === 'error') {
                return status;
            }
            await new Promise((r) => setTimeout(r, 50));
        }
        return statusOf();
    };

    status = await waitUntilSettled(20000);
    if (status === 'loaded' || status === 'unavailable' || status === 'error') {
        return status;
    }

    // Second kick — first registration can stick on "requested" until re-invoked.
    await requestPlugin();
    return waitUntilSettled(15000);
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

function aggregateDisplayName(item) {
    if (item.type === 'city_cluster') {
        const n = Number(item.city_count) || (item.cities || []).length || 0;
        return n > 0 ? `${n} cities` : 'City cluster';
    }
    return item.city_name || (item.label || '').split('·')[0].trim() || 'City';
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

function createAggEl(item, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className =
        item.type === 'city_cluster' ? 'lm3-collection lm3-collection--cluster' : 'lm3-collection';
    const name = aggregateDisplayName(item);
    const count = item.count ?? item.city_count ?? 0;
    el.innerHTML = `
      <span class="lm3-collection__name" dir="auto">${escapeHtml(name)}</span>
      <span class="lm3-collection__meta"><strong>${escapeHtml(String(count))}</strong> events</span>
      <span class="lm3-collection__cta">Open</span>`;
    el.title = item.label || `${name} · ${count} events`;
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
    const y = Math.min(Math.max(point.y - 240, 56), rect.height - 260);
    card.style.width = `${w}px`;
    card.style.left = `${x}px`;
    card.style.top = `${y}px`;
    card.style.right = 'auto';
}

function openEventCard(root, item, theme) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card) return;
    card.hidden = false;
    card.dataset.mode = 'event';
    const media = card.querySelector('[data-lm3-card-media]');
    const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
    const paintFallback = () => {
        media.innerHTML = `<div class="lm3-card__fallback" style="--c:${theme.color};--s:${theme.soft}">${theme.emoji}</div>`;
    };
    if (imageUrl) {
        media.innerHTML = `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy">`;
        const img = media.querySelector('img');
        if (img) img.addEventListener('error', paintFallback, { once: true });
    } else {
        paintFallback();
    }
    const cat = card.querySelector('[data-lm3-card-cat]');
    cat.textContent = `${theme.emoji} ${item.category_name || 'Event'}`;
    cat.style.background = theme.soft;
    cat.style.color = theme.color;
    const titleEl = card.querySelector('[data-lm3-card-title]');
    titleEl.textContent = item.title || 'Event';
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
        bits.push('Approximate city location');
    }
    if (item.members_count != null && Number(item.members_count) > 0) {
        bits.push(`${item.members_count} going`);
    }
    const meta = card.querySelector('[data-lm3-card-meta]');
    meta.textContent = bits.join(' · ');
    meta.setAttribute('dir', 'auto');
    const link = card.querySelector('[data-lm3-card-link]');
    link.textContent = 'View Event';
    link.href = item.url || (item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events');
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
    const mid = [
        (a[0] + b[0]) / 2,
        (a[1] + b[1]) / 2 + Math.max(0.12, Math.abs(a[1] - b[1]) * 0.35),
    ];
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
        if (c.type === 'city_cluster') return;
        const w = Math.max(1, Number(c.count) || 1);
        for (let i = 0; i < Math.min(w, 8); i += 1) pts.push([c.longitude, c.latitude]);
    });
    (payload.markers || []).forEach((m) => {
        if (m.latitude == null || m.longitude == null) return;
        pts.push([m.longitude, m.latitude]);
        if (m.location_precision === 'exact') pts.push([m.longitude, m.latitude]);
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
    if (span > 6) zoom = 6.4;
    else if (span > 3) zoom = 7.2;
    else if (span > 1.2) zoom = 8.3;
    else if (span > 0.4) zoom = 9.5;
    else zoom = 10.8;
    return {
        center,
        zoom,
        pitch: prefersReducedMotion() ? 0 : 46,
        bearing: prefersReducedMotion() ? 0 : -6,
        label: 'Featured activity region',
        sub: 'Event-first framing · not your GPS',
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

async function fetchCityEvents(cityId) {
    const params = new URLSearchParams({ city_id: String(cityId) });
    const res = await fetch(`/api/discovery/city-events?${params}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`city-events ${res.status}`);
    return res.json();
}

async function initLivingMapV3(root = document.querySelector('[data-living-map-v3]')) {
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const mapEl = root.querySelector('[data-lm3-map]');
    const stage = root.querySelector('[data-lm3-stage]');
    const tourBtn = root.querySelector('[data-lm3-tour]');
    const prevBtn = root.querySelector('[data-lm3-prev]');
    const nextBtn = root.querySelector('[data-lm3-next]');
    const strip = root.querySelector('[data-lm3-strip]');
    const cityPanel = root.querySelector('[data-lm3-city-panel]');
    const stripTitle = root.querySelector('[data-lm3-strip-title]');
    if (!mapEl || !window.maplibregl) {
        setStatus(root, 'MapLibre failed to load');
        return;
    }

    setStatus(root, 'Loading RTL text + Funnity style…');
    const rtlStatus = await ensureRtlTextPlugin();
    if (rtlStatus !== 'loaded' && rtlStatus !== 'unavailable') {
        console.warn('RTL plugin not fully loaded before map init:', rtlStatus);
    }

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
        pitch: prefersReducedMotion() ? 0 : 44,
        bearing: prefersReducedMotion() ? 0 : -8,
        maxBounds: EGYPT_BOUNDS,
        attributionControl: { compact: true },
        cooperativeGestures: true,
        locale: root.dataset.locale || undefined,
    });
    map.addControl(new window.maplibregl.NavigationControl({ visualizePitch: true }), 'bottom-right');

    const markerObjs = [];
    const pinByEventId = new Map();
    let selectedEl = null;
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
    let mode = 'egypt'; // egypt | city

    const clearMarkers = () => {
        markerObjs.forEach((m) => m.remove());
        markerObjs.length = 0;
        pinByEventId.clear();
        selectedEl = null;
    };

    const highlightStrip = (key) => {
        strip?.querySelectorAll('[data-strip-key]').forEach((a) => {
            a.classList.toggle('is-active', a.dataset.stripKey === key);
            if (a.dataset.stripKey === key) {
                a.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
            }
        });
        cityPanel?.querySelectorAll('[data-city-event-id]').forEach((btn) => {
            btn.classList.toggle('is-active', `e-${btn.dataset.cityEventId}` === key);
        });
    };

    const setStripHeading = (text) => {
        if (stripTitle) {
            stripTitle.textContent = text;
            stripTitle.setAttribute('dir', 'auto');
        }
    };

    const hideCityPanel = () => {
        if (!cityPanel) return;
        cityPanel.hidden = true;
        cityPanel.innerHTML = '';
        activeCityId = null;
        activeCityEvents = [];
        cityEventCursor = 0;
        mode = 'egypt';
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
                return `<button type="button" class="lm3-city-panel__row" data-city-event-id="${escapeHtml(
                    String(ev.id)
                )}">
                    <span class="lm3-city-panel__emoji" aria-hidden="true">${theme.emoji}</span>
                    <span class="lm3-city-panel__body">
                      <strong dir="auto">${escapeHtml(ev.title || 'Event')}</strong>
                      <em dir="auto">${escapeHtml(
                          [ev.category_name, approx ? 'Approximate city location' : ev.venue_label]
                              .filter(Boolean)
                              .join(' · ')
                      )}</em>
                    </span>
                  </button>`;
            })
            .join('');
        cityPanel.innerHTML = `
          <div class="lm3-city-panel__head">
            <div>
              <p>Events in</p>
              <strong dir="auto">${escapeHtml(cityName || 'City')}</strong>
              <span>${activeCityEvents.length} activities · approximate city locations</span>
            </div>
            <button type="button" data-lm3-city-close aria-label="Close city collection">✕</button>
          </div>
          <div class="lm3-city-panel__list fancy-scroll">${
              rows || '<p class="lm3-city-panel__empty">No eligible events</p>'
          }</div>`;
        cityPanel.hidden = false;
        cityPanel.querySelector('[data-lm3-city-close]')?.addEventListener('click', (e) => {
            e.stopPropagation();
            hideCityPanel();
            closeCard(root);
            setStripHeading('Events near you');
            if (payloadCache) paintData(payloadCache, { keepStrip: false });
        });
        cityPanel.querySelectorAll('[data-city-event-id]').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const ev = activeCityEvents.find((x) => String(x.id) === btn.dataset.cityEventId);
                if (ev) selectEvent(ev);
            });
        });
    };

    const renderStrip = (items) => {
        if (!strip) return;
        strip.innerHTML = '';
        const eventsOnly = (items || []).filter(
            (item) => item && item.id && item.type !== 'city_aggregate' && item.type !== 'city_cluster'
        );
        eventsOnly.slice(0, 12).forEach((item) => {
            const theme = themeFor(item.category_name);
            const a = document.createElement('button');
            a.type = 'button';
            a.className = 'lm3-strip__card';
            a.dataset.stripKey = `e-${item.id}`;
            const approx =
                item.location_precision && item.location_precision !== 'exact' && !item.has_exact_coordinates;
            a.innerHTML = `
              <div class="lm3-strip__media" style="--s:${theme.soft}"><span>${theme.emoji}</span></div>
              <div class="pad">
                <span class="lm3-strip__cat" style="color:${theme.color}">${theme.emoji} ${escapeHtml(
                    item.category_name || 'Event'
                )}</span>
                <h4 dir="auto">${escapeHtml(item.title || '')}</h4>
                <p dir="auto">${escapeHtml(
                    approx ? 'Approximate city location' : item.venue_label || ''
                )}</p>
              </div>`;
            a.addEventListener('click', () => selectEvent(item));
            strip.appendChild(a);
        });
    };

    const selectEvent = async (item, el = null) => {
        stopTour(false);
        if (selectedEl) {
            selectedEl.classList.remove('is-selected', 'is-float', 'is-pulse');
            if (selectedEl.classList.contains('lm3-marker')) {
                const prevTheme = themeFor(selectedEl.dataset.cat);
                selectedEl.innerHTML = markerSvg(prevTheme);
            }
        }
        const theme = themeFor(item.category_name);
        const pin = el || pinByEventId.get(String(item.id)) || null;
        selectedEl = pin;
        if (pin && pin.classList.contains('lm3-marker')) {
            pin.dataset.cat = item.category_name || '';
            pin.classList.add('is-selected', 'is-float');
            pin.innerHTML = markerSvg(theme, true);
        }
        openEventCard(root, item, theme);
        highlightStrip(`e-${item.id}`);
        const exact =
            item.location_precision === 'exact' || item.has_exact_coordinates === true;
        setFocus(
            root,
            item.title || 'Event',
            exact ? 'Exact venue' : 'Approximate city-level location'
        );
        if (exact && item.latitude != null && item.longitude != null) {
            mode = 'city';
            await animateCamera(
                map,
                {
                    center: [item.longitude, item.latitude],
                    zoom: Math.max(map.getZoom(), 12.6),
                    pitch: prefersReducedMotion() ? 0 : 52,
                    bearing: (map.getBearing() + 14) % 360,
                },
                1600
            );
            placeCard(root, stage, map.project([item.longitude, item.latitude]));
        } else if (activeCityId && payloadCache) {
            const city = (payloadCache.city_aggregates || []).find(
                (c) => String(c.city_id) === String(activeCityId)
            );
            if (city?.latitude != null) {
                placeCard(root, stage, map.project([city.longitude, city.latitude]));
            }
        }
        const idx = activeCityEvents.findIndex((e) => String(e.id) === String(item.id));
        if (idx >= 0) cityEventCursor = idx;
    };

    const enterCity = async (item, el) => {
        stopTour(false);
        if (item.type === 'city_cluster') {
            // Expand zoom only — not a named venue.
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
                const payload = await fetchMapPayload(map.getBounds(), map.getZoom());
                paintData(payload);
            } catch {
                /* keep */
            }
            return;
        }

        if (selectedEl) selectedEl.classList.remove('is-selected', 'is-pulse');
        selectedEl = el || null;
        el?.classList.add('is-selected', 'is-pulse');
        mode = 'city';
        const cityName = aggregateDisplayName(item);
        setFocus(root, cityName, 'Living City · approximate city-level events');
        setStatus(root, `Loading events in ${cityName}…`);
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: 11.4,
                pitch: prefersReducedMotion() ? 0 : 50,
                bearing: -12,
            },
            1700
        );

        let events = [];
        try {
            const payload = await fetchCityEvents(item.city_id);
            events = payload.events || [];
            renderCityPanel(payload.city?.name || cityName, events, item.city_id);
            setStripHeading(`Events in ${payload.city?.name || cityName}`);
            renderStrip(events);
            setStatus(root, `${events.length} events · Living City`);
            if (events[0]) {
                await selectEvent(events[0]);
            } else {
                closeCard(root);
            }
        } catch (e) {
            console.warn(e);
            setStatus(root, 'Could not load city events');
        }
    };

    const paintData = (payload, opts = {}) => {
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
                    c.longitude != null &&
                    Number(c.count) > 0
            )
            .sort((a, b) => Number(b.count) - Number(a.count));

        // Primary: exact event pins.
        exact.forEach((item) => {
            const theme = themeFor(item.category_name);
            const el = createPinEl(item, theme, selectEvent);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
            if (item.id) pinByEventId.set(String(item.id), el);
        });

        // Tertiary: compact city collections (not giant numeric blobs).
        // Prefer named aggregates; limit clusters to reduce collision.
        const collections = (payload.city_aggregates || [])
            .filter((c) => c.latitude != null && c.longitude != null)
            .sort((a, b) => Number(b.count || 0) - Number(a.count || 0));
        const named = collections.filter((c) => c.type !== 'city_cluster').slice(0, 14);
        const clusters = collections.filter((c) => c.type === 'city_cluster').slice(0, 4);
        [...named, ...clusters].forEach((item) => {
            const el = createAggEl(item, enterCity);
            const m = new window.maplibregl.Marker({ element: el, anchor: 'center' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markerObjs.push(m);
        });

        if (!opts.keepStrip) {
            // Event-first strip: exact first, then approximate individual markers.
            renderStrip([...exact, ...approx]);
            setStripHeading(
                exact.length
                    ? `${exact.length} exact venues · ${approx.length} city-level events`
                    : `${approx.length || cityTour.reduce((n, c) => n + Number(c.count || 0), 0)} discoverable events`
            );
        }

        tourMode =
            eventTourQueue.length >= 2 ? 'exact' : cityTour.length >= 1 ? 'city-events' : 'ambient';
        setStatus(
            root,
            tourMode === 'exact'
                ? `${eventTourQueue.length} exact events · ▶ event tour`
                : tourMode === 'city-events'
                  ? `${cityTour.length} cities · ▶ event discovery tour`
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

    const showTourEvent = async (item, cityMeta = null) => {
        const theme = themeFor(item.category_name);
        openEventCard(root, item, theme);
        highlightStrip(`e-${item.id}`);
        if (item.location_precision === 'exact' && item.latitude != null) {
            placeCard(root, stage, map.project([item.longitude, item.latitude]));
        } else if (cityMeta?.longitude != null) {
            placeCard(root, stage, map.project([cityMeta.longitude, cityMeta.latitude]));
        }
    };

    const runTourStep = async () => {
        if (!touring || prefersReducedMotion() || document.hidden) return;

        if (tourMode === 'exact' && eventTourQueue.length >= 2) {
            const item = eventTourQueue[tourIndex % eventTourQueue.length];
            const next = eventTourQueue[(tourIndex + 1) % eventTourQueue.length];
            tourIndex += 1;
            if (next) drawArc(map, [item.longitude, item.latitude], [next.longitude, next.latitude]);
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
            // Stay honest: camera on city, cycle real events from city-events API.
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
                    setStripHeading(`Events in ${payload.city?.name || city.city_name}`);
                    renderStrip(events);
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
                await showTourEvent(ev, city);
                setFocus(
                    root,
                    ev.title || city.city_name,
                    `${city.city_name} · approximate · ${cityEventCursor}/${events.length}`
                );
                // After finishing city event set, advance to next city.
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
            setStatus(root, 'Reduced motion — tour disabled');
            return;
        }
        if (tourMode === 'ambient') {
            setStatus(root, 'Not enough events for a tour');
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
        if (eventTourQueue.length) {
            tourIndex = (tourIndex + dir + eventTourQueue.length) % eventTourQueue.length;
            const item = eventTourQueue[tourIndex];
            await selectEvent(item, pinByEventId.get(String(item.id)));
        }
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

        // If the plugin finishes after first paint, reshape Arabic by re-applying style.
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

        setStatus(root, 'Composing featured region…');
        const overviewBounds = new window.maplibregl.LngLatBounds([24.9, 22.0], [35.0, 31.7]);
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

        try {
            payload = await fetchMapPayload(map.getBounds(), map.getZoom());
        } catch {
            /* keep overview */
        }
        paintData(payload);
        setStatus(
            root,
            tourMode === 'city-events'
                ? 'Ready · ▶ Event Discovery Tour'
                : tourMode === 'exact'
                  ? 'Ready · ▶ Event tour'
                  : 'Ready'
        );
    });

    map.on('movestart', (e) => {
        if (e.originalEvent) stopTour();
    });
    map.on('click', () => {
        /* keep city panel open; only close floating card on empty map */
        closeCard(root);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopTour();
    });
    const io = new IntersectionObserver((entries) => {
        if (!entries.some((en) => en.isIntersecting)) stopTour();
    });
    io.observe(stage);

    tourBtn?.addEventListener('click', () => (touring ? stopTour() : startTour()));
    prevBtn?.addEventListener('click', () => stepManual(-1));
    nextBtn?.addEventListener('click', () => stepManual(1));
    root.querySelector('[data-lm3-zoom-in]')?.addEventListener('click', () =>
        map.zoomIn({ duration: 400 })
    );
    root.querySelector('[data-lm3-zoom-out]')?.addEventListener('click', () =>
        map.zoomOut({ duration: 400 })
    );
    root.querySelector('[data-lm3-reset]')?.addEventListener('click', async () => {
        stopTour();
        closeCard(root);
        hideCityPanel();
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
        getExactCount: () => eventTourQueue.length,
        getCityCount: () => cityTour.length,
        getMode: () => mode,
        getRtlStatus: () => window.maplibregl?.getRTLTextPluginStatus?.() || 'unknown',
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

export { initLivingMapV3, ensureRtlTextPlugin };
