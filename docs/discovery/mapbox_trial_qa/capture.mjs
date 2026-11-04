import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/mapbox_trial_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW = process.env.MBX_URL || '';

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = { preview: PREVIEW, shots: [], metrics: null, cities: {}, errors: [] };

try {
    if (!PREVIEW) throw new Error('Set MBX_URL');

    const context = await browser.newContext({ viewport: { width: 1440, height: 1100 } });
    const page = await context.newPage();
    page.on('pageerror', (e) => report.errors.push(String(e)));

    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: csrf, locale: 'en' },
        headers: { Referer: 'https://funnity.com/' },
    });

    await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 120000 });
    await page.waitForFunction(
        () => document.querySelector('[data-mapbox-standard-trial]')?.dataset?.ready === '1',
        { timeout: 60000 }
    );
    await page.waitForTimeout(1200);

    report.metrics = await page.evaluate(() => {
        try {
            return JSON.parse(
                document.querySelector('[data-mapbox-standard-trial]')?.dataset?.metricsJson || '{}'
            );
        } catch {
            return {};
        }
    });

    const gateVisible = await page.locator('[data-mbx-token-gate]:not([hidden])').count();
    report.tokenGateVisible = gateVisible > 0;

    await page.screenshot({
        path: path.join(OUT, '00-token-gate-or-initial.jpg'),
        type: 'jpeg',
        quality: 90,
        fullPage: true,
    });
    report.shots.push('00-token-gate-or-initial.jpg');

    if (!report.metrics?.blocked) {
        for (const city of ['cairo', 'alexandria', 'port-said']) {
            await page.evaluate(async (key) => {
                const api = document.querySelector('[data-mapbox-standard-trial]')?._mapboxStandardTrial;
                await api?.flyToCity?.(key);
                const map = api?.map;
                if (map) {
                    await new Promise((r) => map.once('idle', r));
                }
                await new Promise((r) => setTimeout(r, 2500));
            }, city);
            await page.waitForTimeout(1500);
            report.cities[city] = await page.evaluate(() => {
                try {
                    return JSON.parse(
                        document.querySelector('[data-mapbox-standard-trial]')?.dataset
                            ?.metricsJson || '{}'
                    );
                } catch {
                    return {};
                }
            });
            const shot = `01-${city}-desktop.jpg`;
            await page.screenshot({
                path: path.join(OUT, shot),
                type: 'jpeg',
                quality: 90,
                fullPage: true,
            });
            report.shots.push(shot);
            await page.locator('[data-mbx-stage]').screenshot({
                path: path.join(OUT, `02-${city}-stage.jpg`),
                type: 'jpeg',
                quality: 92,
            });
            report.shots.push(`02-${city}-stage.jpg`);
        }

        const mobileCtx = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
        });
        const mpage = await mobileCtx.newPage();
        await mpage.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 120000 });
        await mpage.waitForFunction(
            () => document.querySelector('[data-mapbox-standard-trial]')?.dataset?.ready === '1',
            { timeout: 60000 }
        );
        await mpage.waitForTimeout(2500);
        await mpage.screenshot({
            path: path.join(OUT, '03-mobile-cairo.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('03-mobile-cairo.jpg');
        await mobileCtx.close();
    }

    fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
    console.log(JSON.stringify(report, null, 2));
    await context.close();
} catch (e) {
    report.errors.push(String(e));
    fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
    console.error(e);
    process.exitCode = 1;
} finally {
    await browser.close();
}
