/**
 * Living Map V3 — prototype only (preview route).
 * Does NOT wire into production Home / Discover.
 * MapLibre GL + OpenFreeMap vector style + Explore V4 API.
 */

const EGYPT = { center: [30.8, 26.8], zoom: 5.4, pitch: 38, bearing: -12 };
const EGYPT_BOUNDS = [
    [24.5, 21.5],
    [35.5, 32.2],
];

const CATEGORY_THEME = {
    football: { color: '#FF762E', emoji: '⚽', soft: '#FFE8D6' },
    basketball: { color: '#F97316', emoji: '🏀', soft: '#FFEDD5' },
    tennis: { color: '#22C55E', emoji: '🎾', soft: '#DCFCE7' },
    running: { color: '#14B8A6', emoji: '🏃', soft: '#CCFBF1' },
    outdoor: { color: '#10B981', emoji: '🌳', soft: '#D1FAE5' },
    gaming: { color: '#3B82F6', emoji: '🎮', soft: '#DBEAFE' },
    playstation: { color: '#2563EB', emoji: '🎮', soft: '#DBEAFE' },
    'board-games': { color: '#6366F1', emoji: '♟️', soft: '#E0E7FF' },
    cinema: { color: '#F43F5E', emoji: '🎬', soft: '#FFE4E6' },
    social: { color: '#EC4899', emoji: '🤝', soft: '#FCE7F3' },
    default: { color: '#7138F5', emoji: '✨', soft: '#F1EBFF' },
};

function themeFor(name = '', slug = '') {
    const key = String(slug || name)
        .toLowerCase()
        .replace(/\s+/g, '-');
    return CATEGORY_THEME[key] || CATEGORY_THEME.default;
}

function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function setStatus(root, msg) {
    const el = root.querySelector('[data-lm3-status]');
    if (el) el.textContent = msg || '';
}

function markerSvg(theme, selected = false) {
    const glow = selected ? 0.55 : 0.28;
    return `<svg viewBox="0 0 42 52" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <defs>
        <radialGradient id="g" cx="50%" cy="35%" r="55%">
          <stop offset="0%" stop-color="#fff" stop-opacity=".55"/>
          <stop offset="100%" stop-color="${theme.color}" stop-opacity="1"/>
        </radialGradient>
      </defs>
      <ellipse cx="21" cy="48" rx="10" ry="3.2" fill="${theme.color}" opacity="${glow}"/>
      <path d="M21 2c-9.4 0-17 7.4-17 16.6 0 11.4 13.2 26.6 16.2 29.8a1.2 1.2 0 0 0 1.7 0C25 45.2 38 30 38 18.6 38 9.4 30.4 2 21 2z" fill="url(#g)" stroke="#fff" stroke-width="2.2"/>
      <circle cx="21" cy="18.5" r="9.2" fill="#fff"/>
      <text x="21" y="22.5" text-anchor="middle" font-size="11">${theme.emoji}</text>
    </svg>`;
}

function createPinEl(item, theme, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'lm3-marker';
    el.innerHTML = markerSvg(theme);
    el.title = item.title || 'Event';
    el.setAttribute('aria-label', item.title || 'Event');
    el.addEventListener('click', (e) => {
        e.stopPropagation();
        onClick(item, el);
    });
    return el;
}

function createAggEl(item, onClick) {
    const el = document.createElement('button');
    el.type = 'button';
    el.className = 'lm3-agg';
    el.textContent = String(item.count ?? item.city_count ?? '?');
    el.title = item.label || item.city_name || 'City';
    el.addEventListener('click', (e) => {
        e.stopPropagation();
        onClick(item);
    });
    return el;
}

function placeCard(root, stage, point) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card || !stage) return;
    const rect = stage.getBoundingClientRect();
    const x = Math.min(Math.max(point.x - 148, 12), rect.width - 300);
    const y = Math.min(Math.max(point.y - 210, 12), rect.height - 240);
    card.style.left = `${x}px`;
    card.style.top = `${y}px`;
    card.style.right = 'auto';
}

