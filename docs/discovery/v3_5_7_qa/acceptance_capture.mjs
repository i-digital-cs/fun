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
    'https://funnity.com/preview/living-map-v35?expires=1794212659&signature=4e43cb1fc3474ba50241031929a93b4ce8f91657e84250701ab55504e5f63d64';

async function setLocale(page, locale) {
    await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    await page.request.post('https://funnity.com/locale', {
        form: { _token: token, locale },
        headers: { Referer: 'https://funnity.com/' },
    });
}

async function ready(page, { settleMs = 4200 } = {}) {
    const el = page.locator('[data-living-map].lm-shell--v35');
    await el.waitFor({ state: 'visible', timeout: 45000 });
    await page.waitForFunction(
        () => document.querySelector('[data-living-map].lm-shell--v35')?.dataset?.ready === '1',
        { timeout: 60000 }
    ).catch(() => {});
    await page.waitForTimeout(settleMs);
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

async function spotlightProbe(page) {
    return page.evaluate(() => {
        const spot = document.querySelector('[data-lm-spotlight]');
        const lit = document.querySelector('.lm-marker.is-spotlight');
        const stage = document.querySelector('.lm-stage')?.getBoundingClientRect();
        const spotRect = spot && !spot.hidden ? spot.getBoundingClientRect() : null;
        let clipped = false;
        if (spotRect && stage) {
            clipped =
                spotRect.bottom > stage.bottom + 2 ||
                spotRect.right > stage.right + 2 ||
                spotRect.left < stage.left - 2;
        }
        return {
            exists: !!spot,
            hiddenAttr: spot?.hasAttribute('hidden') ?? null,
            computedDisplay: spot ? getComputedStyle(spot).display : null,
            title: spot?.querySelector('[data-lm-spotlight-title]')?.textContent || '',
            place: spot?.querySelector('[data-lm-spotlight-place]')?.textContent || '',
            meta: spot?.querySelector('[data-lm-spotlight-meta]')?.textContent || '',
            cat: spot?.querySelector('[data-lm-spotlight-cat]')?.textContent || '',
            markerLit: !!lit,
            markerKind: lit?.dataset?.markerKind || null,
            markerCount: lit?.dataset?.count || null,
            markerCityId: lit?.dataset?.cityId || null,
            sessionDismissed: sessionStorage.getItem('lm-v35-spotlight-dismissed'),
            clippedByStage: clipped,
            spotRect: spotRect
                ? {
                      w: Math.round(spotRect.width),
                      h: Math.round(spotRect.height),
                      top: Math.round(spotRect.top),
                      left: Math.round(spotRect.left),
                  }
                : null,
            carouselCards: document.querySelectorAll('[data-lm-list-event]').length,
            aggMarkers: document.querySelectorAll('.lm-marker--agg').length,
        };
    });
}

const browser = await chromium.launch({
    executablePath: CHROME,
    headless: true,
    args: ['--no-sandbox', '--disable-dev-shm-usage'],
});

const report = {
    preview: PREVIEW,
    capturedAt: new Date().toISOString(),
    shots: [],
    investigation: {},
    interactions: {},
};

try {
    // --- A) Reproduce missing spotlight via persisted dismiss ---
    {
        const context = await browser.newContext({ viewport: { width: 1440, height: 1200 } });
        await context.addInitScript(() => {
            sessionStorage.setItem('lm-v35-spotlight-dismissed', '1');
        });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);
        await pauseAnim(page);
        report.investigation.withDismissedStorage = await spotlightProbe(page);
        await page.screenshot({
            path: path.join(OUT, 'acceptance-00-dismissed-storage-reload.jpg'),
            type: 'jpeg',
            quality: 88,
            fullPage: true,
        });
        report.shots.push('acceptance-00-dismissed-storage-reload.jpg');
        await context.close();
    }

    // --- B) Fresh desktop load (no dismiss) ---
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
        const el = await ready(page, { settleMs: 4500 });
        await pauseAnim(page);
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 15000 }).catch(() => {});
        await page.waitForTimeout(700);

        report.investigation.freshLoad = await spotlightProbe(page);

        await page.screenshot({
            path: path.join(OUT, 'acceptance-01-fresh-desktop-load.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('acceptance-01-fresh-desktop-load.jpg');

        await page.locator('[data-lm-spotlight]:not([hidden])').screenshot({
            path: path.join(OUT, 'acceptance-02-desktop-spotlight-visible.jpg'),
            type: 'jpeg',
            quality: 92,
        }).catch(async () => {
            await el.screenshot({
                path: path.join(OUT, 'acceptance-02-desktop-spotlight-visible.jpg'),
                type: 'jpeg',
                quality: 90,
            });
        });
        report.shots.push('acceptance-02-desktop-spotlight-visible.jpg');

        // Highlighted aggregate (full stage)
        await el.screenshot({
            path: path.join(OUT, 'acceptance-03-spotlight-marker-highlight.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('acceptance-03-spotlight-marker-highlight.jpg');

        // Aggregate list
        await page.evaluate(() => {
            document.querySelector('[data-lm-card]')?.setAttribute('hidden', '');
        });
        const portSaid = page.locator('.lm-marker--agg[data-city-id="11"]');
        const anyAgg = page.locator('.lm-marker--agg').first();
        const aggTarget = (await portSaid.count()) ? portSaid : anyAgg;
        await aggTarget.click({ force: true });
        await page.waitForTimeout(2400);
        const panel = page.locator('[data-lm-city-panel]:not([hidden])');
        await panel.waitFor({ state: 'visible', timeout: 12000 }).catch(() => {});
        report.interactions.aggregateList = {
            visible: await panel.isVisible().catch(() => false),
            rows: await panel.locator('[data-city-event-id]').count().catch(() => 0),
        };
        if (await panel.isVisible()) {
            await panel.screenshot({
                path: path.join(OUT, 'acceptance-04-aggregate-event-list.jpg'),
                type: 'jpeg',
                quality: 92,
            });
            report.shots.push('acceptance-04-aggregate-event-list.jpg');

            const row = panel.locator('[data-city-event-id]').first();
            if (await row.count()) {
                await row.click({ force: true });
                await page.waitForTimeout(1500);
                report.interactions.floatingFromList = await page
                    .locator('[data-lm-card]:not([hidden])')
                    .count();
                await page.screenshot({
                    path: path.join(OUT, 'acceptance-05-floating-event-card.jpg'),
                    type: 'jpeg',
                    quality: 90,
                    fullPage: true,
                });
                report.shots.push('acceptance-05-floating-event-card.jpg');
            }
        }

        // Close overlays; show carousel
        await page.evaluate(() => {
            document.querySelector('[data-lm-card]')?.setAttribute('hidden', '');
            document.querySelector('[data-lm-city-panel]')?.setAttribute('hidden', '');
        });
        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, 'acceptance-06-desktop-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('acceptance-06-desktop-carousel.jpg');

        // Carousel → aggregate highlight
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);
        await pauseAnim(page);
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 12000 }).catch(() => {});
        const carouselCard = page.locator('[data-lm-list-event]').first();
        if (await carouselCard.count()) {
            await carouselCard.click({ force: true });
            await page.waitForTimeout(1600);
            report.interactions.carouselSelect = await page.evaluate(() => ({
                cardOpen: !!document.querySelector('[data-lm-card]:not([hidden])'),
                selectedAgg: !!document.querySelector('.lm-marker--agg.is-selected'),
                title:
                    document.querySelector('[data-lm-card-title]')?.textContent || '',
            }));
        }

        // Dismiss spotlight (fresh again)
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
        await page.waitForTimeout(500);
        report.interactions.dismiss = await spotlightProbe(page);
        // Reload should stay dismissed
        await page.reload({ waitUntil: 'networkidle', timeout: 90000 });
        await ready(page, { settleMs: 3500 });
        report.interactions.afterDismissReload = await spotlightProbe(page);
        await page.screenshot({
            path: path.join(OUT, 'acceptance-10-spotlight-dismissed.jpg'),
            type: 'jpeg',
            quality: 88,
            fullPage: true,
        });
        report.shots.push('acceptance-10-spotlight-dismissed.jpg');

        // Keyboard: focus rail + arrow
        await page.evaluate(() => {
            try {
                sessionStorage.removeItem('lm-v35-spotlight-dismissed');
            } catch {}
        });
        await page.goto(PREVIEW, { waitUntil: 'networkidle', timeout: 90000 });
        await ready(page);
        const rail = page.locator('[data-lm-list]');
        await rail.focus();
        const before = await rail.evaluate((n) => n.scrollLeft);
        await page.keyboard.press('ArrowRight');
        await page.waitForTimeout(400);
        const after = await rail.evaluate((n) => n.scrollLeft);
        report.interactions.keyboardRail = { before, after, moved: after !== before };

        await context.close();
    }

    // --- C) Mobile ---
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
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 15000 }).catch(() => {});
        report.investigation.mobileFresh = await spotlightProbe(page);
        await page.screenshot({
            path: path.join(OUT, 'acceptance-07-mobile-spotlight.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('acceptance-07-mobile-spotlight.jpg');
        await page.locator('[data-lm-list]').evaluate((n) => n.scrollBy({ left: 180 }));
        await page.waitForTimeout(400);
        await page.locator('[data-lm-carousel-wrap]').screenshot({
            path: path.join(OUT, 'acceptance-08-mobile-carousel.jpg'),
            type: 'jpeg',
            quality: 90,
        });
        report.shots.push('acceptance-08-mobile-carousel.jpg');

        // Touch dismiss
        const dismiss = page.locator('[data-lm-spotlight-dismiss]');
        if (await dismiss.isVisible()) {
            await dismiss.tap().catch(() => dismiss.click({ force: true }));
            await page.waitForTimeout(400);
            report.interactions.mobileDismiss = await spotlightProbe(page);
        }
        await context.close();
    }

    // --- D) Arabic RTL ---
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
        await page.waitForSelector('[data-lm-spotlight]:not([hidden])', { timeout: 15000 }).catch(() => {});
        report.investigation.rtlFresh = await spotlightProbe(page);
        report.investigation.rtl = {
            lang: await page.locator('html').getAttribute('lang'),
            dir: await page.locator('html').getAttribute('dir'),
        };
        await page.screenshot({
            path: path.join(OUT, 'acceptance-09-arabic-rtl.jpg'),
            type: 'jpeg',
            quality: 90,
            fullPage: true,
        });
        report.shots.push('acceptance-09-arabic-rtl.jpg');
        await context.close();
    }

    // Homepage isolation
    {
        const context = await browser.newContext({ viewport: { width: 1200, height: 800 } });
        const page = await context.newPage();
        await setLocale(page, 'en');
        await page.goto('https://funnity.com/', { waitUntil: 'domcontentloaded', timeout: 90000 });
        report.interactions.homepageIsolation = (await page.locator('.lm-shell--v35').count()) === 0;
        await context.close();
    }
} finally {
    await browser.close();
}

fs.writeFileSync(path.join(OUT, 'acceptance_report.json'), JSON.stringify(report, null, 2));
console.log(JSON.stringify(report, null, 2));
