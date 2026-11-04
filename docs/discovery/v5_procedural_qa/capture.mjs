import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v5_procedural_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW = process.env.LWP_URL || '';
const V5_URL = process.env.V5_URL || '';

async function setLocale(page) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale: 'en' },
        headers: { Referer: 'https://funnity.com/' },
    });
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { preview: PREVIEW, v5: V5_URL, shots: [], modes: {}, mobile: null, errors: [] };

try {
    if (!PREVIEW) throw new Error('Set LWP_URL');

    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
        const page = await context.newPage();
        page.on('pageerror', (e) => report.errors.push(String(e)));
        page.on('console', (msg) => {
            if (msg.type() === 'error') report.errors.push(`console: ${msg.text()}`);
        });

        await setLocale(page);
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 120000 });
        await page.waitForFunction(
            () => document.querySelector('[data-living-world-procedural]')?.dataset?.ready === '1',
            { timeout: 90000 }
        );
        // Allow mesh rebuild
        await page.waitForTimeout(2500);

        // Wait until procedural buildings exist
        await page.waitForFunction(
            () => {
                const api = document.querySelector('[data-living-world-procedural]')?._livingWorldProcedural;
                const n = api?.proceduralLayer?.getStats?.()?.buildingCount || 0;
                return n > 5;
            },
            { timeout: 30000 }
        ).catch(() => {});

        await page.waitForTimeout(1500);

        report.modes.procedural = await page.evaluate(() => {
            const root = document.querySelector('[data-living-world-procedural]');
            try {
                return JSON.parse(root?.dataset?.metricsJson || '{}');
            } catch {
                return {};
            }
        });

        await page.screenshot({
            path: path.join(OUT, '01-procedural-desktop.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('01-procedural-desktop.jpg');

        await page.locator('[data-lwp-stage]').screenshot({
            path: path.join(OUT, '02-procedural-stage.jpg'),
            type: 'jpeg',
            quality: 92,
        });
        report.shots.push('02-procedural-stage.jpg');

        await page.locator('[data-lwp-mode="extrusion"]').click();
        await page.waitForTimeout(2000);

        report.modes.extrusion = await page.evaluate(() => {
            const root = document.querySelector('[data-living-world-procedural]');
            try {
                return JSON.parse(root?.dataset?.metricsJson || '{}');
            } catch {
                return {};
            }
        });

        await page.locator('[data-lwp-stage]').screenshot({
            path: path.join(OUT, '03-extrusion-stage.jpg'),
            type: 'jpeg',
            quality: 92,
        });
        report.shots.push('03-extrusion-stage.jpg');

        await page.screenshot({
            path: path.join(OUT, '04-extrusion-desktop.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('04-extrusion-desktop.jpg');

        await context.close();
    }

    // Optional V5 same-ish framing reference shot
    if (V5_URL) {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
        const page = await context.newPage();
        await setLocale(page);
        await page.goto(V5_URL, { waitUntil: 'networkidle', timeout: 120000 });
        await page.waitForFunction(
            () => document.querySelector('[data-living-world-v5]')?.dataset?.ready === '1',
            { timeout: 90000 }
        );
        await page.evaluate(async () => {
            await document.querySelector('[data-living-world-v5]')?._livingWorldV5?.flyToCity?.('cairo');
        });
        await page.waitForTimeout(2000);
        await page.locator('[data-lw-stage]').screenshot({
            path: path.join(OUT, '05-v5-cairo-stage.jpg'),
            type: 'jpeg',
            quality: 92,
        });
        report.shots.push('05-v5-cairo-stage.jpg');
        await context.close();
    }

    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await setLocale(page);
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 120000 });
        await page.waitForFunction(
            () => document.querySelector('[data-living-world-procedural]')?.dataset?.ready === '1',
            { timeout: 90000 }
        );
        await page.waitForTimeout(3500);
        report.mobile = await page.evaluate(() => {
            try {
                return JSON.parse(
                    document.querySelector('[data-living-world-procedural]')?.dataset?.metricsJson ||
                        '{}'
                );
            } catch {
                return {};
            }
        });
        await page.screenshot({
            path: path.join(OUT, '06-mobile-procedural.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('06-mobile-procedural.jpg');
        await context.close();
    }

    fs.writeFileSync(path.join(OUT, 'metrics.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
} catch (e) {
    console.error(e);
    report.errors.push(String(e));
    fs.writeFileSync(path.join(OUT, 'metrics.json'), JSON.stringify(report, null, 2));
    process.exitCode = 1;
} finally {
    await browser.close();
}
