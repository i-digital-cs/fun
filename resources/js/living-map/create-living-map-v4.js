/**
 * Living Map V4.0 — Hybrid cinematic Discover + MapLibre Navigate (preview only).
 * Does not modify V3.4 / V3.5 production paths.
 */
import { loadFunnityStyleV35 } from './funnity-style-v35.js';
import { fillTemplate, readI18n } from './i18n.js';
import {
    requestDevicePosition,
    reverseGeocodeLabel,
    saveDiscoveryState,
} from '../discovery-location.js';
import { PORT_SAID_SCENE } from './v4/scene-port-said.js';

const RTL_PLUGIN_URL =
    'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js';

const CATEGORY_THEME = {
    football: { color: '#FF762E', emoji: '⚽', glow: 'rgba(255,118,46,.55)' },
    basketball: { color: '#F97316', emoji: '🏀', glow: 'rgba(249,115,22,.5)' },
    tennis: { color: '#22C55E', emoji: '🎾', glow: 'rgba(34,197,94,.5)' },
    running: { color: '#14B8A6', emoji: '🏃', glow: 'rgba(20,184,166,.5)' },
    outdoor: { color: '#10B981', emoji: '🌳', glow: 'rgba(16,185,129,.5)' },
    gaming: { color: '#3B82F6', emoji: '🎮', glow: 'rgba(59,130,246,.55)' },
    playstation: { color: '#2563EB', emoji: '🎮', glow: 'rgba(37,99,235,.55)' },
    'board-games': { color: '#6366F1', emoji: '♟️', glow: 'rgba(99,102,241,.5)' },
    cinema: { color: '#F43F5E', emoji: '🎬', glow: 'rgba(244,63,94,.55)' },
    social: { color: '#EC4899', emoji: '🤝', glow: 'rgba(236,72,153,.55)' },
    music: { color: '#7138F5', emoji: '🎵', glow: 'rgba(113,56,245,.55)' },
    food: { color: '#F59E0B', emoji: '☕', glow: 'rgba(245,158,11,.5)' },
    default: { color: '#7138F5', emoji: '✨', glow: 'rgba(113,56,245,.5)' },
};

