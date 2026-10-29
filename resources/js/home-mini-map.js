/**
 * Home dashboard "Explore Around You" — compact Leaflet preview.
 * Reuses /api/discovery/map; fails soft (section stays usable without map).
 */
async function initHomeMiniMap(root = document.querySelector('[data-home-mini-map]')) {
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1';

    const canvas = root.querySelector('[data-home-mini-map-canvas]');
    const status = root.querySelector('[data-home-mini-map-status]');
    if (!canvas) return;

    const lat = Number(root.dataset.lat);
    const lng = Number(root.dataset.lng);
    const km = Number(root.dataset.distanceKm || 25);
    const mode = root.dataset.discoveryMode || 'nationwide';
    const hasOrigin = Number.isFinite(lat) && Number.isFinite(lng) && mode !== 'nationwide';

    const setStatus = (msg) => {
        if (status) status.textContent = msg || '';
    };

    try {
        if (!window.L) {
            await new Promise((resolve, reject) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                document.head.appendChild(link);
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        const map = window.L.map(canvas, {
            zoomControl: false,
            attributionControl: false,
            scrollWheelZoom: false,
        });
        window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
        }).addTo(map);

        if (hasOrigin) {
            const pad = Math.max(km, 25) / 111;
            map.fitBounds([
                [lat - pad, lng - pad],
                [lat + pad, lng + pad],
            ]);
            window.L.circleMarker([lat, lng], {
                radius: 7,
                color: '#fff',
                weight: 2,
                fillColor: '#2563EB',
                fillOpacity: 1,
            }).addTo(map);
        } else {
            map.fitBounds([
                [22.0, 24.9],
                [31.75, 35.0],
            ]);
        }

        const b = map.getBounds();
        const params = new URLSearchParams({
            north: String(b.getNorth()),
            south: String(b.getSouth()),
            east: String(b.getEast()),
            west: String(b.getWest()),
            zoom: String(Math.round(map.getZoom())),
            include_approximate: '0',
            limit: '40',
        });
        if (hasOrigin) {
            params.set('discovery_mode', mode === 'manual' ? 'manual' : 'device');
            params.set('latitude', String(lat));
            params.set('longitude', String(lng));
            params.set('distance_km', String(km > 0 ? km : 25));
        }

        const res = await fetch(`/api/discovery/map?${params}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!res.ok) {
            setStatus(root.dataset.i18nMapError || 'Map unavailable');
            return;
        }
        const payload = await res.json();
        const layer = window.L.layerGroup().addTo(map);
        let count = 0;
        (payload.city_aggregates || []).slice(0, 12).forEach((item) => {
            count += 1;
            window.L.circleMarker([item.latitude, item.longitude], {
                radius: 10,
                color: '#7138F5',
                weight: 2,
                fillColor: '#C9B6FF',
                fillOpacity: 0.9,
            })
                .bindTooltip(item.label || `${item.count} events`)
                .addTo(layer);
        });
        (payload.markers || []).slice(0, 20).forEach((item) => {
            count += 1;
            window.L.circleMarker([item.latitude, item.longitude], {
                radius: 6,
                color: '#fff',
                weight: 1,
                fillColor: '#FF762E',
                fillOpacity: 1,
            })
                .bindTooltip(item.title || 'Event')
                .addTo(layer);
        });
        if (count === 0) {
            setStatus(root.dataset.i18nEmpty || 'No map events nearby');
        }
        requestAnimationFrame(() => map.invalidateSize());
    } catch (e) {
        console.warn('home mini map', e);
        setStatus(root.dataset.i18nMapError || 'Map unavailable');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Lazy: only when section is near viewport.
        const root = document.querySelector('[data-home-mini-map]');
        if (!root) return;
        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver(
                (entries) => {
                    if (entries.some((e) => e.isIntersecting)) {
                        io.disconnect();
                        initHomeMiniMap(root);
                    }
                },
                { rootMargin: '120px' }
            );
            io.observe(root);
        } else {
            initHomeMiniMap(root);
        }
    });
}

export { initHomeMiniMap };
