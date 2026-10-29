/**
 * Home "Explore Around You" — compact Leaflet preview (Explore V4 API).
 * Vite modules run after parse (readyState !== loading); always schedule init.
 */
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
const OSM_TILES = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

const EGYPT_FOCUS = [
    [22.2, 25.0],
    [31.65, 34.9],
];

function loadCss(href) {
    if (document.querySelector(`link[href="${href}"]`)) return;
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        if (window.L) {
            resolve(window.L);
            return;
        }
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            existing.addEventListener('load', () => resolve(window.L));
            existing.addEventListener('error', reject);
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.async = true;
        script.onload = () => resolve(window.L);
        script.onerror = reject;
        document.head.appendChild(script);
    });
}

function ensureLeafletCssPatch() {
    if (document.getElementById('home-mini-map-leaflet-css')) return;
    const style = document.createElement('style');
    style.id = 'home-mini-map-leaflet-css';
    style.textContent = `
[data-home-mini-map-canvas].leaflet-container{width:100%;height:100%;background:#e8eaf2}
[data-home-mini-map-canvas] .leaflet-tile-pane{opacity:1}
.home-mini-map__overlay{position:absolute;inset:0;z-index:500;display:flex;align-items:center;justify-content:center;padding:1rem;text-align:center;background:rgba(248,249,254,.88);backdrop-filter:blur(2px)}
.home-mini-map__overlay[hidden]{display:none!important}
.home-mini-map__overlay-card{max-width:16rem;border-radius:1rem;border:1px solid #e2e8f0;background:#fff;padding:.85rem 1rem;box-shadow:0 10px 30px -18px rgba(25,33,61,.45)}
.home-mini-map__overlay-card p{margin:0;font-size:.75rem;font-weight:600;color:#475569}
.home-mini-map__overlay-card button{margin-top:.55rem;border:0;border-radius:.65rem;background:#7138F5;color:#fff;font-size:.7rem;font-weight:800;padding:.4rem .75rem;cursor:pointer}
.home-mini-map__agg{width:34px;height:34px;border-radius:9999px;background:#7138F5;color:#fff;border:2px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.28);display:flex;align-items:center;justify-content:center;font:800 11px/1 ui-sans-serif,system-ui,sans-serif}
`;
    document.head.appendChild(style);
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

function radiusBounds(lat, lng, km) {
    const pad = Math.max(1, Number(km) || 25) * 1.15;
    const latPad = pad / 111;
    const lngPad = pad / (111 * Math.max(0.2, Math.cos((lat * Math.PI) / 180)));
    return [
        [lat - latPad, lng - lngPad],
        [lat + latPad, lng + lngPad],
    ];
}

function showOverlay(root, kind, message) {
    const host = root.querySelector('[data-home-mini-map-stage]');
    if (!host) return;
    let overlay = host.querySelector('[data-home-mini-map-overlay]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'home-mini-map__overlay';
        overlay.dataset.homeMiniMapOverlay = '';
        overlay.innerHTML = `<div class="home-mini-map__overlay-card"><p data-overlay-text></p><button type="button" data-overlay-retry hidden>Retry</button></div>`;
        host.appendChild(overlay);
    }
    overlay.hidden = false;
    overlay.querySelector('[data-overlay-text]').textContent = message || '';
    const retry = overlay.querySelector('[data-overlay-retry]');
    retry.hidden = kind !== 'error';
    retry.textContent = root.dataset.i18nRetry || 'Retry';
    return overlay;
}

function hideOverlay(root) {
    const overlay = root.querySelector('[data-home-mini-map-overlay]');
    if (overlay) overlay.hidden = true;
}

async function initHomeMiniMap(root = document.querySelector('[data-home-mini-map]')) {
    if (!root || root.dataset.booting === '1') return;
    if (root.dataset.ready === '1' && root._homeMiniMap) return;

    root.dataset.booting = '1';
    const canvas = root.querySelector('[data-home-mini-map-canvas]');
    const status = root.querySelector('[data-home-mini-map-status]');
    if (!canvas) {
        root.dataset.booting = '0';
        return;
    }

    const setStatus = (msg) => {
        if (status) status.textContent = msg || '';
    };

    const lat = Number(root.dataset.lat);
    const lng = Number(root.dataset.lng);
    const km = Number(root.dataset.distanceKm || 25);
    const mode = root.dataset.discoveryMode || 'nationwide';
    const hasOrigin =
        Number.isFinite(lat) &&
        Number.isFinite(lng) &&
        mode !== 'nationwide' &&
        Math.abs(lat) <= 90 &&
        Math.abs(lng) <= 180;

    showOverlay(root, 'loading', root.dataset.i18nLoading || 'Loading map…');

    try {
        loadCss(LEAFLET_CSS);
        ensureLeafletCssPatch();
        const L = await loadScript(LEAFLET_JS);
        if (!L?.map) throw new Error('Leaflet unavailable');

        if (root._homeMiniMap) {
            try {
                root._homeMiniMap.remove();
            } catch {
                /* ignore */
            }
            root._homeMiniMap = null;
            canvas.innerHTML = '';
        }

        const map = L.map(canvas, {
            zoomControl: false,
            attributionControl: true,
            scrollWheelZoom: false,
            dragging: true,
            tap: true,
        });
        root._homeMiniMap = map;

        let tilesOk = false;
        let tileErrors = 0;
        const tiles = L.tileLayer(OSM_TILES, {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
            crossOrigin: true,
        });
        tiles.on('tileload', () => {
            tilesOk = true;
            hideOverlay(root);
        });
        tiles.on('tileerror', () => {
            tileErrors += 1;
            if (!tilesOk && tileErrors >= 4) {
                showOverlay(root, 'error', root.dataset.i18nTileError || 'Map tiles failed to load');
            }
        });
        tiles.addTo(map);

        const invalidate = () => {
            map.invalidateSize({ animate: false });
        };

        // Force layout before Leaflet measures the pane (zero-height → invalid bbox).
        invalidate();
        if (canvas.clientHeight < 120) {
            canvas.style.height = '230px';
            canvas.style.minHeight = '230px';
            invalidate();
        }

        if (hasOrigin) {
            map.fitBounds(radiusBounds(lat, lng, km > 0 ? km : 25), {
                padding: [18, 18],
                maxZoom: zoomForRadius(km),
            });
            L.circleMarker([lat, lng], {
                radius: 7,
                color: '#fff',
                weight: 2,
                fillColor: '#2563EB',
                fillOpacity: 1,
            })
                .bindTooltip(root.dataset.locationLabel || 'You')
                .addTo(map);
            if (km > 0) {
                L.circle([lat, lng], {
                    radius: km * 1000,
                    color: '#7138F5',
                    weight: 1,
                    opacity: 0.45,
                    fillColor: '#7138F5',
                    fillOpacity: 0.06,
                }).addTo(map);
            }
        } else {
            map.fitBounds(EGYPT_FOCUS, { padding: [24, 24], maxZoom: 7 });
        }

        await new Promise((resolve) => {
            requestAnimationFrame(() => {
                invalidate();
                setTimeout(() => {
                    invalidate();
                    resolve();
                }, 100);
            });
        });

        let b = map.getBounds();
        // Guard collapsed / degenerate viewport (north === south → API 422).
        if (!b.isValid() || b.getNorth() - b.getSouth() < 0.05) {
            map.fitBounds(EGYPT_FOCUS, { padding: [24, 24], maxZoom: 7 });
            invalidate();
            b = map.getBounds();
        }
        if (!b.isValid() || b.getNorth() - b.getSouth() < 0.05) {
            b = L.latLngBounds(EGYPT_FOCUS);
        }

        const params = new URLSearchParams({
            north: String(b.getNorth()),
            south: String(b.getSouth()),
            east: String(b.getEast()),
            west: String(b.getWest()),
            zoom: String(Math.max(5, Math.round(map.getZoom() || 6))),
            // City-level events become aggregates — matches nearby list geography.
            include_approximate: '1',
            limit: '40',
        });
        if (hasOrigin) {
            params.set('discovery_mode', mode === 'manual' ? 'manual' : 'device');
            params.set('latitude', String(lat));
            params.set('longitude', String(lng));
            params.set('distance_km', String(km > 0 ? km : 25));
        } else {
            params.set('discovery_mode', 'nationwide');
        }

        const res = await fetch(`/api/discovery/map?${params}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            // Keep tiles visible; only status fails soft for data.
            setStatus(root.dataset.i18nMapError || 'Map unavailable');
            if (!tilesOk) {
                showOverlay(root, 'error', root.dataset.i18nMapError || 'Map unavailable');
            } else {
                hideOverlay(root);
            }
            root.dataset.ready = '1';
            root.dataset.booting = '0';
            return;
        }

        const payload = await res.json();
        const layer = L.layerGroup().addTo(map);
        let count = 0;

        (payload.city_aggregates || []).slice(0, 16).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            count += 1;
            const icon = L.divIcon({
                className: 'discover-marker-icon',
                html: `<div class="home-mini-map__agg">${Number(item.count) || 0}</div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
            });
            L.marker([item.latitude, item.longitude], { icon })
                .bindTooltip(
                    item.label ||
                        `${item.count} · ${root.dataset.i18nApprox || 'City-level'}`
                )
                .addTo(layer);
        });

        (payload.clusters || []).slice(0, 12).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            count += 1;
            const icon = L.divIcon({
                className: 'discover-marker-icon',
                html: `<div class="home-mini-map__agg">${Number(item.count) || 0}</div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
            });
            L.marker([item.latitude, item.longitude], { icon }).addTo(layer);
        });

        (payload.markers || []).slice(0, 24).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            // Never treat approximate city centroids as exact venue pins.
            if ((item.location_precision || item.precision) === 'approximate') return;
            count += 1;
            L.circleMarker([item.latitude, item.longitude], {
                radius: 6,
                color: '#fff',
                weight: 1.5,
                fillColor: '#FF762E',
                fillOpacity: 1,
            })
                .bindTooltip(item.title || 'Event')
                .addTo(layer);
        });

        if (count === 0) {
            setStatus(root.dataset.i18nEmpty || 'No map events in this view');
        } else {
            setStatus('');
        }

        if (tilesOk) hideOverlay(root);
        else {
            // Tiles may still be in flight; clear loading after a short grace.
            setTimeout(() => {
                if (tilesOk) hideOverlay(root);
                else if (tileErrors === 0) hideOverlay(root);
            }, 1200);
        }

        root.dataset.ready = '1';
    } catch (e) {
        console.warn('home mini map', e);
        setStatus(root.dataset.i18nMapError || 'Map unavailable');
        const overlay = showOverlay(
            root,
            'error',
            root.dataset.i18nMapError || 'Map unavailable'
        );
        overlay?.querySelector('[data-overlay-retry]')?.addEventListener(
            'click',
            () => {
                root.dataset.ready = '0';
                root.dataset.booting = '0';
                hideOverlay(root);
                initHomeMiniMap(root);
            },
            { once: true }
        );
    } finally {
        root.dataset.booting = '0';
    }
}

function scheduleHomeMiniMap() {
    const root = document.querySelector('[data-home-mini-map]');
    if (!root) return;

    root.addEventListener('click', (e) => {
        const retry = e.target.closest('[data-overlay-retry]');
        if (!retry) return;
        root.dataset.ready = '0';
        root.dataset.booting = '0';
        hideOverlay(root);
        initHomeMiniMap(root);
    });

    const start = () => initHomeMiniMap(root);

    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    io.disconnect();
                    start();
                }
            },
            { rootMargin: '160px' }
        );
        io.observe(root);
        // Fallback if already in view but observer is delayed.
        setTimeout(() => {
            const rect = root.getBoundingClientRect();
            if (rect.top < window.innerHeight + 160 && root.dataset.ready !== '1') {
                io.disconnect();
                start();
            }
        }, 400);
    } else {
        start();
    }
}

function boot() {
    scheduleHomeMiniMap();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

export { initHomeMiniMap };
