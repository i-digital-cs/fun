import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_qa');
fs.mkdirSync(OUT, { recursive: true });

const CHROME =
    process.env.CHROME ||
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';

const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794154683&signature=0e5f9c33fdafd559ffad0777360bf15d9dc3555163f0d2778e942baba51bedfe';

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function waitMap(page) {
    const el = page.locator('[data-living-map].lm-shell--v35');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(() => {
        const root = document.querySelector('[data-living-map].lm-shell--v35');
        if (!root || root.dataset.ready !== '1') return false;
        return !!root.querySelector('canvas');
    }, { timeout: 60000 }).catch(() => {});
    await page.waitForTimeout(2800);
    await el.scrollIntoViewIfNeeded();
    await page.evaluate(() => {
        try {
            document.querySelector('[data-living-map]')?._livingMap?.map?.resize?.();
        } catch {}
        window.dispatchEvent(new Event('resize'));
    });
    await page.waitForTimeout(900);
    return el;
}

async function shot(page, name) {
    const el = await waitMap(page);
    const file = path.join(OUT, name);
    await el.screenshot({ path: file, type: 'jpeg', quality: 88 });
    console.log('wrote', file, fs.statSync(file).size);
    return el;
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

try {
    // EN 1440
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '01-en-desktop-1440.jpg');
        await context.close();
    }

    // EN 1920
    {
        const context = await browser.newContext({ viewport: { width: 1920, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '02-en-desktop-1920.jpg');
        await context.close();
    }

    // EN mobile 390
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '03-en-mobile-390.jpg');
        await context.close();
    }

    // AR desktop
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'ar');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '04-ar-desktop-1440.jpg');
        await context.close();
    }

    // AR mobile
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await setLocale(page, 'ar');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '05-ar-mobile-390.jpg');
        await context.close();
    }

    // Selected event
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        const el = await waitMap(page);
        const card = el.locator('[data-lm-list-event]').first();
        if (await card.count()) {
            await card.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(1800);
        } else {
            const pin = el.locator('.lm-marker, .lm-collection, .lm-cluster-pin').first();
            if (await pin.count()) {
                await pin.click({ timeout: 5000 }).catch(() => {});
                await page.waitForTimeout(1800);
            }
        }
        await el.screenshot({
            path: path.join(OUT, '06-en-desktop-selected.jpg'),
            type: 'jpeg',
            quality: 88,
        });
        console.log('wrote selected');
        await context.close();
    }
} finally {
    await browser.close();
}