function themeFor(name = '') {
    const key = String(name).toLowerCase().replace(/\s+/g, '-');
    if (CATEGORY_THEME[key]) return CATEGORY_THEME[key];
    if (/music|concert/.test(key)) return CATEGORY_THEME.music;
    if (/food|cafe|coffee/.test(key)) return CATEGORY_THEME.food;
    if (/sport|padel|football|soccer/.test(key)) return CATEGORY_THEME.football;
    if (/game|play/.test(key)) return CATEGORY_THEME.gaming;
    if (/cinema|movie|film/.test(key)) return CATEGORY_THEME.cinema;
    if (/run|jog/.test(key)) return CATEGORY_THEME.running;
    if (/tennis/.test(key)) return CATEGORY_THEME.tennis;
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

function formatWhen(item, locale) {
    if (!item?.starts_at) return '';
    try {
        return new Date(item.starts_at).toLocaleString(locale || undefined, {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        });
    } catch {
        return '';
    }
}

function isActuallyLive(item) {
    if (!item?.starts_at) return false;
    const start = new Date(item.starts_at).getTime();
    if (!Number.isFinite(start)) return false;
    const end = item.ends_at ? new Date(item.ends_at).getTime() : start + 3 * 60 * 60 * 1000;
    const now = Date.now();
    return now >= start && now <= end;
}

function categoryKey(item) {
    return String(item?.category_name || item?.category || '')
        .toLowerCase()
        .replace(/\s+/g, '-');
}

function eventHref(item, eventsUrl) {
    return item?.url || (item?.id ? `/events/${item.id}` : eventsUrl || '/events');
}

async function ensureRtlTextPlugin() {
    const gl = window.maplibregl;
    if (!gl?.setRTLTextPlugin) return 'missing';
    try {
        const status = gl.getRTLTextPluginStatus?.() || 'unknown';
        if (status === 'loaded' || status === 'unavailable') return status;
        const pending = gl.setRTLTextPlugin(RTL_PLUGIN_URL, false);
        if (pending?.then) await pending.catch(() => {});
        return gl.getRTLTextPluginStatus?.() || 'unknown';
    } catch {
        return 'error';
    }
}

async function fetchDiscovery(origin, bbox, opts = {}) {
    const params = new URLSearchParams({
        north: String(bbox.north),
        south: String(bbox.south),
        east: String(bbox.east),
        west: String(bbox.west),
        zoom: String(opts.zoom ?? 11),
        include_approximate: '1',
        limit: '60',
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
        signal: opts.signal,
    });
    if (!res.ok) throw new Error(`discovery map ${res.status}`);
    return res.json();
}

async function fetchCityEvents(cityId) {
    const res = await fetch(`/api/discovery/city-events?city_id=${encodeURIComponent(cityId)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });
    if (!res.ok) throw new Error(`city-events ${res.status}`);
    return res.json();
}

function readOrigin(root) {
    const lat = Number(root.dataset.lat);
    const lng = Number(root.dataset.lng);
    const km = Number(root.dataset.distanceKm);
    const mode = root.dataset.discoveryMode || 'manual';
    const hasOrigin = Number.isFinite(lat) && Number.isFinite(lng);
    return {
        hasOrigin,
        lat: hasOrigin ? lat : PORT_SAID_SCENE.geo.lat,
        lng: hasOrigin ? lng : PORT_SAID_SCENE.geo.lng,
        km: Number.isFinite(km) ? km : 25,
        mode: hasOrigin ? mode : 'manual',
        label: root.dataset.locationLabel || PORT_SAID_SCENE.cityName,
        cityId: root.dataset.cityId ? Number(root.dataset.cityId) : PORT_SAID_SCENE.cityId,
    };
}

/**
 * Bind real API events onto decorative hotspots.
 * Empty slots stay hidden — never invent events.
 */
function bindEventsToHotspots(events, hotspots) {
    const pool = [...events].filter((e) => e && e.id != null);
    const used = new Set();
    const bindings = [];

    hotspots.forEach((slot) => {
        let pick = null;
        const prefers = (slot.prefer || []).map((p) => String(p).toLowerCase());
        if (prefers.length) {
            pick = pool.find((e) => {
                if (used.has(e.id)) return false;
                const key = categoryKey(e);
                return prefers.some((p) => key.includes(p) || p.includes(key));
            });
        }
        if (!pick) {
            pick = pool.find((e) => !used.has(e.id));
        }
        if (!pick) return;
        used.add(pick.id);
        bindings.push({ slot, event: pick });
    });

    return bindings;
}

function portSaidEvents(payload, cityEvents) {
    const fromCity = Array.isArray(cityEvents?.events) ? cityEvents.events : [];
    if (fromCity.length) return fromCity;

    const markers = Array.isArray(payload?.markers) ? payload.markers : [];
    const city = PORT_SAID_SCENE.cityName.toLowerCase();
    const local = markers.filter((m) => {
        const venue = String(m.venue_label || m.venue || '').toLowerCase();
        const name = String(m.city_name || m.city || '').toLowerCase();
        if (Number(m.city_id) === PORT_SAID_SCENE.cityId) return true;
        if (name.includes('port said') || name.includes(city)) return true;
        if (venue.includes('port said') || venue.includes('بورسعيد')) return true;
        // Same centroid as Port Said city aggregate (approximate city-level).
        if (
            m.latitude != null &&
            m.longitude != null &&
            Math.abs(Number(m.latitude) - PORT_SAID_SCENE.geo.lat) < 0.02 &&
            Math.abs(Number(m.longitude) - PORT_SAID_SCENE.geo.lng) < 0.02
        ) {
            return true;
        }
        return false;
    });
    return local.length ? local : markers.slice(0, 8);
}

/**
 * @param {HTMLElement} root
 */
export async function createLivingMapV4(root) {
    if (!root || root.dataset.ready === '1') return null;
    root.dataset.ready = '1';

    const i18n = readI18n(root);
    const scene = PORT_SAID_SCENE;
    let origin = readOrigin(root);
    let viewMode = 'map'; // map | list | nearby  (map = Discover or Navigate stage)
    let stageMode = 'discover'; // discover | navigate
    let payloadCache = null;
    let cityEventsCache = null;
    let listEvents = [];
    let selectedEventId = null;
    let spotlightDismissed = false;
    let abortCtrl = null;
    let map = null;
    let mapReady = false;
    const mapMarkers = [];

    try {
        spotlightDismissed = sessionStorage.getItem('lm-v4-spotlight-dismissed') === '1';
    } catch {
        /* ignore */
    }

    const statusEl = root.querySelector('[data-lm-status]');
    const sceneLayer = root.querySelector('[data-lm-v4-scene]');
    const mapLayer = root.querySelector('[data-lm-v4-map]');
    const pinsEl = root.querySelector('[data-lm-v4-pins]');
    const arcsEl = root.querySelector('[data-lm-v4-arcs]');
    const spotlightEl = root.querySelector('[data-lm-spotlight]');
    const railEl = root.querySelector('[data-lm-list]');
    const listHeading = root.querySelector('[data-lm-list-heading]');
    const cityLabelEls = root.querySelectorAll('[data-lm-focus-label]');
    const focusSub = root.querySelector('[data-lm-focus-sub]');
    const distReadout = root.querySelector('[data-lm-distance-readout]');
    const radiusSlider = root.querySelector('[data-lm-radius-slider]');
    const radiusWrap = root.querySelector('[data-lm-radius-wrap]');
    const useLocationBtn = root.querySelector('[data-lm-use-location]');
    const disclaimerEl = root.querySelector('[data-lm-v4-disclaimer]');

    let radiusOptions = [];
    try {
        radiusOptions = JSON.parse(root.dataset.radiusOptions || '[]');
        if (!Array.isArray(radiusOptions)) radiusOptions = [];
        radiusOptions = radiusOptions.map((n) => Number(n)).filter((n) => Number.isFinite(n));
    } catch {
        radiusOptions = [5, 10, 25, 50, 100, 0];
    }

    const setStatus = (text) => {
        if (statusEl) statusEl.textContent = text || '';
    };

    const syncRadiusUi = () => {
        const show = origin.hasOrigin;
        if (radiusWrap) radiusWrap.hidden = !show;
        const idx = Math.max(0, radiusOptions.findIndex((v) => Number(v) === Number(origin.km)));
        if (radiusSlider) {
            radiusSlider.disabled = !show;
            radiusSlider.value = String(idx >= 0 ? idx : 0);
        }
        root.querySelectorAll('.lm-slider__labels span').forEach((span, i) => {
            span.classList.toggle('is-active', show && i === idx);
        });
        if (distReadout) {
            distReadout.textContent = show
                ? origin.km > 0
                    ? fillTemplate(i18n.withinKm || 'within :km km', { km: origin.km })
                    : i18n.allDistances || 'All distances'
                : '';
            distReadout.hidden = !show;
        }
    };

    const syncCityLabel = () => {
        const label = origin.label || scene.cityName;
        cityLabelEls.forEach((el) => {
            el.textContent = label;
        });
        if (focusSub) {
            focusSub.textContent = fillTemplate(
                i18n.eventsAround || 'Events around :city · Approximate locations',
                { city: label }
            );
            // Keep readout as a separate line (avoid concatenating into the subcopy).
            focusSub.style.display = 'block';
        }
        syncRadiusUi();
    };

    const setStageMode = (next) => {
        stageMode = next === 'navigate' ? 'navigate' : 'discover';
        root.dataset.stageMode = stageMode;
        root.classList.toggle('is-stage-discover', stageMode === 'discover');
        root.classList.toggle('is-stage-navigate', stageMode === 'navigate');
        root.querySelectorAll('[data-lm-stage-mode]').forEach((btn) => {
            const on = btn.dataset.lmStageMode === stageMode;
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        if (sceneLayer) sceneLayer.hidden = stageMode !== 'discover';
        if (mapLayer) mapLayer.hidden = stageMode !== 'navigate';
        if (disclaimerEl) {
            disclaimerEl.hidden = stageMode !== 'discover';
        }
        if (stageMode === 'navigate') {
            ensureMap()
                .then(() => paintNavigate())
                .catch((e) => console.warn(e));
        }
    };

    const setViewMode = (next) => {
        const allowed = ['map', 'list', 'nearby'];
        viewMode = allowed.includes(next) ? next : 'map';
        root.classList.remove('is-view-map', 'is-view-list', 'is-view-nearby');
        root.classList.add(`is-view-${viewMode}`);
        root.querySelectorAll('[data-lm-view]').forEach((btn) => {
            btn.setAttribute('aria-pressed', btn.dataset.lmView === viewMode ? 'true' : 'false');
        });
        if (viewMode === 'nearby' || viewMode === 'map') {
            refresh().catch((e) => console.warn(e));
        } else {
            renderRail(listEvents);
        }
        if (viewMode === 'map' && stageMode === 'navigate' && map) {
            requestAnimationFrame(() => {
                try {
                    map.resize();
                } catch {
                    /* ignore */
                }
            });
        }
    };

    const dismissSpotlight = () => {
        spotlightDismissed = true;
        try {
            sessionStorage.setItem('lm-v4-spotlight-dismissed', '1');
        } catch {
            /* ignore */
        }
        if (spotlightEl) {
            spotlightEl.hidden = true;
            spotlightEl.classList.remove('is-open');
        }
        root.querySelectorAll('.lm4-pin.is-spotlight').forEach((el) =>
            el.classList.remove('is-spotlight')
        );
    };

    const renderSpotlight = (item) => {
        if (!spotlightEl || !item || spotlightDismissed || viewMode !== 'map') {
            if (spotlightEl) {
                spotlightEl.hidden = true;
                spotlightEl.classList.remove('is-open');
            }
            return;
        }
        const theme = themeFor(item.category_name || item.category);
        const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
        const when = formatWhen(item, root.dataset.locale);
        const going =
            item.members_count != null && Number(item.members_count) > 0
                ? fillTemplate(i18n.going, { count: item.members_count })
                : '';
        const approx =
            item.location_precision !== 'exact' || stageMode === 'discover'
                ? i18n.spotlightApproxNote ||
                  'Approximate city location · not an exact venue pin'
                : i18n.exactVenue || 'Exact venue';
        const live = isActuallyLive(item)
            ? `<span class="lm4-spotlight__live">${escapeHtml(i18n.liveNow || 'Live Now')}</span>`
            : '';
        const href = eventHref(item, root.dataset.eventsUrl);

        spotlightEl.innerHTML = `
            <button type="button" class="lm4-spotlight__close" data-lm-spotlight-dismiss aria-label="${escapeHtml(i18n.dismissSpotlight || 'Dismiss')}">×</button>
            <div class="lm4-spotlight__media" style="--lm-pin:${theme.color}">
                ${
                    imageUrl
                        ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" width="72" height="72">`
                        : `<span class="lm4-spotlight__emoji" aria-hidden="true">${theme.emoji}</span>`
                }
            </div>
            <div class="lm4-spotlight__body min-w-0">
                <div class="lm4-spotlight__top">
                    <span class="lm4-spotlight__cat" style="color:${theme.color}">${escapeHtml(item.category_name || item.category || '')}</span>
                    ${live}
                </div>
                <strong class="lm4-spotlight__title" dir="auto">${escapeHtml(item.title || '')}</strong>
                <span class="lm4-spotlight__meta" dir="auto">${escapeHtml([when, going].filter(Boolean).join(' · '))}</span>
                <span class="lm4-spotlight__note" dir="auto">${escapeHtml(approx)}</span>
                <a class="lm4-spotlight__cta" href="${escapeHtml(href)}">${escapeHtml(i18n.viewEvent || 'View Event')}</a>
            </div>
        `;
        spotlightEl.hidden = false;
        requestAnimationFrame(() => spotlightEl.classList.add('is-open'));
        spotlightEl.querySelector('[data-lm-spotlight-dismiss]')?.addEventListener('click', dismissSpotlight);
    };

    const selectEvent = (item, { fromPin = false } = {}) => {
        if (!item) return;
        selectedEventId = item.id;
        root.querySelectorAll('.lm4-pin').forEach((el) => {
            const on = String(el.dataset.eventId) === String(item.id);
            el.classList.toggle('is-selected', on);
            el.classList.toggle('is-spotlight', on && fromPin);
            el.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        renderSpotlight(item);
        root.querySelectorAll('[data-lm-rail-card]').forEach((card) => {
            card.classList.toggle('is-selected', String(card.dataset.eventId) === String(item.id));
        });
    };

    const renderDiscoverPins = (events) => {
        if (!pinsEl || !arcsEl) return;
        pinsEl.innerHTML = '';
        arcsEl.innerHTML = '';

        const bindings = bindEventsToHotspots(events, scene.hotspots);
        const agg = (payloadCache?.city_aggregates || []).find(
            (c) => Number(c.city_id) === scene.cityId || Number(c.id) === scene.cityId
        );
        const aggCount = agg
            ? Number(agg.count) || events.length
            : events.length;

        // City collection badge (illustrated placement)
        if (aggCount > 0) {
            const b = scene.cityBadge;
            const badge = document.createElement('button');
            badge.type = 'button';
            badge.className = 'lm4-pin lm4-pin--agg';
            badge.style.left = `${b.u * 100}%`;
            badge.style.top = `${b.v * 100}%`;
            badge.style.setProperty('--lm-pin', '#7138F5');
            badge.style.setProperty('--lm-glow', 'rgba(113,56,245,.55)');
            badge.dataset.kind = 'aggregate';
            badge.setAttribute(
                'aria-label',
                fillTemplate(i18n.aggregateEventsInCity || ':count events in :city · Approximate locations', {
                    count: aggCount,
                    city: origin.label || scene.cityName,
                })
            );
            badge.innerHTML = `<span class="lm4-pin__orb">${escapeHtml(String(aggCount))}</span><span class="lm4-pin__halo" aria-hidden="true"></span>`;
            badge.addEventListener('click', () => {
                if (events[0]) selectEvent(events[0], { fromPin: true });
            });
            pinsEl.appendChild(badge);
        }

        const pts = [];
        bindings.forEach(({ slot, event }) => {
            const theme = themeFor(event.category_name || event.category);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lm4-pin lm4-pin--event';
            btn.style.left = `${slot.u * 100}%`;
            btn.style.top = `${slot.v * 100}%`;
            btn.style.setProperty('--lm-pin', theme.color);
            btn.style.setProperty('--lm-glow', theme.glow);
            btn.dataset.eventId = String(event.id);
            btn.dataset.kind = 'event';
            btn.dataset.slot = slot.id;
            btn.setAttribute(
                'aria-label',
                `${event.title || 'Event'} · ${i18n.approximateCityLocation || 'Approximate city location'}`
            );
            btn.setAttribute('aria-pressed', 'false');
            btn.innerHTML = `
                <span class="lm4-pin__teardrop" aria-hidden="true">
                    <span class="lm4-pin__emoji">${theme.emoji}</span>
                </span>
                <span class="lm4-pin__halo" aria-hidden="true"></span>
            `;
            btn.addEventListener('click', () => selectEvent(event, { fromPin: true }));
            pinsEl.appendChild(btn);
            pts.push({ u: slot.u, v: slot.v, color: theme.color });
        });

        // Decorative arcs between first few pins (visual only)
        if (!prefersReducedMotion() && pts.length >= 2) {
            const ns = 'http://www.w3.org/2000/svg';
            const svg = document.createElementNS(ns, 'svg');
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('preserveAspectRatio', 'none');
            svg.classList.add('lm4-arcs__svg');
            const pairs = [
                [0, 1],
                [1, 2],
                [0, 2],
            ].filter(([a, b]) => pts[a] && pts[b]);
            pairs.forEach(([a, b], i) => {
                const p0 = pts[a];
                const p1 = pts[b];
                const midX = (p0.u + p1.u) * 50;
                const midY = Math.min(p0.v, p1.v) * 100 - 8;
                const path = document.createElementNS(ns, 'path');
                path.setAttribute(
                    'd',
                    `M ${p0.u * 100} ${p0.v * 100} Q ${midX} ${midY} ${p1.u * 100} ${p1.v * 100}`
                );
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke', i % 2 === 0 ? 'rgba(113,56,245,.45)' : 'rgba(255,118,46,.4)');
                path.setAttribute('stroke-width', '0.45');
                path.setAttribute('stroke-linecap', 'round');
                svg.appendChild(path);
            });
            arcsEl.appendChild(svg);
        }

        if (!spotlightDismissed && bindings[0]) {
            selectEvent(bindings[0].event, { fromPin: true });
        } else if (!spotlightDismissed && events[0]) {
            selectEvent(events[0], { fromPin: false });
        }
    };

    const renderRail = (events) => {
        if (!railEl) return;
        if (listHeading) {
            listHeading.textContent =
                viewMode === 'list'
                    ? i18n.listHeading || i18n.eventsNearYou || 'Events near you'
                    : i18n.eventsNearYou || 'Events near you';
        }
        if (!events.length) {
            railEl.innerHTML = `<p class="lm-rail__empty">${escapeHtml(i18n.listEmpty || 'No events in this view yet.')}</p>`;
            return;
        }
        railEl.innerHTML = events
            .map((item) => {
                const theme = themeFor(item.category_name || item.category);
                const imageUrl = typeof item.image_url === 'string' ? item.image_url.trim() : '';
                const when = formatWhen(item, root.dataset.locale);
                const going =
                    item.members_count != null && Number(item.members_count) > 0
                        ? fillTemplate(i18n.going, { count: item.members_count })
                        : '';
                const href = eventHref(item, root.dataset.eventsUrl);
                const live = isActuallyLive(item)
                    ? `<span class="lm-rail__live">${escapeHtml(i18n.liveNow || 'Live Now')}</span>`
                    : '';
                const approx = escapeHtml(
                    i18n.approximateCityLocation || 'Approximate city location'
                );
                return `
                <article class="lm-rail__card lm4-rail-card ${String(item.id) === String(selectedEventId) ? 'is-selected' : ''}"
                         data-lm-rail-card data-event-id="${escapeHtml(String(item.id))}">
                    <button type="button" class="lm4-rail-card__hit" data-lm-rail-select aria-label="${escapeHtml(item.title || '')}">
                        <div class="lm-rail__media" style="--lm-pin:${theme.color}">
                            ${
                                imageUrl
                                    ? `<img src="${escapeHtml(imageUrl)}" alt="" loading="lazy" width="280" height="160">`
                                    : `<span class="lm-rail__emoji" aria-hidden="true">${theme.emoji}</span>`
                            }
                            <span class="lm-rail__chip">${escapeHtml(item.category_name || item.category || '')}</span>
                            ${live}
                        </div>
                        <div class="lm-rail__body">
                            <strong dir="auto">${escapeHtml(item.title || '')}</strong>
                            <span class="lm-rail__meta" dir="auto">${escapeHtml([when, going].filter(Boolean).join(' · '))}</span>
                            <span class="lm4-rail-card__approx" dir="auto">${approx}</span>
                        </div>
                    </button>
                    <a class="lm-rail__go" href="${escapeHtml(href)}" aria-label="${escapeHtml(i18n.viewEvent || 'View Event')}">→</a>
                </article>`;
            })
            .join('');

        railEl.querySelectorAll('[data-lm-rail-select]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const card = btn.closest('[data-event-id]');
                const id = card?.dataset.eventId;
                const item = events.find((e) => String(e.id) === String(id));
                if (item) {
                    if (viewMode === 'list') setViewMode('map');
                    selectEvent(item, { fromPin: true });
                }
            });
        });
    };

    const clearMapMarkers = () => {
        mapMarkers.forEach((m) => m.remove());
        mapMarkers.length = 0;
    };

    const paintNavigate = () => {
        if (!map || !payloadCache) return;
        clearMapMarkers();
        const gl = window.maplibregl;
        const markers = Array.isArray(payloadCache.markers) ? payloadCache.markers : [];
        const aggs = Array.isArray(payloadCache.city_aggregates)
            ? payloadCache.city_aggregates
            : [];

        aggs.forEach((c) => {
            if (c.latitude == null || c.longitude == null) return;
            if (c.type === 'city_cluster') return;
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'lm4-map-marker lm4-map-marker--agg';
            el.textContent = String(c.count || '');
            el.setAttribute(
                'aria-label',
                fillTemplate(i18n.aggregateEventsInCity || ':count events in :city · Approximate locations', {
                    count: c.count,
                    city: c.name || c.city_name || '',
                })
            );
            const m = new gl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([c.longitude, c.latitude])
                .addTo(map);
            mapMarkers.push(m);
        });

        markers.forEach((item) => {
            if (item.latitude == null || item.longitude == null) return;
            const theme = themeFor(item.category_name || item.category);
            const el = document.createElement('button');
            el.type = 'button';
            el.className = 'lm4-map-marker lm4-map-marker--event';
            el.style.setProperty('--lm-pin', theme.color);
            el.innerHTML = `<span>${theme.emoji}</span>`;
            el.setAttribute(
                'aria-label',
                `${item.title || ''} · ${
                    item.location_precision === 'exact'
                        ? i18n.exactVenue || 'Exact venue'
                        : i18n.approximateCityLocation || 'Approximate'
                }`
            );
            el.addEventListener('click', () => selectEvent(item, { fromPin: true }));
            const m = new gl.Marker({ element: el, anchor: 'bottom' })
                .setLngLat([item.longitude, item.latitude])
                .addTo(map);
            mapMarkers.push(m);
        });
    };

    async function ensureMap() {
        if (mapReady && map) {
            map.resize();
            return map;
        }
        const mapEl = root.querySelector('[data-lm-map]');
        if (!mapEl || !window.maplibregl) {
            setStatus(i18n.maplibreFailed || 'MapLibre failed to load');
            throw new Error('maplibre');
        }
        setStatus(i18n.loadingRtl || 'Loading…');
        await ensureRtlTextPlugin();
        setStatus(i18n.loadingStyle || 'Loading style…');
        let style;
        try {
            style = await loadFunnityStyleV35(root.dataset.styleUrl);
        } catch (e) {
            console.warn(e);
            style = root.dataset.styleUrl;
        }
        map = new window.maplibregl.Map({
            container: mapEl,
            style,
            center: [origin.lng, origin.lat],
            zoom: 10.5,
            pitch: prefersReducedMotion() ? 0 : 36,
            bearing: prefersReducedMotion() ? 0 : -6,
            attributionControl: true,
        });
        await new Promise((resolve) => map.once('load', resolve));
        mapReady = true;
        return map;
    }

    async function refresh() {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        setStatus(i18n.composing || 'Composing…');
        syncCityLabel();

        try {
            const payload = await fetchDiscovery(origin, scene.bbox, {
                zoom: 11,
                signal: abortCtrl.signal,
            });
            payloadCache = payload;

            let cityPayload = null;
            try {
                cityPayload = await fetchCityEvents(scene.cityId);
                cityEventsCache = cityPayload;
            } catch (e) {
                console.warn('city-events', e);
            }

            const events = portSaidEvents(payload, cityPayload);
            listEvents = events;

            if (stageMode === 'discover') {
                renderDiscoverPins(events);
            } else {
                await ensureMap();
                map.easeTo({
                    center: [origin.lng, origin.lat],
                    zoom: 10.8,
                    duration: prefersReducedMotion() ? 0 : 900,
                });
                paintNavigate();
            }
            renderRail(events);
            setStatus(i18n.ready || 'Ready');
        } catch (e) {
            if (e?.name === 'AbortError') return;
            console.warn(e);
            setStatus(i18n.discoveryUnavailable || 'Discovery API unavailable');
            if (pinsEl) pinsEl.innerHTML = '';
            if (railEl) {
                railEl.innerHTML = `<p class="lm-rail__empty">${escapeHtml(i18n.discoveryUnavailable || 'Discovery unavailable')}</p>`;
            }
        }
    }

    // Controls
    root.querySelectorAll('[data-lm-view]').forEach((btn) => {
        btn.addEventListener('click', () => setViewMode(btn.dataset.lmView));
    });
    root.querySelectorAll('[data-lm-stage-mode]').forEach((btn) => {
        btn.addEventListener('click', () => setStageMode(btn.dataset.lmStageMode));
    });

    root.querySelector('[data-lm-zoom-in]')?.addEventListener('click', () => {
        if (stageMode === 'navigate' && map) map.zoomIn({ duration: 300 });
        else {
            const img = root.querySelector('[data-lm-v4-art]');
            if (img) img.classList.add('is-zoomed');
        }
    });
    root.querySelector('[data-lm-zoom-out]')?.addEventListener('click', () => {
        if (stageMode === 'navigate' && map) map.zoomOut({ duration: 300 });
        else {
            const img = root.querySelector('[data-lm-v4-art]');
            if (img) img.classList.remove('is-zoomed');
        }
    });
    root.querySelector('[data-lm-reset]')?.addEventListener('click', () => {
        if (stageMode === 'navigate' && map) {
            map.easeTo({
                center: [origin.lng, origin.lat],
                zoom: 10.8,
                duration: prefersReducedMotion() ? 0 : 700,
            });
        } else {
            root.querySelector('[data-lm-v4-art]')?.classList.remove('is-zoomed');
        }
    });

    root.querySelector('[data-lm-rail-next]')?.addEventListener('click', () => {
        const rail = root.querySelector('[data-lm-list]');
        if (rail) rail.scrollBy({ left: rail.clientWidth * 0.8, behavior: 'smooth' });
    });

    if (radiusSlider) {
        radiusSlider.addEventListener('input', () => {
            const idx = Number(radiusSlider.value);
            if (!Number.isFinite(idx) || !radiusOptions[idx] && radiusOptions[idx] !== 0) return;
            origin = { ...origin, km: Number(radiusOptions[idx]), hasOrigin: true };
            root.dataset.distanceKm = String(origin.km);
            syncRadiusUi();
        });
        radiusSlider.addEventListener('change', () => {
            saveDiscoveryState({
                mode: origin.mode === 'device' ? 'device' : 'manual',
                lat: origin.lat,
                lng: origin.lng,
                distanceKm: origin.km,
                cityId: origin.cityId,
                cityName: origin.label,
                locationLabel: origin.label,
            });
            refresh().catch((e) => console.warn(e));
        });
    }

    if (useLocationBtn) {
        useLocationBtn.addEventListener('click', async () => {
            useLocationBtn.disabled = true;
            setStatus(i18n.detectingLocation || 'Detecting…');
            try {
                const pos = await requestDevicePosition();
                const geo = await reverseGeocodeLabel(
                    pos.lat,
                    pos.lng,
                    root.dataset.reverseUrl
                );
                const label = geo?.short_name || geo?.display_name || origin.label;
                origin = {
                    hasOrigin: true,
                    lat: pos.lat,
                    lng: pos.lng,
                    km: origin.km > 0 ? origin.km : 25,
                    mode: 'device',
                    label,
                    cityId: null,
                };
                root.dataset.lat = String(origin.lat);
                root.dataset.lng = String(origin.lng);
                root.dataset.discoveryMode = 'device';
                root.dataset.locationLabel = label;
                delete root.dataset.cityId;
                saveDiscoveryState({
                    mode: 'device',
                    lat: origin.lat,
                    lng: origin.lng,
                    accuracyM: pos.accuracyM,
                    distanceKm: origin.km,
                    locationLabel: geo?.display_name || label,
                    shortLabel: geo?.short_name || null,
                });
                syncCityLabel();
                // Device GPS may leave the Port Said scene — switch to Navigate for honesty.
                setStageMode('navigate');
                await refresh();
            } catch (e) {
                console.warn(e);
                setStatus(i18n.gpsUnavailable || i18n.gpsDenied || 'Location unavailable');
            } finally {
                useLocationBtn.disabled = false;
            }
        });
    }

    // Init
    root.classList.add('is-view-map', 'is-stage-discover');
    setStageMode('discover');
    syncCityLabel();
    await refresh();

    return {
        refresh,
        setStageMode,
        getStageMode: () => stageMode,
    };
}
