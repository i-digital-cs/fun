import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_6_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794163661&signature=a65759f91a870d0ad3ea64e9aaf175ccc7b78336b98f4b4499576fa97a877269';

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
        () => document.querySelector('[data-living-map].lm-shell--v35')?.dataset?.ready === '1',
        { timeout: 60000 }
    ).catch(() => {});
    await page.waitForTimeout(3200);
    await el.scrollIntoViewIfNeeded();
    await page.evaluate(() => {
        try {
            document.querySelector('[data-living-map]')?._livingMap?.map?.resize?.();
        } catch {}
    });
    await page.waitForTimeout(900);
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

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { preview: PREVIEW, shots: [], checks: {} };

try {
    // Clear spotlight dismiss from prior runs via fresh context storage
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        await context.addInitScript(() => {
            try {
                sessionStorage.removeItem('lm-v35-spotlight-dismissed');
            } catch {}
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);

        const markerInfo = await page.evaluate(() =>
            [...document.querySelectorAll('.lm-marker')].map((m) => ({
                kind: m.dataset.markerKind,
                count: m.dataset.count,
                cityId: m.dataset.cityId,
                aria: m.getAttribute('aria-label'),
                transform: m.style.transform,
            }))
        );
        report.checks.markers = markerInfo;

        const spotlightVisible = await page.locator('[data-lm-spotlight]:not([hidden])').count();
        report.checks.spotlightVisible = spotlightVisible > 0;

        await el.screenshot({
            path: path.join(OUT, '01-desktop-initial-spotlight.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('01-desktop-initial-spotlight.jpg');

        const carousel = page.locator('[data-lm-carousel-wrap]');
        await carousel.screenshot({
            path: path.join(OUT, '02-desktop-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('02-desktop-carousel.jpg');

        // Select first carousel card → floating card + marker highlight
        const card = page.locator('[data-lm-list-event]').first();
        if (await card.count()) {
            await card.click({ force: true });
            await page.waitForTimeout(1600);
            await el.screenshot({
                path: path.join(OUT, '03-selected-event-card.jpg'),
                type: 'jpeg',
                quality: 90,
            });
            report.shots.push('03-selected-event-card.jpg');
            report.checks.selectedCard = await page.locator('[data-lm-card]:not([hidden])').count();
        }

        // Aggregate list
        const agg = page.locator('.lm-marker--agg').first();
        if (await agg.count()) {
            await agg.click({ force: true });
            await page.waitForTimeout(2200);
            const panel = page.locator('[data-lm-city-panel]:not([hidden])');
            await panel.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
            if (await panel.count()) {
                await panel.screenshot({
                    path: path.join(OUT, '04-aggregate-event-list.jpg'),
                    type: 'jpeg',
                    quality: 92,
                });
                report.shots.push('04-aggregate-event-list.jpg');
                report.checks.aggregateList = true;
            }
        }

        // Empty-state behavior
        await page.evaluate(() => {
            const root = document.querySelector('[data-living-map]');
            const list = root?.querySelector('[data-lm-list]');
            const spot = root?.querySelector('[data-lm-spotlight]');
            if (list) {
                list.innerHTML =
                    '<p class="lm-rail__empty">No events in this view yet. Pan the map or open a city collection.</p>';
            }
            if (spot) spot.hidden = true;
            root?.querySelectorAll('.maplibregl-marker').forEach((m) => {
                m.style.visibility = 'hidden';
            });
        });
        await page.waitForTimeout(400);
        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, '08-empty-state.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('08-empty-state.jpg');

        const homeOk = await page.evaluate(async () => {
            const res = await fetch('https://funnity.com/', { credentials: 'same-origin' });
            const html = await res.text();
            return !html.includes('lm-shell--v35');
        });
        report.checks.homepageIsolationFetch = homeOk;

        await context.close();
    }

    // Mobile
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        await context.addInitScript(() => {
            try {
                sessionStorage.removeItem('lm-v35-spotlight-dismissed');
            } catch {}
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);
        await el.screenshot({
            path: path.join(OUT, '05-mobile-initial.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('05-mobile-initial.jpg');

        const rail = page.locator('[data-lm-list]');
        await rail.evaluate((node) => {
            node.scrollBy({ left: 220, behavior: 'instant' });
        });
        await page.waitForTimeout(500);
        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, '06-mobile-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('06-mobile-carousel.jpg');
        await context.close();
    }

    // Arabic RTL
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        await context.addInitScript(() => {
            try {
                sessionStorage.removeItem('lm-v35-spotlight-dismissed');
            } catch {}
        });
        const page = await context.newPage();
        await setLocale(page, 'ar');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await pauseAnim(page);
        await el.screenshot({
            path: path.join(OUT, '07-arabic-rtl-desktop.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('07-arabic-rtl-desktop.jpg');
        report.checks.rtl = {
            lang: await page.locator('html').getAttribute('lang'),
            dir: await page.locator('html').getAttribute('dir'),
        };
        await context.close();
    }

    // Homepage isolation via browser
    {
        const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
        report.checks.homepageIsolation = (await page.locator('.lm-shell--v35').count()) === 0;
        await context.close();
    }
} finally {
    await browser.close();
}

fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
