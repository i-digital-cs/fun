import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_5_qa');
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794158574&signature=38349c59121f3893af317728574e9242769c41d5ba35c10dd3ade57b857adeb1';

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});
const page = await (
    await browser.newContext({ viewport: { width: 1440, height: 1100 } })
).newPage();

const apiHits = [];
page.on('response', async (res) => {
    try {
        if (res.url().includes('/api/discovery/map')) {
            const json = await res.json().catch(() => null);
            if (json) apiHits.push({ url: res.url(), json });
        }
    } catch {}
});

await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
await page.waitForFunction(
    () => document.querySelector('[data-living-map].lm-shell--v35')?.dataset?.ready === '1',
    { timeout: 60000 }
);
await page.waitForTimeout(3000);

const deep = await page.evaluate(() => {
    const root = document.querySelector('[data-living-map]');
    const map = root._livingMap.map;
    const canvas = map.getCanvas();
    const w = canvas.clientWidth;
    const h = canvas.clientHeight;
    const layerIds = [
        'poi_r20',
        'poi_r7',
        'poi_r1',
        'poi_transit',
        'airport',
        'highway-shield-non-us',
        'highway-shield-us-interstate',
        'road_shield_us',
        'label_village',
        'label_town',
        'label_city',
        'label_city_capital',
    ].filter((id) => map.getLayer(id));

    const feats = map.queryRenderedFeatures(
        [
            [0, 0],
            [w, h],
        ],
        { layers: layerIds }
    );

    const byLayer = {};
    const interesting = [];
    for (const f of feats) {
        const layer = f.layer?.id || '?';
        byLayer[layer] = (byLayer[layer] || 0) + 1;
        const props = f.properties || {};
        if (!(layer.startsWith('poi') || layer.includes('shield') || layer === 'airport')) continue;
        const geom = f.geometry;
        let lngLat = null;
        if (geom?.type === 'Point' && Array.isArray(geom.coordinates)) {
            lngLat = { lng: geom.coordinates[0], lat: geom.coordinates[1] };
        }
        let screen = null;
        if (lngLat) {
            const p = map.project([lngLat.lng, lngLat.lat]);
            screen = { x: Math.round(p.x), y: Math.round(p.y) };
        }
        interesting.push({
            layer,
            name: props.name || props.name_en || null,
            ref: props.ref || null,
            class: props.class || props.subclass || null,
            maki: props.maki || null,
            lngLat,
            screen,
        });
    }

    const seen = new Set();
    const deduped = [];
    for (const item of interesting) {
        const key = [item.layer, item.name, item.ref, item.screen?.x, item.screen?.y].join('|');
        if (seen.has(key)) continue;
        seen.add(key);
        deduped.push(item);
    }

    const allLayers = map.getStyle().layers.map((l) => l.id);
    const customLayers = allLayers.filter((id) =>
        /lm|living|radius|funnity|origin|pulse|halo/i.test(id)
    );
    const sources = map.getStyle().sources;
    const customSources = {};
    for (const [k, v] of Object.entries(sources)) {
        if (/lm|living|radius|funnity|origin/i.test(k)) {
            customSources[k] = { type: v.type };
        }
    }

    const canvasRect = canvas.getBoundingClientRect();
    const markers = [...document.querySelectorAll('.maplibregl-marker')].map((el, i) => {
        const r = el.getBoundingClientRect();
        const tipX = r.left + r.width / 2 - canvasRect.left;
        const tipY = r.bottom - canvasRect.top;
        const ll = map.unproject([tipX, tipY]);
        return {
            i,
            kind: el.dataset.markerKind,
            count: el.dataset.count,
            cityId: el.dataset.cityId,
            eventId: el.dataset.eventId || null,
            aria: el.getAttribute('aria-label'),
            tipCanvas: { x: Math.round(tipX), y: Math.round(tipY) },
            lngLat: { lng: Number(ll.lng.toFixed(5)), lat: Number(ll.lat.toFixed(5)) },
            inCanvas: tipX >= 0 && tipX <= w && tipY >= 0 && tipY <= h,
            className: el.className,
        };
    });

    // Origin source data
    let originData = null;
    try {
        const src = map.getSource('lm-home-origin');
        originData = src?._data || src?._options?.data || null;
    } catch {}

    return {
        canvas: { w, h },
        center: map.getCenter(),
        zoom: map.getZoom(),
        bounds: {
            north: map.getBounds().getNorth(),
            south: map.getBounds().getSouth(),
            east: map.getBounds().getEast(),
            west: map.getBounds().getWest(),
        },
        byLayer,
        poiAndShieldCount: deduped.length,
        poiAndShields: deduped,
        customLayers,
        customSources,
        markers,
        originData,
    };
});