function openCard(root, item, theme) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card) return;
    card.hidden = false;
    const media = card.querySelector('[data-lm3-card-media]');
    if (item.image_url) {
        media.innerHTML = `<img src="${item.image_url}" alt="" loading="lazy">`;
    } else {
        media.innerHTML = `<span style="font-size:2.4rem">${theme.emoji}</span>`;
    }
    const cat = card.querySelector('[data-lm3-card-cat]');
    cat.textContent = `${theme.emoji} ${item.category_name || 'Event'}`;
    cat.style.background = theme.soft;
    cat.style.color = theme.color;
    card.querySelector('[data-lm3-card-title]').textContent = item.title || 'Event';
    const when = item.starts_at
        ? new Date(item.starts_at).toLocaleString(undefined, {
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit',
          })
        : '';
    const bits = [when, item.venue_label, item.location_precision === 'exact' ? null : 'City-level'].filter(Boolean);
    if (item.members_count != null) bits.push(`${item.members_count} going`);
    card.querySelector('[data-lm3-card-meta]').textContent = bits.join(' · ');
    const link = card.querySelector('[data-lm3-card-link]');
    link.href = item.id ? `/events/${item.id}` : root.dataset.eventsUrl || '/events';
    requestAnimationFrame(() => card.classList.add('is-open'));
}

function closeCard(root) {
    const card = root.querySelector('[data-lm3-card]');
    if (!card) return;
    card.classList.remove('is-open');
    setTimeout(() => {
        card.hidden = true;
    }, 220);
}

function easeInOutCubic(t) {
    return t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2;
}

