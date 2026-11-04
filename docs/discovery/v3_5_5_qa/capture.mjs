import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_5_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794158149&signature=6e978ecdc3cf1ac7c1f00af04c561e15030979213b22125f66b630fce0379fce';

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function ready(page) {
    const el = page.locator('[data-living-map].lm-shell--v35');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(
        () => {
            const root = document.querySelector('[data-living-map].lm-shell--v35');
            return root?.dataset?.ready === '1' && !!root.querySelector('canvas');
        },
        { timeout: 60000 }
    ).catch(() => {});
    await page.waitForTimeout(2800);
    await el.scrollIntoViewIfNeeded();
    await page.evaluate(() => {
        try {
            document.querySelector('[data-living-map]')?._livingMap?.map?.resize?.();
        } catch {}
    });
    await page.waitForTimeout(800);
    return el;
}

async function pauseAnim(page) {
    await page.addStyleTag({
        content: `
      .lm-shell--v35 .lm-marker.is-float .lm-marker__visual,
      .lm-shell--v35 .lm-marker.is-selected.is-float .lm-marker__visual,
      .lm-shell--v35 .lm-marker-halo { animation: none !important; }
    `,
    });
}

async function auditMarkers(page) {
    return page.evaluate(() => {
        const root = document.querySelector('[data-living-map]');
        const map = root?._livingMap?.map;
        const stage = root?.querySelector('.lm-stage')?.getBoundingClientRect();
        const markers = [...root.querySelectorAll('.maplibregl-marker')].map((el, i) => {
            const r = el.getBoundingClientRect();
            const btn = el.querySelector('button, .lm-marker, .lm-marker--agg') || el;
            const kind =
                el.querySelector('[data-marker-kind]')?.dataset?.markerKind ||
                (el.querySelector('.lm-marker--agg') ? 'city-aggregate' : 'unknown');
            const lngLat = el._lngLat || null;
            return {
                index: i,
                kind,
                count: el.querySelector('[data-count]')?.dataset?.count || el.querySelector('.lm-marker__count')?.textContent || '',
                cityId: el.querySelector('[data-city-id]')?.dataset?.cityId || '',
                aria: el.querySelector('[aria-label]')?.getAttribute('aria-label') || '',
                title: el.querySelector('[title]')?.getAttribute('title') || '',
                screen: {
                    x: Math.round(r.left + r.width / 2 - (stage?.left || 0)),
                    y: Math.round(r.top + r.height / 2 - (stage?.top || 0)),
                },
                classes: el.className,
                lngLat,
            };
        });
        const center = map?.getCenter?.();
        const zoom = map?.getZoom?.();
        const bounds = map?.getBounds?.();
        return {
            center,
            zoom,
            bounds: bounds
                ? {
                      north: bounds.getNorth(),
                      south: bounds.getSouth(),
                      east: bounds.getEast(),
                      west: bounds.getWest(),
                  }
                : null,
            markers,
        };
    });
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { preview: PREVIEW, shots: [], audit: null, interactions: {} };

try {
    // 1) Desktop EN initial + filter + aggregate + list + card
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);

        report.audit = await auditMarkers(page);
        fs.writeFileSync(path.join(OUT, 'ui_audit.json'), JSON.stringify(report.audit, null, 2));

        await el.screenshot({ path: path.join(OUT, '01-desktop-initial.jpg'), type: 'jpeg', quality: 90 });
        report.shots.push('01-desktop-initial.jpg');

        const loc = page.locator('[data-lm-loc-panel]').first();
        await loc.screenshot({ path: path.join(OUT, '06-location-filter.jpg'), type: 'jpeg', quality: 92 });
        report.shots.push('06-location-filter.jpg');

        // Approximate city aggregate (prefer Port Said count 4)
        const aggs = page.locator('.lm-marker--agg');
        const aggCount = await aggs.count();
        report.interactions.aggregateCount = aggCount;
        let portSaid = null;
        for (let i = 0; i < aggCount; i++) {
            const a = aggs.nth(i);
            const label = ((await a.getAttribute('aria-label')) || '') + ((await a.getAttribute('title')) || '');
            const count = await a.getAttribute('data-count');
            if (count === '4' || /port said/i.test(label)) {
                portSaid = a;
                break;
            }
        }
        const agg = portSaid || aggs.first();
        if (await agg.count()) {
            await agg.scrollIntoViewIfNeeded().catch(() => {});
            await agg.click({ force: true, timeout: 8000 });
            await page.waitForTimeout(2200);
            await page.locator('[data-lm-city-panel]:not([hidden])').waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
            await el.screenshot({ path: path.join(OUT, '03-approximate-city-aggregate.jpg'), type: 'jpeg', quality: 90 });
            report.shots.push('03-approximate-city-aggregate.jpg');

            const panel = page.locator('[data-lm-city-panel]').first();
            if (await panel.isVisible()) {
                await panel.screenshot({ path: path.join(OUT, '04-aggregate-event-list.jpg'), type: 'jpeg', quality: 92 });
                report.shots.push('04-aggregate-event-list.jpg');
                report.interactions.aggregateList = true;

                const row = panel.locator('[data-city-event-id]').first();
                if (await row.count()) {
                    await row.click({ force: true, timeout: 5000 });
                    await page.waitForTimeout(1600);
                    const card = page.locator('[data-lm-card]:not([hidden])').first();
                    await card.waitFor({ state: 'visible', timeout: 8000 }).catch(() => {});
                    if (await card.isVisible()) {
                        await card.screenshot({ path: path.join(OUT, '05-floating-event-card.jpg'), type: 'jpeg', quality: 92 });
                        report.shots.push('05-floating-event-card.jpg');
                        report.interactions.floatingCard = true;
                    }
                    await el.screenshot({ path: path.join(OUT, '05b-desktop-card-context.jpg'), type: 'jpeg', quality: 88 });
                }
            }
        }

        // Exact venue pin — fly to known exact event (id 605) if not in view
        await page.evaluate(async () => {
            const root = document.querySelector('[data-living-map]');
            const api = root?._livingMap;
            const map = api?.map;
            if (!map) return;
            map.flyTo({ center: [30.9532648, 29.9803057], zoom: 12.4, pitch: 40, duration: 0 });
        });
        await page.waitForTimeout(3500);
        const exactPin = page.locator('.lm-marker[data-marker-kind="exact-event"]').first();
        if (await exactPin.count()) {
            await exactPin.click({ force: true, timeout: 6000 });
            await page.waitForTimeout(1500);
            await el.screenshot({ path: path.join(OUT, '02-exact-location-marker.jpg'), type: 'jpeg', quality: 90 });
            report.shots.push('02-exact-location-marker.jpg');
            report.interactions.exactMarker = true;
        } else {
            // Fallback: any non-agg teardrop
            const pin = page.locator('.lm-marker:not(.lm-marker--agg)').first();
            if (await pin.count()) {
                await pin.click({ force: true, timeout: 5000 }).catch(() => {});
                await page.waitForTimeout(1200);
                await el.screenshot({ path: path.join(OUT, '02-exact-location-marker.jpg'), type: 'jpeg', quality: 90 });
                report.shots.push('02-exact-location-marker.jpg');
                report.interactions.exactMarker = 'fallback-non-agg';
            } else {
                report.interactions.exactMarker = false;
            }
        }

        await context.close();
    }

    // 7) Mobile
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);
        await el.screenshot({ path: path.join(OUT, '07-mobile-layout.jpg'), type: 'jpeg', quality: 90 });
        report.shots.push('07-mobile-layout.jpg');
        await context.close();
    }

    // 8) Arabic RTL
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
        const page = await context.newPage();
        await setLocale(page, 'ar');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);
        await el.screenshot({ path: path.join(OUT, '08-arabic-rtl.jpg'), type: 'jpeg', quality: 90 });
        report.shots.push('08-arabic-rtl.jpg');

        const htmlLang = await page.locator('html').getAttribute('lang');
        const dir = await page.locator('html').getAttribute('dir');
        report.interactions.rtl = { htmlLang, dir };
        await context.close();
    }

    // Homepage isolation
    {
        const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
        const hasV35 = await page.locator('.lm-shell--v35').count();
        report.interactions.homepageIsolation = hasV35 === 0;
        await context.close();
    }
} finally {
    await browser.close();
}

fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
