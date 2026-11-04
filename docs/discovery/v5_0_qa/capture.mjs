import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v5_0_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';

const PREVIEW_BASE = process.env.V5_URL || '';
const cities = ['cairo', 'port-said', 'alexandria'];

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function ready(page) {
    const el = page.locator('[data-living-world-v5]');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(
        () => document.querySelector('[data-living-world-v5]')?.dataset?.ready === '1',
        { timeout: 90000 }
    );
    await page.waitForTimeout(1200);
    return el;
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { previewBase: PREVIEW_BASE, cities: {}, shots: [], mobile: null };

try {
    if (!PREVIEW_BASE) throw new Error('Set V5_URL to a signed preview URL');

    const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
    const page = await context.newPage();
    await setLocale(page, 'en');
    await page.goto(PREVIEW_BASE, { waitUntil: 'networkidle', timeout: 120000 });
    await ready(page);

    for (const key of cities) {
        await page.evaluate(async (cityKey) => {
            const api = document.querySelector('[data-living-world-v5]')?._livingWorldV5;
            if (!api) throw new Error('api missing');
            await api.flyToCity(cityKey);
            await new Promise((r) => setTimeout(r, 900));
            api.sampleBuildings();
        }, key);

        await page.waitForTimeout(700);
        const metrics = await page.evaluate(() => {
            const root = document.querySelector('[data-living-world-v5]');
            try {
                return JSON.parse(root?.dataset?.metricsJson || '{}');
            } catch {
                return {};
            }
        });
        report.cities[key] = metrics;

        const shot = `01-${key}-desktop.jpg`;
        await page.screenshot({
            path: path.join(OUT, shot),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push(shot);

        await page.locator('[data-lw-stage]').screenshot({
            path: path.join(OUT, `02-${key}-stage.jpg`),
            type: 'jpeg',
            quality: 92,
        });
        report.shots.push(`02-${key}-stage.jpg`);
    }

    // Pan continuity check (Cairo → mid-delta geographic continuity)
    await page.evaluate(async () => {
        const api = document.querySelector('[data-living-world-v5]')?._livingWorldV5;
        const map = api?.map;
        if (!map) return;
        map.easeTo({
            center: [31.0, 30.6],
            zoom: 7.2,
            pitch: 42,
            bearing: -10,
            duration: 1200,
        });
        await new Promise((r) => map.once('idle', r));
    });
    await page.waitForTimeout(400);
    await page.screenshot({
        path: path.join(OUT, '03-egypt-overview-continuity.jpg'),
        type: 'jpeg',
        quality: 88,
        fullPage: false,
    });
    report.shots.push('03-egypt-overview-continuity.jpg');

    await context.close();

    {
        const mobileCtx = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await mobileCtx.newPage();
        await setLocale(page, 'en');
        const url = new URL(PREVIEW_BASE);
        url.searchParams.set('city', 'cairo');
        // preserve signature query: city may invalidate signature if signed without city.
        // Use base signed URL then switch city in-app.
        await page.goto(PREVIEW_BASE, { waitUntil: 'networkidle', timeout: 120000 });
        await ready(page);
        await page.evaluate(async () => {
            const api = document.querySelector('[data-living-world-v5]')?._livingWorldV5;
            await api?.flyToCity('cairo');
        });
        await page.waitForTimeout(800);
        await page.screenshot({
            path: path.join(OUT, '04-mobile-cairo.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('04-mobile-cairo.jpg');
        report.mobile = await page.evaluate(() => {
            try {
                return JSON.parse(
                    document.querySelector('[data-living-world-v5]')?.dataset?.metricsJson || '{}'
                );
            } catch {
                return {};
            }
        });
        await mobileCtx.close();
    }

    fs.writeFileSync(path.join(OUT, 'metrics.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (e) {
    console.error(e);
    fs.writeFileSync(path.join(OUT, 'metrics.json'), JSON.stringify({ error: String(e), ...report }, null, 2));
    process.exitCode = 1;
} finally {
    await browser.close();
}