function animateCamera(map, to, duration = 2200) {
    return new Promise((resolve) => {
        if (!map || prefersReducedMotion()) {
            map?.jumpTo(to);
            resolve();
            return;
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
            const lng = from.center.lng + (to.center[0] - from.center.lng) * e;
            const lat = from.center.lat + (to.center[1] - from.center.lat) * e;
            map.jumpTo({
                center: [lng, lat],
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

function drawArc(map, a, b) {
    if (!map.getSource('lm3-arc')) {
        map.addSource('lm3-arc', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] },
        });
        map.addLayer({
            id: 'lm3-arc-line',
            type: 'line',
            source: 'lm3-arc',
            paint: {
                'line-color': '#7138F5',
                'line-width': 3,
                'line-opacity': 0.55,
                'line-blur': 0.4,
            },
        });
    }
    const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2 + 0.35];
    map.getSource('lm3-arc').setData({
        type: 'FeatureCollection',
        features: [
            {
                type: 'Feature',
                geometry: {
                    type: 'LineString',
                    coordinates: [a, mid, b],
                },
            },
        ],
    });
    setTimeout(() => {
        if (map.getSource('lm3-arc')) {
            map.getSource('lm3-arc').setData({ type: 'FeatureCollection', features: [] });
        }
    }, 2400);
}

async function fetchMapPayload(bounds, zoom) {
    const params = new URLSearchParams({
        north: String(bounds.getNorth()),
        south: String(bounds.getSouth()),
        east: String(bounds.getEast()),
        west: String(bounds.getWest()),
        zoom: String(Math.round(zoom)),
        include_approximate: '1',
        limit: '80',
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

    setStatus(root, 'Loading style…');

    const map = new window.maplibregl.Map({
        container: mapEl,
        style: root.dataset.styleUrl,
        center: EGYPT.center,
        zoom: EGYPT.zoom,
        pitch: prefersReducedMotion() ? 0 : EGYPT.pitch,
        bearing: prefersReducedMotion() ? 0 : EGYPT.bearing,
        maxBounds: EGYPT_BOUNDS,
        attributionControl: { compact: true },
        cooperativeGestures: true,
    });

    map.addControl(new window.maplibregl.NavigationControl({ visualizePitch: true }), 'bottom-right');

    const markers = [];
    let selectedEl = null;
    let tourTimer = null;
    let tourIndex = 0;
    let touring = false;
    let userPaused = true;
    let exactTour = [];
    let payloadCache = null;

    const clearMarkers = () => {
        markers.forEach((m) => m.remove());
        markers.length = 0;
    };

    const selectItem = async (item, el) => {
        userPaused = true;
        touring = false;
        tourBtn?.setAttribute('aria-pressed', 'false');
        tourBtn && (tourBtn.textContent = '▶');
        if (selectedEl) {
            selectedEl.classList.remove('is-selected', 'is-float');
            selectedEl.innerHTML = markerSvg(themeFor(selectedEl.dataset.cat, selectedEl.dataset.slug));
        }
        selectedEl = el || null;
        const theme = themeFor(item.category_name, item.category_slug);
        if (el) {
            el.classList.add('is-selected', 'is-float');
            el.dataset.cat = item.category_name || '';
            el.innerHTML = markerSvg(theme, true);
        }
        openCard(root, item, theme);
        const point = map.project([item.longitude, item.latitude]);
        placeCard(root, stage, point);
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: Math.max(map.getZoom(), 11.2),
                pitch: prefersReducedMotion() ? 0 : 48,
                bearing: map.getBearing() + (prefersReducedMotion() ? 0 : 18),
            },
            1600
        );
        const after = map.project([item.longitude, item.latitude]);
        placeCard(root, stage, after);
        root.querySelector('[data-lm3-focus-label]').textContent = item.title || 'Event';
        root.querySelector('[data-lm3-focus-sub]').textContent =
            item.location_precision === 'exact' ? 'Exact venue' : 'City-level location';
    };

    const renderStrip = (items) => {
        if (!strip) return;
        strip.innerHTML = '';
        items.slice(0, 8).forEach((item) => {
            const theme = themeFor(item.category_name);
            const a = document.createElement('a');
            a.href = item.id ? `/events/${item.id}` : '#';
            a.innerHTML = `
              <div style="height:6.5rem;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,${theme.soft},#fff);font-size:2rem">${
                  item.image_url
                      ? `<img src="${item.image_url}" alt="" style="width:100%;height:100%;object-fit:cover">`
                      : theme.emoji
              }</div>
              <div class="pad">
                <span style="font-size:.65rem;font-weight:800;color:${theme.color}">${theme.emoji} ${item.category_name || ''}</span>
                <h4>${item.title || ''}</h4>
                <p>${item.venue_label || ''}</p>
              </div>`;
            a.addEventListener('click', (e) => {
                if (!item.latitude) return;
                e.preventDefault();
                selectItem(item);
            });
            strip.appendChild(a);
        });
    };

    const paintData = (payload) => {
        clearMarkers();
        payloadCache = payload;
        const exact = [];
        const approxMarkers = [];

        (payload.markers || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact') exact.push(item);
            else approxMarkers.push(item);
        });
        (payload.clusters || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            if (item.location_precision === 'exact' || item.count === 1) exact.push(item);
        });

        exactTour = exact.filter((e) => e.id && e.latitude != null);

        exact.forEach((item) => {
            const theme = themeFor(item.category_name);
            const el = createPinEl(item, theme, selectItem);
            el.dataset.slug = '';
            const m = new window.maplibregl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markers.push(m);
        });

        (payload.city_aggregates || []).forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            const el = createAggEl(item, (agg) => {
                map.easeTo({
                    center: [agg.longitude, agg.latitude],
                    zoom: Math.min(12, (agg.expansion_zoom || 10) + 1),
                    pitch: prefersReducedMotion() ? 0 : 42,
                    duration: prefersReducedMotion() ? 0 : 900,
                });
                root.querySelector('[data-lm3-focus-label]').textContent = agg.city_name || agg.label || 'City';
                root.querySelector('[data-lm3-focus-sub]').textContent =
                    `${agg.count || 0} city-level events · not exact venues`;
                closeCard(root);
            });
            const m = new window.maplibregl.Marker({ element: el, anchor: 'center' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            markers.push(m);
        });

        renderStrip([...exact, ...approxMarkers]);
        setStatus(
            root,
            `${exact.length} exact · ${(payload.city_aggregates || []).length} city aggregates`
        );
    };

    const load = async () => {
        setStatus(root, 'Loading events…');
        try {
            const payload = await fetchMapPayload(map.getBounds(), map.getZoom());
            paintData(payload);
        } catch (e) {
            console.warn('living map v3', e);
            setStatus(root, 'Discovery API unavailable');
        }
    };

    const stopTour = () => {
        touring = false;
        userPaused = true;
        if (tourTimer) clearTimeout(tourTimer);
        tourBtn?.setAttribute('aria-pressed', 'false');
        if (tourBtn) tourBtn.textContent = '▶';
    };

    const runTourStep = async () => {
        if (!touring || prefersReducedMotion() || document.hidden) return;
        if (exactTour.length < 2) {
            setStatus(root, 'Need 2+ exact venues for cinematic tour');
            stopTour();
            return;
        }
        const item = exactTour[tourIndex % exactTour.length];
        const next = exactTour[(tourIndex + 1) % exactTour.length];
        tourIndex += 1;
        const theme = themeFor(item.category_name);
        openCard(root, item, theme);
        const point = map.project([item.longitude, item.latitude]);
        placeCard(root, stage, point);
        if (next) {
            drawArc(map, [item.longitude, item.latitude], [next.longitude, next.latitude]);
        }
        await animateCamera(
            map,
            {
                center: [item.longitude, item.latitude],
                zoom: 11.6,
                pitch: 50,
                bearing: (map.getBearing() + 28) % 360,
            },
            2400
        );
        if (!touring) return;
        tourTimer = setTimeout(runTourStep, 2800);
    };

    const startTour = () => {
        if (prefersReducedMotion()) {
            setStatus(root, 'Reduced motion — tour disabled');
            return;
        }
        if (exactTour.length < 2) {
            setStatus(root, 'Ambient map — fewer than 2 exact events');
            return;
        }
        userPaused = false;
        touring = true;
        tourBtn?.setAttribute('aria-pressed', 'true');
        if (tourBtn) tourBtn.textContent = '❚❚';
        runTourStep();
    };

    map.on('load', async () => {
        // Soft water tint via atmosphere when supported.
        try {
            map.setFog({
                color: 'rgb(200, 220, 255)',
                'high-color': 'rgb(180, 210, 255)',
                'horizon-blend': 0.08,
            });
        } catch {
            /* style may not support fog */
        }
        await load();
        setStatus(root, 'Ready · press ▶ for cinematic tour');
    });

    map.on('movestart', (e) => {
        if (e.originalEvent) stopTour();
    });
    map.on('click', () => closeCard(root));
    map.on('move', () => {
        // Keep card anchored during camera motion if open.
        const card = root.querySelector('[data-lm3-card]');
        if (!card || card.hidden || !exactTour[tourIndex % Math.max(exactTour.length, 1)]) return;
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopTour();
    });

    const io = new IntersectionObserver((entries) => {
        if (!entries.some((en) => en.isIntersecting)) stopTour();
    });
    io.observe(stage);

    tourBtn?.addEventListener('click', () => {
        if (touring) stopTour();
        else startTour();
    });
    root.querySelector('[data-lm3-zoom-in]')?.addEventListener('click', () => map.zoomIn({ duration: 400 }));
    root.querySelector('[data-lm3-zoom-out]')?.addEventListener('click', () => map.zoomOut({ duration: 400 }));
    root.querySelector('[data-lm3-reset]')?.addEventListener('click', () => {
        stopTour();
        closeCard(root);
        map.easeTo({ ...EGYPT, duration: prefersReducedMotion() ? 0 : 1200 });
        root.querySelector('[data-lm3-focus-label]').textContent = 'Egypt';
        root.querySelector('[data-lm3-focus-sub]').textContent = 'Nationwide · no auto GPS';
    });

    // Expose for performance probes.
    root._lm3 = { map, reload: load, getExactCount: () => exactTour.length };
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
