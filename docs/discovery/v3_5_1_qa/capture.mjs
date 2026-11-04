import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_1_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794155442&signature=4272bfa20707dcaa786a50c23dfdd1bf39d712cf331b5bbb5b4c17c9bdc9bb61';

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
    await page.waitForFunction(() => {
        const root = document.querySelector('[data-living-map].lm-shell--v35');
        return root?.dataset?.ready === '1' && !!root.querySelector('canvas');
    }, { timeout: 60000 }).catch(() => {});
    await page.waitForTimeout(2600);
    await el.scrollIntoViewIfNeeded();
    await page.evaluate(() => {
        try {
            document.querySelector('[data-living-map]')?._livingMap?.map?.resize?.();
        } catch {}
    });
    await page.waitForTimeout(700);
    return el;
}

async function shotBox(page, selector, file) {
    const loc = page.locator(selector).first();
    await loc.waitFor({ state: 'visible', timeout: 15000 });
    await loc.screenshot({ path: path.join(OUT, file), type: 'jpeg', quality: 90 });
    console.log('wrote', file);
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

try {
    // EN desktop full + components
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await el.screenshot({ path: path.join(OUT, '04-full-desktop-en.jpg'), type: 'jpeg', quality: 88 });
        console.log('wrote full desktop');

        await shotBox(page, '[data-lm-loc-panel]', '02-location-filter.jpg');

        // Prefer an exact pin; fallback city collection then carousel.
        const pin = el.locator('.lm-marker').first();
        if (await pin.count()) {
            await pin.click({ timeout: 5000 }).catch(() => {});
        } else {
            const agg = el.locator('.lm-collection, .lm-cluster-pin').first();
            if (await agg.count()) await agg.click({ timeout: 5000 }).catch(() => {});
            await page.waitForTimeout(1200);
            const cityEv = el.locator('[data-city-event-id]').first();
            if (await cityEv.count()) await cityEv.click({ timeout: 4000 }).catch(() => {});
            else {
                const card = el.locator('[data-lm-list-event]').first();
                if (await card.count()) await card.click({ timeout: 4000 }).catch(() => {});
            }
        }
        await page.waitForTimeout(1600);

        const openCard = page.locator('[data-lm-card].is-open, [data-lm-card]:not([hidden])');
        if (await openCard.count()) {
            await shotBox(page, '[data-lm-card]', '01-floating-event-card.jpg');
        } else {
            console.log('no open floating card — capturing stage instead');
            await el.screenshot({
                path: path.join(OUT, '01-floating-event-card.jpg'),
                type: 'jpeg',
                quality: 88,
            });
        }

        // Pins close-up: crop map stage center
        await shotBox(page, '[data-lm-stage]', '03-glowing-pins-stage.jpg');
        await context.close();
    }

    // EN mobile
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await el.screenshot({ path: path.join(OUT, '05-full-mobile-en.jpg'), type: 'jpeg', quality: 88 });
        console.log('wrote mobile');
        await context.close();
    }

    // AR desktop
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'ar');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await ready(page);
        await el.screenshot({ path: path.join(OUT, '06-full-desktop-ar.jpg'), type: 'jpeg', quality: 88 });
        console.log('wrote ar desktop');
        await context.close();
    }
} finally {
    await browser.close();
}