const lastApi = apiHits.at(-1)?.json || null;
const api = lastApi
    ? {
          url: apiHits.at(-1).url,
          markers: (lastApi.markers || []).map((m) => ({
              id: m.id,
              lat: m.latitude,
              lng: m.longitude,
              precision: m.location_precision,
              title: m.title,
              venue: m.venue_label,
              members: m.members_count,
              category: m.category_name,
              count: m.count,
          })),
          aggregates: (lastApi.city_aggregates || []).map((a) => ({
              type: a.type,
              city_id: a.city_id,
              city: a.city_name,
              lat: a.latitude,
              lng: a.longitude,
              count: a.count,
              sample: a.sample_event_ids,
              label: a.label,
          })),
          clusters: lastApi.clusters || [],
          meta: lastApi.meta,
      }
    : null;

const el = page.locator('[data-living-map].lm-shell--v35');

await page.evaluate(() => {
    const map = document.querySelector('[data-living-map]')._livingMap.map;
    [
        'poi_r20',
        'poi_r7',
        'poi_r1',
        'poi_transit',
        'airport',
        'highway-shield-non-us',
        'highway-shield-us-interstate',
        'road_shield_us',
    ].forEach((id) => {
        if (map.getLayer(id)) map.setLayoutProperty(id, 'visibility', 'none');
    });
});
await page.waitForTimeout(500);
await el.screenshot({ path: path.join(OUT, 'integrity-03-pois-hidden.jpg'), type: 'jpeg', quality: 90 });

await page.evaluate(() => {
    const map = document.querySelector('[data-living-map]')._livingMap.map;
    [
        'poi_r20',
        'poi_r7',
        'poi_r1',
        'poi_transit',
        'airport',
        'highway-shield-non-us',
        'highway-shield-us-interstate',
        'road_shield_us',
    ].forEach((id) => {
        if (map.getLayer(id)) map.setLayoutProperty(id, 'visibility', 'visible');
    });
    document.querySelectorAll('.maplibregl-marker').forEach((el) => {
        el.style.visibility = 'hidden';
    });
});
await page.waitForTimeout(400);
await el.screenshot({ path: path.join(OUT, 'integrity-04-basemap-only.jpg'), type: 'jpeg', quality: 90 });

// Hide markers + POIs/shields — only labels + radius remain
await page.evaluate(() => {
    const map = document.querySelector('[data-living-map]')._livingMap.map;
    [
        'poi_r20',
        'poi_r7',
        'poi_r1',
        'poi_transit',
        'airport',
        'highway-shield-non-us',
        'highway-shield-us-interstate',
        'road_shield_us',
    ].forEach((id) => {
        if (map.getLayer(id)) map.setLayoutProperty(id, 'visibility', 'none');
    });
});
await page.waitForTimeout(400);
await el.screenshot({
    path: path.join(OUT, 'integrity-05-radius-and-labels-only.jpg'),
    type: 'jpeg',
    quality: 90,
});

const report = { capturedAt: new Date().toISOString(), preview: PREVIEW, deep, api };
fs.writeFileSync(path.join(OUT, 'marker_integrity_poi.json'), JSON.stringify(report, null, 2));
console.log(
    JSON.stringify(
        {
            eventMarkers: deep.markers,
            byLayer: deep.byLayer,
            customLayers: deep.customLayers,
            customSources: deep.customSources,
            poiCount: deep.poiAndShieldCount,
            shields: deep.poiAndShields.filter((p) => p.layer.includes('shield')).slice(0, 20),
            pois: deep.poiAndShields.filter((p) => p.layer.startsWith('poi') || p.layer === 'airport').slice(0, 30),
            apiMarkerCount: api?.markers?.length,
            apiAggCount: api?.aggregates?.length,
            apiAggs: api?.aggregates,
        },
        null,
        2
    )
);

await browser.close();
