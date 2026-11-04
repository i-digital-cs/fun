/**
 * Living World — Procedural 3D feasibility POC (Cairo downtown only).
 * Toggle: V5 fill-extrusion vs procedural Three.js custom layer.
 */
import { loadFunnityStyleV5 } from './funnity-style-v5.js';
import { createProceduralBuildingsLayer } from './procedural/procedural-buildings-layer.js';

const RTL_PLUGIN_URL =
    'https://unpkg.com/@mapbox/mapbox-gl-rtl-text@0.2.3/mapbox-gl-rtl-text.js';

/** Same camera for A/B comparison. */
export const CAIRO_POC = {
    lng: 31.2357,
    lat: 30.0444,
    zoom: 15.35,
    pitch: 58,
    bearing: -28,
};

async function ensureRtl() {
    const gl = window.maplibregl;
    if (!gl?.setRTLTextPlugin) return;
    try {
        const st = gl.getRTLTextPluginStatus?.();
        if (st === 'loaded' || st === 'unavailable') return;
        const p = gl.setRTLTextPlugin(RTL_PLUGIN_URL, false);
        if (p?.then) await p.catch(() => {});
    } catch {
        /* ignore */
    }
}

function isMobile() {
    return window.matchMedia('(max-width: 767px), (pointer: coarse)').matches;
}

/**
 * @param {HTMLElement} root
 */
