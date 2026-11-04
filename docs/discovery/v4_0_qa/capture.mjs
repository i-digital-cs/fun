import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v4_0_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V4_URL ||
    'https://funnity.com/preview/living-map-v4?expires=1793781721&signature=a4d8aded201641f3446fece144691500db85a784a8cb0d55a0c14b92781d8202';

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function ready(page) {
    const el = page.locator('[data-living-map-v4]');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(
        () => document.querySelector('[data-living-map-v4]')?.dataset?.ready === '1',
        { timeout: 60000 }
    ).catch(() => {});
    await page.waitForTimeout(2500);
    await el.scrollIntoViewIfNeeded();
    return el;
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { preview: PREVIEW, shots: [], checks: {} };

try {
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        await context.addInitScript(() => {
            try {
                sessionStorage.removeItem('lm-v4-spotlight-dismissed');
            } catch {}
        });
        const page = await context.newPage();
        const apiHits = [];
        page.on('response', (res) => {
            const u = res.url();
            if (u.includes('/api/discovery/')) {
                apiHits.push({ url: u, status: res.status() });
            }
        });

        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);

        await page.waitForSelector('[data-lm-spotlight].is-open, [data-lm-spotlight]:not([hidden])', {
            timeout: 12000,
        }).catch(() => {});
        await page.waitForTimeout(800);

        report.checks.discover = await page.evaluate(() => {
            const root = document.querySelector('[data-living-map-v4]');
            const art = document.querySelector('[data-lm-v4-art]');
            const pins = [...document.querySelectorAll('.lm4-pin')];
            const spot = document.querySelector('[data-lm-spotlight]:not([hidden])');
            const disclaimer = document.querySelector('[data-lm-v4-disclaimer]');
            return {
                stageMode: root?.dataset?.stageMode || root?.className,
                artSrc: art?.getAttribute('src') || '',
                artNatural: { w: art?.naturalWidth || 0, h: art?.naturalHeight || 0 },
                pinCount: pins.length,
                pinKinds: pins.map((p) => p.dataset.kind),
                spotlightVisible: !!spot,
                spotlightTitle: spot?.querySelector('.lm4-spotlight__title')?.textContent?.trim() || '',
                disclaimer: disclaimer?.textContent?.trim() || '',
                disclaimerHidden: !!disclaimer?.hidden,
                carouselCards: document.querySelectorAll('[data-lm-rail-card]').length,
                status: document.querySelector('[data-lm-status]')?.textContent || '',
            };
        });
        report.checks.apiHits = apiHits;

        await page.screenshot({
            path: path.join(OUT, '01-desktop-discover.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('01-desktop-discover.jpg');

        await page.locator('[data-lm-stage]').screenshot({
            path: path.join(OUT, '02-desktop-scene-stage.jpg'),
            type: 'jpeg',
            quality: 92,
        });
        report.shots.push('02-desktop-scene-stage.jpg');

        await page.locator('.lm4-carousel').screenshot({
            path: path.join(OUT, '03-desktop-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('03-desktop-carousel.jpg');

        await page.locator('[data-lm-stage-mode="navigate"]').click();
        await page.waitForTimeout(3500);
        report.checks.navigate = await page.evaluate(() => {
            const root = document.querySelector('[data-living-map-v4]');
            const scene = document.querySelector('[data-lm-v4-scene]');
            const map = document.querySelector('[data-lm-v4-map]');
            const canvas = document.querySelector('[data-lm-map] canvas, .maplibregl-canvas');
            return {
                stageMode: root?.dataset?.stageMode,
                sceneHidden: !!scene?.hidden,
                mapHidden: !!map?.hidden,
                hasCanvas: !!canvas,
                mapMarkers: document.querySelectorAll('.lm4-map-marker').length,
                disclaimerHidden: !!document.querySelector('[data-lm-v4-disclaimer]')?.hidden,
            };
        });

        await page.screenshot({
            path: path.join(OUT, '04-desktop-navigate.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('04-desktop-navigate.jpg');

        await context.close();
    }

    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        await context.addInitScript(() => {
            try {
                sessionStorage.removeItem('lm-v4-spotlight-dismissed');
            } catch {}
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);
        await page.waitForTimeout(800);
        await page.screenshot({
            path: path.join(OUT, '05-mobile-discover.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('05-mobile-discover.jpg');
        await context.close();
    }

    fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (e) {
    console.error(e);
    fs.writeFileSync(
        path.join(OUT, 'capture_report.json'),
        JSON.stringify({ error: String(e), ...report }, null, 2)
    );
    process.exitCode = 1;
} finally {
    await browser.close();
}
