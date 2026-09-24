/**
 * Free place picker: OpenStreetMap tiles via Leaflet CDN (no Google API key).
 * Paste Google Maps / OSM share links — coords are parsed client-side when possible.
 */
const LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
const LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';

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

function parseMapsUrl(raw) {
    if (!raw || typeof raw !== 'string') return null;
    let url = raw.trim();
    if (!url) return null;
    if (!/^https?:\/\//i.test(url)) url = `https://${url}`;

    const patterns = [
        /@(-?\d+\.?\d*),(-?\d+\.?\d*)(?:,|\/|$)/,
        /!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)/,
        /[?&](?:q|query)=(?:loc:)?(-?\d+\.?\d*),(-?\d+\.?\d*)/,
        /[?&](?:ll|center)=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
        /[?&]destination=(-?\d+\.?\d*),(-?\d+\.?\d*)/,
        /[?&]mlat=(-?\d+\.?\d*).*?[?&]mlon=(-?\d+\.?\d*)/,
        /#map=\d+\/(-?\d+\.?\d*)\/(-?\d+\.?\d*)/,
        /(-?\d{1,2}\.\d{3,}),\s*(-?\d{1,3}\.\d{3,})/,
    ];

    for (const pattern of patterns) {
        const m = url.match(pattern);
        if (!m) continue;
        const lat = Number(m[1]);
        const lng = Number(m[2]);
        if (Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            return { lat, lng };
        }
    }
    return null;
}

function osmUrl(lat, lng) {
    return `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=16/${lat}/${lng}`;
}

function cityCentersFromSelect(select) {
    const map = {};
    if (!select) return map;
    for (const opt of select.options) {
        const lat = opt.dataset.lat;
        const lng = opt.dataset.lng;
        if (lat && lng) {
            map[opt.value] = { lat: Number(lat), lng: Number(lng) };
        }
    }
    return map;
}

async function initPicker(root) {
    const mapEl = root.querySelector('[data-place-map]');
    const latInput = root.querySelector('[data-place-lat]');
    const lngInput = root.querySelector('[data-place-lng]');
    const urlInput = root.querySelector('[data-place-url]');
    const statusEl = root.querySelector('[data-place-status]');
    const clearBtn = root.querySelector('[data-place-clear]');
    const citySelect = document.getElementById('city_id');
    if (!mapEl || !latInput || !lngInput) return;

    loadCss(LEAFLET_CSS);
    const L = await loadScript(LEAFLET_JS);
    if (!L) return;

    const centers = cityCentersFromSelect(citySelect);
    const defaultLat = Number(latInput.value) || Number(root.dataset.defaultLat) || 30.0444;
    const defaultLng = Number(lngInput.value) || Number(root.dataset.defaultLng) || 31.2357;
    const hasPin = latInput.value !== '' && lngInput.value !== '';

    const map = L.map(mapEl, { scrollWheelZoom: true }).setView([defaultLat, defaultLng], hasPin ? 15 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    let marker = null;

    const setStatus = (text) => {
        if (statusEl) statusEl.textContent = text || '';
    };

    const placeMarker = (lat, lng, { writeUrl = true, zoom = true } = {}) => {
        latInput.value = Number(lat).toFixed(7);
        lngInput.value = Number(lng).toFixed(7);
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                const p = marker.getLatLng();
                placeMarker(p.lat, p.lng, { writeUrl: true, zoom: false });
            });
        }
        if (zoom) map.setView([lat, lng], Math.max(map.getZoom(), 15));
        // Pin is source of truth — always keep maps_url in sync with the marker.
        if (writeUrl && urlInput) {
            urlInput.value = osmUrl(lat, lng);
        }
        setStatus(root.dataset.statusPinned || '');
    };

    if (hasPin) {
        placeMarker(Number(latInput.value), Number(lngInput.value), { writeUrl: false, zoom: true });
    }

    map.on('click', (e) => {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });

    const applyUrl = async () => {
        const raw = urlInput?.value || '';
        const parsed = parseMapsUrl(raw);
        if (parsed) {
            placeMarker(parsed.lat, parsed.lng, { writeUrl: false });
            setStatus(root.dataset.statusFromLink || '');
            return;
        }

        if (!raw.trim() || !/^https?:\/\//i.test(raw.trim()) && !/maps\.|goo\.gl|openstreetmap/i.test(raw)) {
            return;
        }

        setStatus(root.dataset.statusResolving || '…');
        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/api/maps/resolve', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                body: JSON.stringify({ url: raw.trim() }),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok || !body?.data) {
                setStatus(root.dataset.statusUnresolved || '');
                return;
            }
            placeMarker(body.data.latitude, body.data.longitude, { writeUrl: false });
            setStatus(root.dataset.statusFromLink || '');
        } catch {
            setStatus(root.dataset.statusUnresolved || '');
        }
    };

    urlInput?.addEventListener('change', applyUrl);
    urlInput?.addEventListener('paste', () => setTimeout(applyUrl, 50));

    clearBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        latInput.value = '';
        lngInput.value = '';
        if (urlInput) urlInput.value = '';
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        setStatus(root.dataset.statusCleared || '');
    });

    citySelect?.addEventListener('change', () => {
        if (latInput.value && lngInput.value) return;
        const center = centers[citySelect.value];
        if (center) map.setView([center.lat, center.lng], 12);
    });

    // Leaflet needs a invalidate after layout.
    setTimeout(() => map.invalidateSize(), 100);
}

async function initViewer(root) {
    const mapEl = root.querySelector('[data-place-map]');
    const lat = Number(root.dataset.lat);
    const lng = Number(root.dataset.lng);
    if (!mapEl || !Number.isFinite(lat) || !Number.isFinite(lng)) return;

    loadCss(LEAFLET_CSS);
    const L = await loadScript(LEAFLET_JS);
    if (!L) return;

    const map = L.map(mapEl, { scrollWheelZoom: false }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
    setTimeout(() => map.invalidateSize(), 100);
}

export function initEventPlaceMaps() {
    document.querySelectorAll('[data-event-place-picker]').forEach((el) => {
        initPicker(el).catch(() => {});
    });
    document.querySelectorAll('[data-event-place-viewer]').forEach((el) => {
        initViewer(el).catch(() => {});
    });
}