export async function createLivingWorldProcedural(root) {
    if (!root || root.dataset.ready === '1') return null;
    root.dataset.ready = '1';

    const mapEl = root.querySelector('[data-lwp-map]');
    const statusEl = root.querySelector('[data-lwp-status]');
    const metricsEl = root.querySelector('[data-lwp-metrics]');
    const mode = { value: 'procedural' }; // procedural | extrusion

    const setStatus = (t) => {
        if (statusEl) statusEl.textContent = t || '';
    };

    if (!mapEl || !window.maplibregl || !window.THREE) {
        setStatus('MapLibre or Three.js failed to load');
        return null;
    }

    const THREE = window.THREE;
    const mobile = isMobile();
    const perf = {
        styleMs: null,
        mapIdleMs: null,
        fps: null,
        fpsSamples: [],
        jsHeapMb: null,
        mode: 'procedural',
    };

    await ensureRtl();
    const tStyle = performance.now();
    setStatus('Loading style…');
    let style;
    try {
        style = await loadFunnityStyleV5(root.dataset.styleUrl);
    } catch (e) {
        console.warn(e);
        style = root.dataset.styleUrl;
    }
    perf.styleMs = Math.round(performance.now() - tStyle);

    // Ensure antialias for nicer façades.
    const tMap = performance.now();
    const map = new window.maplibregl.Map({
        container: mapEl,
        style,
        center: [CAIRO_POC.lng, CAIRO_POC.lat],
        zoom: CAIRO_POC.zoom,
        pitch: CAIRO_POC.pitch,
        bearing: CAIRO_POC.bearing,
        maxPitch: 72,
        canvasContextAttributes: { antialias: true },
        attributionControl: true,
    });
    map.addControl(new window.maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');

    await new Promise((r) => map.once('load', r));

    try {
        map.setLight({
            anchor: 'map',
            color: '#fff5eb',
            intensity: 0.4,
            position: [1.35, 210, 35],
        });
    } catch {
        /* ignore */
    }

    const proceduralLayer = createProceduralBuildingsLayer({
        THREE,
        origin: { lng: CAIRO_POC.lng, lat: CAIRO_POC.lat },
        maxBuildings: mobile ? 45 : 100,
        enableShadows: !mobile,
        enableTrees: !mobile,
    });

    // Keep an invisible building fill so vector tiles continue to load for querySourceFeatures.
    if (!map.getLayer('lwp-building-tile-keeper') && map.getSource('openmaptiles')) {
        try {
            map.addLayer({
                id: 'lwp-building-tile-keeper',
                type: 'fill',
                source: 'openmaptiles',
                'source-layer': 'building',
                paint: {
                    'fill-color': '#ffffff',
                    'fill-opacity': 0,
                },
            });
        } catch (e) {
            console.warn('tile keeper layer', e);
        }
    }

    // Start with procedural: hide native extrusion (tiles still loaded via keeper / optional flat).
    if (map.getLayer('building-3d')) {
        map.setLayoutProperty('building-3d', 'visibility', 'none');
    }
    if (map.getLayer('building')) {
        map.setPaintProperty('building', 'fill-opacity', 0);
        map.setLayoutProperty('building', 'visibility', 'visible');
    }

    map.addLayer(proceduralLayer);

    await new Promise((r) => map.once('idle', r));
    perf.mapIdleMs = Math.round(performance.now() - tMap);

    // Allow a second rebuild after tiles settle.
    setTimeout(() => proceduralLayer.rebuild?.(), 900);

    // FPS sampler (rAF over ~1.5s windows).
    let frames = 0;
    let fpsWindowStart = performance.now();
    let rafId = 0;
    const sampleFps = (now) => {
        frames += 1;
        if (now - fpsWindowStart >= 1000) {
            const fps = Math.round((frames * 1000) / (now - fpsWindowStart));
            perf.fpsSamples.push(fps);
            perf.fps = fps;
            frames = 0;
            fpsWindowStart = now;
            renderMetrics();
        }
        rafId = requestAnimationFrame(sampleFps);
    };
    rafId = requestAnimationFrame(sampleFps);

    const readHeap = () => {
        const mem = performance.memory;
        if (!mem) return null;
        return Math.round((mem.usedJSHeapSize / 1048576) * 10) / 10;
    };

    const renderMetrics = () => {
        if (!metricsEl) return;
        const stats = proceduralLayer.getStats?.() || {};
        perf.jsHeapMb = readHeap();
        let extrusionCount = 0;
        try {
            if (mode.value === 'extrusion') {
                extrusionCount = map.queryRenderedFeatures({ layers: ['building-3d'] }).length;
            }
        } catch {
            /* ignore */
        }
        const buildingN =
            mode.value === 'procedural' ? stats.buildingCount : extrusionCount;
        metricsEl.innerHTML = `
          <h3>POC metrics</h3>
          <dl>
            <div><dt>Mode</dt><dd>${mode.value}</dd></div>
            <div><dt>Buildings rendered</dt><dd>${buildingN}</dd></div>
            <div><dt>Procedural trees</dt><dd>${stats.treeCount ?? 0} (artistic)</dd></div>
            <div><dt>Mesh build</dt><dd>${stats.buildMs != null ? `${stats.buildMs} ms` : '—'}</dd></div>
            <div><dt>Style load</dt><dd>${perf.styleMs ?? '—'} ms</dd></div>
            <div><dt>Map idle</dt><dd>${perf.mapIdleMs ?? '—'} ms</dd></div>
            <div><dt>FPS (last 1s)</dt><dd>${perf.fps ?? '—'}</dd></div>
            <div><dt>FPS samples</dt><dd>${perf.fpsSamples.slice(-5).join(', ') || '—'}</dd></div>
            <div><dt>JS heap</dt><dd>${perf.jsHeapMb != null ? `${perf.jsHeapMb} MB` : 'n/a'}</dd></div>
            <div><dt>Shadows</dt><dd>${mobile ? 'off (mobile)' : 'on'}</dd></div>
          </dl>
          <p class="lwp-metrics__note">Façades, roofs, and trees are procedural art on real footprints — not verified building details.</p>
        `;
        root.dataset.metricsJson = JSON.stringify({
            mode: mode.value,
            procedural: stats,
            perf,
            extrusionCount,
            camera: CAIRO_POC,
            mobile,
        });
    };

    const setMode = (next) => {
        mode.value = next === 'extrusion' ? 'extrusion' : 'procedural';
        perf.mode = mode.value;
        root.dataset.mode = mode.value;
        root.querySelectorAll('[data-lwp-mode]').forEach((btn) => {
            btn.setAttribute('aria-pressed', btn.dataset.lwpMode === mode.value ? 'true' : 'false');
        });

        const procOn = mode.value === 'procedural';
        proceduralLayer.setVisible(procOn);
        if (map.getLayer('building-3d')) {
            map.setLayoutProperty('building-3d', 'visibility', procOn ? 'none' : 'visible');
        }
        if (map.getLayer('building')) {
            map.setLayoutProperty('building', 'visibility', 'visible');
            map.setPaintProperty('building', 'fill-opacity', procOn ? 0 : 0.9);
        }
        if (procOn) {
            // Ensure tiles then rebuild meshes.
            map.once('idle', () => proceduralLayer.rebuild?.());
            proceduralLayer.rebuild?.();
        }
        setStatus(procOn ? 'Procedural cinematic layer' : 'V5 fill-extrusion');
        map.triggerRepaint();
        renderMetrics();
    };

    root.querySelectorAll('[data-lwp-mode]').forEach((btn) => {
        btn.addEventListener('click', () => setMode(btn.dataset.lwpMode));
    });

    root.querySelector('[data-lwp-reset]')?.addEventListener('click', () => {
        map.easeTo({
            center: [CAIRO_POC.lng, CAIRO_POC.lat],
            zoom: CAIRO_POC.zoom,
            pitch: CAIRO_POC.pitch,
            bearing: CAIRO_POC.bearing,
            duration: 900,
        });
    });

    map.on('moveend', () => renderMetrics());
    setMode('procedural');
    setStatus('Procedural cinematic layer');
    renderMetrics();

    root._livingWorldProcedural = {
        map,
        setMode,
        getMetrics: () => JSON.parse(root.dataset.metricsJson || '{}'),
        proceduralLayer,
        destroy: () => cancelAnimationFrame(rafId),
    };

    return root._livingWorldProcedural;
}
