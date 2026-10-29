/**
 * Explore V4 — Web Discover map (Leaflet + OSM).
 * Egypt-first framing, aggregate clustering, card↔map selection, city drill-down.
 */
import { loadDiscoveryState } from './discovery-location';

const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

/** Populated Egypt focus — Nile Delta, Cairo, Alex, Upper Egypt, Red Sea. */
const EGYPT_FOCUS = [
    [22.2, 25.0],
    [31.65, 34.9],
];
const EGYPT_MAX_BOUNDS = [
    [20.8, 23.8],
    [32.8, 37.2],
];

function loadCss(href) {
    if (document.querySelector(`link[href="${href}"]`)) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

function ensureMarkerStyles() {
    if (document.getElementById('discover-map-marker-css')) return;
    const style = document.createElement('style');
    style.id = 'discover-map-marker-css';
    style.textContent = `
.discover-agg-marker{
  width:36px;height:36px;border-radius:9999px;background:#7138F5;color:#fff;
  border:2.5px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.28);
  display:flex;align-items:center;justify-content:center;
  font:800 12px/1 ui-sans-serif,system-ui,sans-serif;letter-spacing:-0.02em;
}
.discover-agg-marker--lg{width:42px;height:42px;font-size:13px}
.discover-agg-marker--selected{outline:3px solid #312e81;outline-offset:2px}
.discover-city-cluster-marker{
  width:44px;height:44px;border-radius:9999px;background:#5B21B6;color:#fff;
  border:3px solid #E9D5FF;box-shadow:0 2px 10px rgba(15,23,42,.35);
  display:flex;align-items:center;justify-content:center;
  font:800 12px/1 ui-sans-serif,system-ui,sans-serif;
}
.discover-cluster-marker{
  width:40px;height:40px;border-radius:9999px;background:#4C1D95;color:#fff;
  border:2.5px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.3);
  display:flex;align-items:center;justify-content:center;
  font:800 12px/1 ui-sans-serif,system-ui,sans-serif;
}
.discover-exact-marker{
  width:28px;height:36px;filter:drop-shadow(0 2px 4px rgba(15,23,42,.35));
}
.discover-exact-marker svg{display:block;width:28px;height:36px}
.discover-exact-marker--selected svg path{stroke:#312e81;stroke-width:2.5}
.leaflet-div-icon.discover-marker-icon{background:transparent;border:0}
.discover-cluster-tip{display:none!important}
.discover-city-panel{
  margin-bottom:0.5rem;border-radius:0.75rem;border:1px solid #c7d2fe;
  background:#eef2ff;padding:0.5rem 0.65rem;
}
.discover-city-panel h3{font-size:0.8125rem;font-weight:800;color:#1e1b4b;margin:0}
.discover-city-panel p{font-size:0.7rem;color:#4338ca;margin:0.15rem 0 0.4rem}
.discover-city-panel ul{list-style:none;margin:0;padding:0;max-height:12rem;overflow:auto}
.discover-city-panel li{border-top:1px solid #e0e7ff}
.discover-city-panel li button{
  width:100%;text-align:start;padding:0.4rem 0.15rem;font-size:0.75rem;
  font-weight:600;color:#0f172a;background:transparent;border:0;cursor:pointer;
}
.discover-city-panel li button:hover{color:#4338ca}
.discover-city-panel .meta{display:block;font-size:0.65rem;font-weight:500;color:#64748b}
.discover-city-panel [data-city-panel-clear]{
  margin-top:0.4rem;font-size:0.7rem;font-weight:700;color:#4338ca;
  background:transparent;border:0;cursor:pointer;text-decoration:underline;padding:0;
}
`;
    document.head.appendChild(style);
}

function loadLeaflet() {
    return new Promise((resolve, reject) => {
        if (window.L) {
            resolve(window.L);
            return;
        }
        loadCss(LEAFLET_CSS);
        const existing = document.querySelector(`script[src="${LEAFLET_JS}"]`);
        if (existing) {
            existing.addEventListener('load', () => resolve(window.L));
            existing.addEventListener('error', reject);
            return;
        }
        const script = document.createElement('script');
        script.src = LEAFLET_JS;
        script.async = true;
        script.onload = () => resolve(window.L);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function ensureErrorUi(root) {
    let el = root.querySelector('[data-discover-map-error]');
    if (el) return el;
    el = document.createElement('div');
    el.setAttribute('data-discover-map-error', '');
    el.hidden = true;
    el.className =
        'absolute inset-x-3 top-14 z-[600] rounded-xl border border-red-200 bg-white/95 px-3 py-2.5 text-sm text-red-800 shadow-md';
    el.innerHTML =
        '<p data-discover-map-error-text class="font-semibold"></p>' +
        '<button type="button" data-discover-map-retry ' +
        'class="mt-2 inline-flex rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:brightness-105">' +
        'Retry</button>';
    root.appendChild(el);
    return el;
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function aggIcon(L, count, selected) {
    const n = Number(count) || 0;
    const large = n >= 10 ? ' discover-agg-marker--lg' : '';
    const sel = selected ? ' discover-agg-marker--selected' : '';
    return L.divIcon({
        className: 'discover-marker-icon',
        html: `<div class="discover-agg-marker${large}${sel}" aria-hidden="true">${escapeHtml(String(n))}</div>`,
        iconSize: [n >= 10 ? 42 : 36, n >= 10 ? 42 : 36],
        iconAnchor: [n >= 10 ? 21 : 18, n >= 10 ? 21 : 18],
    });
}

function cityClusterIcon(L, count) {
    return L.divIcon({
        className: 'discover-marker-icon',
        html: `<div class="discover-city-cluster-marker" aria-hidden="true">${escapeHtml(String(count))}</div>`,
        iconSize: [44, 44],
        iconAnchor: [22, 22],
    });
}

function clusterIcon(L, count) {
    return L.divIcon({
        className: 'discover-marker-icon',
        html: `<div class="discover-cluster-marker" aria-hidden="true">${escapeHtml(String(count))}</div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });
}

function exactIcon(L, selected) {
    const cls = selected ? 'discover-exact-marker discover-exact-marker--selected' : 'discover-exact-marker';
    return L.divIcon({
        className: 'discover-marker-icon',
        html:
            `<div class="${cls}" aria-hidden="true">` +
            `<svg viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">` +
            `<path d="M14 1C7.4 1 2 6.4 2 13c0 8.4 10.2 20.4 11.1 21.4a1.2 1.2 0 0 0 1.8 0C15.8 33.4 26 21.4 26 13 26 6.4 20.6 1 14 1z" fill="#FF762E" stroke="#fff" stroke-width="2"/>` +
            `<circle cx="14" cy="13" r="4.2" fill="#fff"/>` +
            `</svg></div>`,
        iconSize: [28, 36],
        iconAnchor: [14, 34],
        popupAnchor: [0, -30],
    });
}

function i18n(key, fallback) {
    const el = document.querySelector('[data-discover-map]');
    return el?.getAttribute(`data-discover-i18n-${key}`) || fallback;
}

export async function initDiscoverMap() {
    const root = document.querySelector('[data-discover-map]');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    ensureMarkerStyles();
    const L = await loadLeaflet();
    const mapEl = root.querySelector('[data-discover-map-canvas]');
    const searchBtn = root.querySelector('[data-discover-map-search-area]');
    const list = document.querySelector('[data-discover-map-list]');
    const resultsHost = document.querySelector('#discover-results');
    const errorUi = ensureErrorUi(root);
    const errorText = errorUi.querySelector('[data-discover-map-error-text]');
    const retryBtn = errorUi.querySelector('[data-discover-map-retry]');
    if (!mapEl) return;

    const state = loadDiscoveryState();
    const zoomForRadius = (km) => {
        const n = Number(km);
        if (!Number.isFinite(n) || n <= 0) return 6;
        if (n <= 5) return 13;
        if (n <= 10) return 12;
        if (n <= 25) return 11;
        if (n <= 50) return 10;
        if (n <= 100) return 9;
        return 8;
    };
    const radiusBounds = (lat, lng, km) => {
        const pad = Math.max(1, Number(km) || 25) * 1.15;
        const latPad = pad / 111;
        const lngPad = pad / (111 * Math.max(0.2, Math.cos((lat * Math.PI) / 180)));
        return [
            [lat - latPad, lng - lngPad],
            [lat + latPad, lng + lngPad],
        ];
    };
    const startCenter =
        state.lat != null && state.lng != null ? [state.lat, state.lng] : [26.8, 30.5];
    const startZoom =
        state.lat != null && state.mode !== 'nationwide'
            ? zoomForRadius(state.distanceKm ?? 25)
            : 6;

    const map = L.map(mapEl, {
        center: startCenter,
        zoom: startZoom,
        minZoom: 5,
        maxZoom: 17,
        maxBounds: EGYPT_MAX_BOUNDS,
        maxBoundsViscosity: 0.65,
    });

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const fitEgypt = () => {
        map.invalidateSize({ animate: false });
        map.fitBounds(EGYPT_FOCUS, { padding: [36, 36], maxZoom: 7 });
    };
    const fitOrigin = (s) => {
        if (s.lat == null || s.lng == null) return;
        map.invalidateSize({ animate: false });
        const km = Number(s.distanceKm ?? 25);
        if (km > 0) {
            map.fitBounds(radiusBounds(s.lat, s.lng, km), { padding: [28, 28], maxZoom: zoomForRadius(km) });
        } else {
            map.setView([s.lat, s.lng], 11);
        }
    };
    if (state.lat == null || state.lng == null || state.mode === 'nationwide') {
        requestAnimationFrame(() => {
            fitEgypt();
            setTimeout(fitEgypt, 120);
        });
    } else {
        requestAnimationFrame(() => {
            fitOrigin(state);
            setTimeout(() => fitOrigin(state), 120);
        });
    }

    const layer = L.layerGroup().addTo(map);
    const userLayer = L.layerGroup().addTo(map);
    let radiusLayer = null;
    let abort = null;
    let cityAbort = null;
    let requestSeq = 0;
    let moved = false;
    let selectedId = null;
    let selectedCityId = null;
    const markerById = new Map();
    const aggregateByCityId = new Map();

    let cityPanel = list?.querySelector('[data-discover-city-panel]');
    if (list && !cityPanel) {
        cityPanel = document.createElement('div');
        cityPanel.setAttribute('data-discover-city-panel', '');
        cityPanel.className = 'discover-city-panel';
        cityPanel.hidden = true;
        const headingRow = list.querySelector('[data-discover-results-heading]')?.parentElement;
        if (headingRow) {
            headingRow.insertAdjacentElement('afterend', cityPanel);
        } else {
            list.prepend(cityPanel);
        }
    }

    const showError = (msg) => {
        if (errorText) errorText.textContent = msg || 'Could not load map events.';
        errorUi.hidden = false;
    };
    const hideError = () => {
        errorUi.hidden = true;
    };

    const paintUser = () => {
        userLayer.clearLayers();
        if (radiusLayer) {
            map.removeLayer(radiusLayer);
            radiusLayer = null;
        }
        const s = loadDiscoveryState();
        if ((s.mode !== 'device' && s.mode !== 'manual') || s.lat == null || s.lng == null) return;
        const km = Number(s.distanceKm ?? 0);
        if (km > 0) {
            radiusLayer = L.circle([s.lat, s.lng], {
                radius: km * 1000,
                color: '#6366f1',
                weight: 1.5,
                dashArray: '6 6',
                fillColor: '#818cf8',
                fillOpacity: 0.06,
            }).addTo(map);
        }
        if (s.mode === 'device' && s.accuracyM != null && s.accuracyM > 0 && s.accuracyM < 5000) {
            L.circle([s.lat, s.lng], {
                radius: s.accuracyM,
                color: '#3b82f6',
                weight: 1,
                fillColor: '#3b82f6',
                fillOpacity: 0.12,
            }).addTo(userLayer);
        }
        L.circleMarker([s.lat, s.lng], {
            radius: 9,
            color: '#fff',
            weight: 3,
            fillColor: '#2563eb',
            fillOpacity: 1,
        })
            .bindTooltip(s.shortLabel || s.locationLabel || s.cityName || 'You', {
                direction: 'top',
            })
            .addTo(userLayer);
    };

    const highlightCard = (id) => {
        selectedId = id;
        document.querySelectorAll('[data-discover-result-card]').forEach((el) => {
            const on = String(el.dataset.eventId) === String(id);
            el.classList.toggle('ring-2', on);
            el.classList.toggle('ring-indigo-500', on);
            if (on) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        });
        markerById.forEach((marker, mid) => {
            if (marker.setIcon) {
                marker.setIcon(exactIcon(L, String(mid) === String(id)));
            }
        });
    };

    const highlightAggregate = (cityId) => {
        selectedCityId = cityId != null ? String(cityId) : null;
        aggregateByCityId.forEach((marker, cid) => {
            const data = marker.__aggData;
            if (!data || data.type === 'city_cluster') return;
            if (marker.setIcon) {
                marker.setIcon(aggIcon(L, data.count, String(cid) === selectedCityId));
            }
        });
    };

    const clearCityPanel = () => {
        if (!cityPanel) return;
        cityPanel.hidden = true;
        cityPanel.innerHTML = '';
        highlightAggregate(null);
    };

    const openCityPanel = (cityName, events, cityId) => {
        if (!cityPanel) return;
        const unavailable = i18n(
            'exact-unavailable',
            'Exact venue location unavailable — city-level only'
        );
        const showAll = i18n('show-all', 'Show all results');
        const heading = i18n('city-heading', 'Events in :city').replace(':city', cityName || '');
        const rows = (events || [])
            .map((ev) => {
                const note = ev.has_exact_coordinates
                    ? ''
                    : `<span class="meta">${escapeHtml(unavailable)}</span>`;
                return (
                    `<li><button type="button" data-city-panel-event="${ev.id}" ` +
                    `data-geo="${ev.has_exact_coordinates ? 'exact' : 'city'}" ` +
                    `${ev.latitude != null ? `data-lat="${ev.latitude}" data-lng="${ev.longitude}"` : ''} ` +
                    `data-city-id="${cityId}">` +
                    `<span>${escapeHtml(ev.title || '')}</span>${note}</button></li>`
                );
            })
            .join('');
        cityPanel.innerHTML =
            `<h3>${escapeHtml(heading)} (${events.length})</h3>` +
            `<p>${escapeHtml(i18n('city-level', 'City-level location (not an exact venue pin)'))}</p>` +
            `<ul>${rows || `<li class="meta" style="padding:0.4rem 0">No events</li>`}</ul>` +
            `<button type="button" data-city-panel-clear>${escapeHtml(showAll)}</button>`;
        cityPanel.hidden = false;
        highlightAggregate(cityId);
    };

    const fetchCityEvents = async (cityId, focusEventId) => {
        if (cityAbort) cityAbort.abort();
        cityAbort = new AbortController();
        const params = new URLSearchParams({ city_id: String(cityId) });
        const cat = document.querySelector('#category_id')?.value;
        const q = document.querySelector('#q')?.value;
        if (cat) params.set('category_id', cat);
        if (q) params.set('q', q);
        try {
            const res = await fetch(`/api/discovery/city-events?${params}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: cityAbort.signal,
            });
            if (!res.ok) {
                showError('Could not load city events.');
                return;
            }
            const payload = await res.json();
            openCityPanel(payload.city?.name || '', payload.events || [], cityId);
            if (focusEventId) highlightCard(focusEventId);
        } catch (e) {
            if (e?.name === 'AbortError') return;
            showError('Could not load city events.');
        }
    };

    const selectExactOnMap = (id, lat, lng) => {
        highlightCard(id);
        const marker = markerById.get(String(id));
        const target = marker
            ? marker.getLatLng()
            : lat != null && lng != null
              ? L.latLng(Number(lat), Number(lng))
              : null;
        if (!target) {
            showError(i18n('not-on-map', 'This event has no map location yet'));
            return;
        }
        hideError();
        map.setView(target, Math.max(map.getZoom(), 14), { animate: true });
        if (marker) {
            marker.setIcon(exactIcon(L, true));
            marker.openPopup();
        } else {
            // Marker may be outside current layer (paginated / zoom) — temporary pin
            const temp = L.marker(target, {
                icon: exactIcon(L, true),
                zIndexOffset: 500,
            })
                .bindPopup(`Event #${escapeHtml(String(id))}`)
                .addTo(layer);
            temp.openPopup();
            markerById.set(String(id), temp);
        }
    };

    const selectCityOnMap = (cityId, cityLat, cityLng, cityName, eventId) => {
        hideError();
        highlightCard(eventId);
        const agg = aggregateByCityId.get(String(cityId));
        const lat = agg ? agg.getLatLng().lat : Number(cityLat);
        const lng = agg ? agg.getLatLng().lng : Number(cityLng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            showError(i18n('not-on-map', 'This event has no map location yet'));
            return;
        }
        map.setView([lat, lng], Math.max(map.getZoom(), 11), { animate: true });
        if (agg) {
            agg.openPopup();
        }
        fetchCityEvents(cityId, eventId);
    };

    const selectFromCardEl = (el) => {
        if (!el) return;
        const id = el.dataset.eventId;
        const geo = el.dataset.geo || 'none';
        if (geo === 'exact') {
            clearCityPanel();
            selectExactOnMap(id, el.dataset.lat, el.dataset.lng);
            return;
        }
        if (geo === 'city' && el.dataset.cityId) {
            selectCityOnMap(
                el.dataset.cityId,
                el.dataset.cityLat,
                el.dataset.cityLng,
                el.dataset.cityName,
                id
            );
            return;
        }
        highlightCard(id);
        showError(i18n('not-on-map', 'This event has no map location yet'));
    };

    const fetchArea = async () => {
        const b = map.getBounds();
        const zoom = map.getZoom();
        if (abort) abort.abort();
        abort = new AbortController();
        const seq = ++requestSeq;
        searchBtn && (searchBtn.hidden = true);
        moved = false;
        hideError();
        const params = new URLSearchParams({
            north: String(b.getNorth()),
            south: String(b.getSouth()),
            east: String(b.getEast()),
            west: String(b.getWest()),
            zoom: String(Math.round(zoom)),
            include_approximate: '0',
        });
        const cat = document.querySelector('#category_id')?.value;
        const q = document.querySelector('#q')?.value;
        if (cat) params.set('category_id', cat);
        if (q) params.set('q', q);
        const disc = loadDiscoveryState();
        if (
            (disc.mode === 'device' || disc.mode === 'manual') &&
            disc.lat != null &&
            disc.lng != null
        ) {
            params.set('discovery_mode', disc.mode);
            params.set('latitude', String(disc.lat));
            params.set('longitude', String(disc.lng));
            params.set('distance_km', String(disc.distanceKm ?? 25));
        }
        try {
            const res = await fetch(`/api/discovery/map?${params}`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: abort.signal,
            });
            if (seq !== requestSeq) return;
            if (!res.ok) {
                let detail = '';
                try {
                    const errBody = await res.json();
                    detail = errBody?.message || errBody?.errors?.north?.[0] || '';
                } catch {
                    /* ignore */
                }
                if (res.status === 422) {
                    showError(
                        detail ||
                            'This map area is too large. Zoom in, then tap Search this area — or Retry.'
                    );
                } else if (res.status >= 500) {
                    showError('Map server error. Tap Retry.');
                } else {
                    showError(`Could not load map events (${res.status}). Tap Retry.`);
                }
                return;
            }
            const payload = await res.json();
            if (seq !== requestSeq) return;
            layer.clearLayers();
            markerById.clear();
            aggregateByCityId.clear();

            const aggregates = payload.city_aggregates || [];
            const items = [...(payload.clusters || []), ...(payload.markers || [])];

            aggregates.forEach((item) => {
                if (item.type === 'city_cluster') {
                    const marker = L.marker([item.latitude, item.longitude], {
                        icon: cityClusterIcon(L, item.count),
                        zIndexOffset: 180,
                        keyboard: true,
                        title: item.label || `${item.count} city-level events`,
                    });
                    marker.__aggData = item;
                    marker.bindTooltip(item.label || `${item.count} events in nearby cities`, {
                        direction: 'top',
                        opacity: 0.95,
                    });
                    marker.on('click', () => {
                        map.setView(
                            [item.latitude, item.longitude],
                            item.expansion_zoom || zoom + 2
                        );
                    });
                    marker.addTo(layer);
                    return;
                }

                const selected =
                    selectedCityId != null && String(selectedCityId) === String(item.city_id);
                const marker = L.marker([item.latitude, item.longitude], {
                    icon: aggIcon(L, item.count, selected),
                    zIndexOffset: 200,
                    keyboard: true,
                    title: item.label || `${item.city_name} · ${item.count}`,
                });
                marker.__aggData = item;
                marker.bindTooltip(
                    `${item.count} events in ${item.city_name || 'city'} (city-level)`,
                    { direction: 'top', opacity: 0.95 }
                );
                marker.bindPopup(
                    `<strong>${escapeHtml(item.city_name || '')}</strong><br>` +
                        `${item.count} city-level events<br>` +
                        `<em>${escapeHtml(i18n('city-level', 'City-level location (not an exact venue pin)'))}</em>`
                );
                marker.on('click', () => {
                    fetchCityEvents(item.city_id);
                    map.setView([item.latitude, item.longitude], Math.max(map.getZoom(), 11));
                });
                marker.addTo(layer);
                if (item.city_id != null) {
                    aggregateByCityId.set(String(item.city_id), marker);
                }
            });

            items.forEach((item) => {
                if (item.type === 'cluster' || (item.count && item.count > 1 && !item.title)) {
                    const marker = L.marker([item.latitude, item.longitude], {
                        icon: clusterIcon(L, item.count),
                        zIndexOffset: 150,
                        keyboard: true,
                        title: `${item.count} events`,
                    });
                    marker.on('click', () => {
                        map.setView(
                            [item.latitude, item.longitude],
                            item.expansion_zoom || zoom + 2
                        );
                    });
                    marker.addTo(layer);
                    return;
                }
                const selected = selectedId != null && String(selectedId) === String(item.id);
                const marker = L.marker([item.latitude, item.longitude], {
                    icon: exactIcon(L, selected),
                    zIndexOffset: 300,
                    keyboard: true,
                    title: item.title || 'Event',
                });
                marker.bindPopup(
                    `<strong>${escapeHtml(item.title || '')}</strong><br>` +
                        `<span>${escapeHtml(item.venue_label || '')}</span><br>` +
                        `<a href="/events/${item.id}">View details</a>`
                );
                marker.on('click', () => {
                    clearCityPanel();
                    highlightCard(item.id);
                    marker.openPopup();
                    map.setView(marker.getLatLng(), Math.max(map.getZoom(), 14));
                });
                marker.addTo(layer);
                if (item.id != null) markerById.set(String(item.id), marker);
            });
            paintUser();

            const meta = payload.meta || {};
            if (meta.is_suggestion && meta.effective_radius_km != null) {
                const discFit = loadDiscoveryState();
                if (discFit.lat != null && discFit.lng != null) {
                    // Frame suggestions without changing the user's selected radius chip/circle.
                    fitOrigin({
                        ...discFit,
                        distanceKm: Number(meta.effective_radius_km) || discFit.distanceKm,
                    });
                }
            }

            if (aggregates.length === 0 && items.length === 0) {
                const discEmpty = loadDiscoveryState();
                if (
                    (discEmpty.mode === 'device' || discEmpty.mode === 'manual') &&
                    Number(discEmpty.distanceKm) > 0
                ) {
                    showError(
                        i18n('no-events-radius', 'No map events within :km km of your location.')
                            .replace(':km', String(discEmpty.distanceKm))
                    );
                } else {
                    showError(
                        i18n(
                            'no-events-viewport',
                            'No map events in this view. Zoom out or Search this area.'
                        )
                    );
                }
            }
        } catch (e) {
            if (e?.name === 'AbortError') return;
            if (seq !== requestSeq) return;
            showError('Network error loading map events. Check connection and tap Retry.');
            console.warn('discover map', e);
        }
    };

    map.on('moveend', () => {
        if (!moved) {
            moved = true;
            if (searchBtn) searchBtn.hidden = false;
        }
    });

    searchBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        fetchArea();
    });
    retryBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        fetchArea();
    });

    // Event delegation — survives live-search fragment swaps.
    document.addEventListener('click', (e) => {
        const clearBtn = e.target.closest('[data-city-panel-clear]');
        if (clearBtn) {
            e.preventDefault();
            clearCityPanel();
            return;
        }
        const panelEv = e.target.closest('[data-city-panel-event]');
        if (panelEv) {
            e.preventDefault();
            const id = panelEv.getAttribute('data-city-panel-event');
            if (panelEv.dataset.geo === 'exact') {
                selectExactOnMap(id, panelEv.dataset.lat, panelEv.dataset.lng);
            } else {
                highlightCard(id);
                const agg = aggregateByCityId.get(String(panelEv.dataset.cityId));
                if (agg) agg.openPopup();
            }
            return;
        }
        const card = e.target.closest('[data-discover-result-card]');
        if (!card || !list?.contains(card)) return;
        const link = e.target.closest('a[href]');
        // Keep "View details" / external navigation; title taps select on map.
        if (link && /\/events\/\d+/.test(link.getAttribute('href') || '')) {
            const isDetails =
                link.textContent &&
                /view details|عرض التفاصيل/i.test(link.textContent.trim());
            if (isDetails) return;
            e.preventDefault();
        } else if (link) {
            return;
        }
        selectFromCardEl(card);
    });

    document.addEventListener('funnity:discovery-location', () => {
        paintUser();
        const s = loadDiscoveryState();
        if (s.lat != null && s.lng != null && (s.mode === 'device' || s.mode === 'manual')) {
            fitOrigin(s);
            setTimeout(fetchArea, 160);
        } else if (s.mode === 'nationwide') {
            fitEgypt();
            setTimeout(fetchArea, 160);
        }
    });

    paintUser();
    const boot = () => {
        map.invalidateSize();
        const s = loadDiscoveryState();
        if (s.lat != null && s.lng != null && s.mode !== 'nationwide') {
            fitOrigin(s);
        }
        fetchArea();
    };
    requestAnimationFrame(() => {
        setTimeout(boot, 80);
    });
}
