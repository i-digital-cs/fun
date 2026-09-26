/**
 * Free place picker: OpenStreetMap tiles via Leaflet CDN (no Google API key).
 * Paste Google Maps / OSM share links — coords are parsed client-side when possible.
 * Venue typing uses /api/places/suggest (Photon / Nominatim on the server).
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

function googleMapsUrl(lat, lng) {
    return `https://www.google.com/maps?q=${lat},${lng}`;
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

function debounce(fn, wait) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

function authHeaders() {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(token ? { 'X-CSRF-TOKEN': token } : {}),
    };
}

async function initPicker(root) {
    const mapEl = root.querySelector('[data-place-map]');
    const latInput = root.querySelector('[data-place-lat]');
    const lngInput = root.querySelector('[data-place-lng]');
    const urlInput = root.querySelector('[data-place-url]');
    const statusEl = root.querySelector('[data-place-status]');
    const clearBtn = root.querySelector('[data-place-clear]');
    const venueInput = document.getElementById('venue') || root.querySelector('[data-place-venue]');
    const suggestPanel =
        venueInput?.closest('[data-place-venue-wrap]')?.querySelector('[data-place-suggest]')
        || document.querySelector('[data-place-suggest]')
        || root.querySelector('[data-place-suggest]');
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

    const hideSuggest = () => {
        if (!suggestPanel) return;
        suggestPanel.hidden = true;
        suggestPanel.innerHTML = '';
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

        if (!raw.trim() || (!/^https?:\/\//i.test(raw.trim()) && !/maps\.|goo\.gl|openstreetmap/i.test(raw))) {
            return;
        }

        setStatus(root.dataset.statusResolving || '…');
        try {
            const res = await fetch('/api/maps/resolve', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    ...authHeaders(),
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

    const renderSuggest = (items) => {
        if (!suggestPanel) return;
        if (!items.length) {
            hideSuggest();
            return;
        }
        suggestPanel.innerHTML = items
            .map(
                (item, index) => `
            <button
                type="button"
                class="block w-full px-3 py-2.5 text-start text-sm text-slate-800 hover:bg-indigo-50"
                data-suggest-index="${index}"
            >
                <span class="font-medium">${escapeHtml(item.label)}</span>
            </button>`
            )
            .join('');
        suggestPanel.hidden = false;
        suggestPanel.querySelectorAll('[data-suggest-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const item = items[Number(btn.dataset.suggestIndex)];
                if (!item) return;
                if (venueInput) venueInput.value = item.label;
                placeMarker(item.latitude, item.longitude);
                setStatus(root.dataset.statusFromSuggest || root.dataset.statusPinned || '');
                hideSuggest();
            });
        });
    };

    const fetchSuggest = debounce(async () => {
        if (!venueInput || !suggestPanel) return;
        const q = venueInput.value.trim();
        if (q.length < 2) {
            hideSuggest();
            return;
        }
        try {
            const url = new URL('/api/places/suggest', window.location.origin);
            url.searchParams.set('q', q);
            url.searchParams.set('limit', '8');
            if (citySelect?.value) url.searchParams.set('city_id', citySelect.value);
            const res = await fetch(url.toString(), {
                credentials: 'same-origin',
                headers: authHeaders(),
            });
            const body = await res.json().catch(() => ({}));
            if (!res.ok || !Array.isArray(body?.data)) {
                hideSuggest();
                return;
            }
            renderSuggest(body.data);
        } catch {
            hideSuggest();
        }
    }, 320);

    venueInput?.addEventListener('input', fetchSuggest);
    venueInput?.addEventListener('focus', fetchSuggest);
    venueInput?.addEventListener('blur', () => {
        // Allow click on suggestion before hide.
        setTimeout(hideSuggest, 180);
    });

    clearBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        latInput.value = '';
        lngInput.value = '';
        if (urlInput) urlInput.value = '';
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        hideSuggest();
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

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

async function initViewer(root) {
    const mapEl = root.querySelector('[data-place-map]');
    const lat = Number(root.dataset.lat);
    const lng = Number(root.dataset.lng);
    if (!mapEl || !Number.isFinite(lat) || !Number.isFinite(lng)) return;

    // Clicks go to the wrapping <a href="Google Maps"> — Leaflet must not capture them.
    mapEl.style.pointerEvents = 'none';

    loadCss(LEAFLET_CSS);
    const L = await loadScript(LEAFLET_JS);
    if (!L) return;

    const map = L.map(mapEl, {
        scrollWheelZoom: false,
        dragging: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        tap: false,
        touchZoom: false,
        zoomControl: false,
    }).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);
    L.marker([lat, lng], { interactive: false }).addTo(map);
    setTimeout(() => map.invalidateSize(), 100);

    // Fallback if root is not an <a> (older markup).
    if (root.tagName !== 'A') {
        const openUrl = root.dataset.mapsOpenUrl || googleMapsUrl(lat, lng);
        root.style.cursor = 'pointer';
        root.addEventListener('click', (event) => {
            if (event.target.closest('a')) return;
            event.preventDefault();
            window.open(openUrl, '_blank', 'noopener,noreferrer');
        });
    }
}

export function initEventPlaceMaps() {
    document.querySelectorAll('[data-event-place-picker]').forEach((el) => {
        initPicker(el).catch(() => {});
    });
    document.querySelectorAll('[data-event-place-viewer]').forEach((el) => {
        initViewer(el).catch(() => {});
    });
}
