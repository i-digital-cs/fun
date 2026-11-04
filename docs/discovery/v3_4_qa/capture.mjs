import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;

const OUT = path.resolve('docs/discovery/v3_4_qa');
fs.mkdirSync(OUT, { recursive: true });

const CHROME =
    process.env.CHROME ||
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function shot(page, name) {
    const el = page.locator('[data-living-map]');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(() => {
        const root = document.querySelector('[data-living-map]');
        if (!root || root.dataset.ready !== '1') return false;
        const cards = root.querySelectorAll('[data-lm-list-event]').length;
        const markers = root.querySelectorAll('.lm-marker,.lm-collection').length;
        return !!root.querySelector('canvas') && (cards > 0 || markers > 0);
    }, { timeout: 60000 }).catch(() => {});
    await page.waitForTimeout(1500);
    await el.scrollIntoViewIfNeeded();
    await page.waitForTimeout(500);
    // Resize map after scroll into view
    await page.evaluate(() => {
        const api = document.querySelector('[data-living-map]')?._livingMap;
        try { api?.map?.resize?.(); } catch {}
        window.dispatchEvent(new Event('resize'));
    });
    await page.waitForTimeout(800);
    const file = path.join(OUT, name);
    await el.screenshot({ path: file, type: 'jpeg', quality: 85 });
    console.log('wrote', file, fs.statSync(file).size);
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

try {
    // EN desktop 1440
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '01-en-desktop-1440.jpg');
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
        await page.goto('https://funnity.com/', { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '02-en-mobile-390.jpg');
        await context.close();
    }

    // AR desktop 1440
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await page.goto('https://funnity.com/ar', { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '03-ar-desktop-1440.jpg');
        await context.close();
    }

    // AR mobile 390
    {
        const context = await browser.newContext({
            viewport: { width: 390, height: 900 },
            isMobile: true,
            hasTouch: true,
        });
        const page = await context.newPage();
        await page.goto('https://funnity.com/ar', { waitUntil: 'networkidle', timeout: 90000 });
        await shot(page, '04-ar-mobile-390.jpg');
        await context.close();
    }

    // Interaction: selected card + list mode
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'networkidle', timeout: 90000 });
        const el = page.locator('[data-living-map]');
        await el.waitFor({ state: 'visible', timeout: 45000 });
        await page.waitForTimeout(3500);
        await el.scrollIntoViewIfNeeded();
        await page.waitForTimeout(800);
        const card = el.locator('[data-lm-list-event]').first();
        if (await card.count()) {
            await card.click({ timeout: 8000 }).catch(() => {});
            await page.waitForTimeout(1500);
        }
        await el.screenshot({
            path: path.join(OUT, '05-en-desktop-selected.jpg'),
            type: 'jpeg',
            quality: 85,
        });
        await el.locator('[data-lm-view="list"]').click();
        await page.waitForTimeout(700);
        await el.screenshot({
            path: path.join(OUT, '06-en-desktop-list-mode.jpg'),
            type: 'jpeg',
            quality: 85,
        });
        console.log('wrote interaction shots');
        await context.close();
    }
} finally {
    await browser.close();
}
