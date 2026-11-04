import pw from '/home/d14098/funnity.com/tmp-pw/package/index.js';
import path from 'node:path';
import fs from 'node:fs';

const { chromium } = pw;
const OUT = path.resolve('docs/discovery/v3_5_7_qa');
fs.mkdirSync(OUT, { recursive: true });
const CHROME =
    '/home/d14098/.cache/ms-playwright/chromium_headless_shell-1187/chrome-linux/headless_shell';
const PREVIEW =
    process.env.V35_URL ||
    'https://funnity.com/preview/living-map-v35?expires=1794212414&signature=4cb89d5d85cb49a7ca07a7cce53809b3163ffefbcb659450237521e281254bf1';

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
    await page.waitForTimeout(3800);
    await el.scrollIntoViewIfNeeded();
    await page.evaluate(() => {
        try {
            document.querySelector('[data-living-map]')?._livingMap?.map?.resize?.();
        } catch {}
    });
    await page.waitForTimeout(1000);
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

        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 12000 }).catch(() => {});
        await page.waitForTimeout(600);

        report.checks.spotlight = await page.evaluate(() => {
            const spot = document.querySelector('[data-lm-spotlight]:not([hidden])');
            const lit = document.querySelector('.lm-marker.is-spotlight');
            return {
                visible: !!spot,
                title: spot?.querySelector('[data-lm-spotlight-title]')?.textContent || '',
                place: spot?.querySelector('[data-lm-spotlight-place]')?.textContent || '',
                meta: spot?.querySelector('[data-lm-spotlight-meta]')?.textContent || '',
                markerLit: !!lit,
                markerKind: lit?.dataset?.markerKind || null,
                markerCount: lit?.dataset?.count || null,
                markerCityId: lit?.dataset?.cityId || null,
            };
        });

        await page.screenshot({
            path: path.join(OUT, '01-desktop-initial-spotlight.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('01-desktop-initial-spotlight.jpg');

        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, '03-desktop-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('03-desktop-carousel.jpg');

        // Selected card from carousel
        const card = page.locator('[data-lm-list-event]').first();
        if (await card.count()) {
            await card.click({ force: true });
            await page.waitForTimeout(1600);
            await page.screenshot({
                path: path.join(OUT, '02-desktop-selected-card.jpg'),
                type: 'jpeg',
                quality: 90,
                fullPage: true,
            });
            report.shots.push('02-desktop-selected-card.jpg');
            report.checks.selectedCard = await page.locator('[data-lm-card]:not([hidden])').count();
        }

        // Dismiss spotlight path: reload fresh then dismiss
        await page.evaluate(() => {
            try {
                sessionStorage.removeItem('lm-v35-spotlight-dismissed');
            } catch {}
        });
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);
        await pauseAnim(page);
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 12000 }).catch(() => {});
        await page.locator('[data-lm-spotlight-dismiss]').click({ force: true });
        await page.waitForTimeout(400);
        report.checks.dismissed = await page.evaluate(() => ({
            spotHidden: !!document.querySelector('[data-lm-spotlight][hidden]'),
            litGone: !document.querySelector('.lm-marker.is-spotlight'),
        }));
        await page.screenshot({
            path: path.join(OUT, '07-spotlight-dismissed.jpg'),
            type: 'jpeg',
            quality: 88,
            fullPage: true,
        });
        report.shots.push('07-spotlight-dismissed.jpg');

        // Aggregate list
        await page.evaluate(() => {
            document.querySelector('[data-lm-card]')?.setAttribute('hidden', '');
        });
        const portSaid = page.locator('.lm-marker--agg[data-city-id="11"]');
        if (await portSaid.count()) {
            await portSaid.click({ force: true });
            await page.waitForTimeout(2400);
            const panel = page.locator('[data-lm-city-panel]:not([hidden])');
            await panel.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {});
            if (await panel.isVisible()) {
                await panel.screenshot({
                    path: path.join(OUT, '08-aggregate-event-list.jpg'),
                    type: 'jpeg',
                    quality: 92,
                });
                report.shots.push('08-aggregate-event-list.jpg');
                report.checks.aggRows = await panel.locator('[data-city-event-id]').count();
            }
        }

        await context.close();
    }

    {
        const context = await browser.newContext({ viewport: { width: 1100, height: 700 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
        report.checks.homepageIsolation = (await page.locator('.lm-shell--v35').count()) === 0;
        await context.close();
    }

    // Mobile spotlight + carousel
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
        await ready(page);
        await pauseAnim(page);
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 12000 }).catch(() => {});
        await page.screenshot({
            path: path.join(OUT, '04-mobile-spotlight.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('04-mobile-spotlight.jpg');
        await page.locator('[data-lm-list]').evaluate((n) => n.scrollBy({ left: 200 }));
        await page.waitForTimeout(400);
        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, '05-mobile-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('05-mobile-carousel.jpg');
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
        await ready(page);
        await pauseAnim(page);
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 12000 }).catch(() => {});
        await page.screenshot({
            path: path.join(OUT, '06-arabic-rtl.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('06-arabic-rtl.jpg');
        report.checks.rtl = {
            lang: await page.locator('html').getAttribute('lang'),
            dir: await page.locator('html').getAttribute('dir'),
        };
        await context.close();
    }
} finally {
    await browser.close();
}

fs.writeFileSync(path.join(OUT, 'capture_report.json'), JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
