/**
 * Event Quick View — centered listing modal (not a second detail page).
 * Opens from listing CTAs / map floating card / rail; fragment /events/{id}/quick-view.
 * History: pushState to canonical while listing stays mounted; Back closes QV.
 * Direct navigation to canonical still hits events.show (full page).
 *
 * Discover map/list: only the "View details" CTA opens QV. Title/media clicks
 * are left for living-map selection (capture handler must not steal them).
 */

import { hidePageLoader } from './page-loader';

const STATE_KEY = 'funEventQv';
const REDUCED = () =>
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/** @type {{ open: boolean, returnUrl: string, listTitle: string, trigger: Element|null, abort: AbortController|null, ids: number[], loadGen: number, listScroll: number|null, scrollbarGap: number }} */
const state = {
    open: false,
    returnUrl: '',
    listTitle: '',
    trigger: null,
    abort: null,
    ids: [],
    loadGen: 0,
    listScroll: null,
    scrollbarGap: 0,
};

function dialog() {
    return document.getElementById('event-quick-view');
}

function bodyEl() {
    return dialog()?.querySelector('[data-event-qv-content]');
}

function loadingEl() {
    return dialog()?.querySelector('[data-event-qv-loading]');
}

function discoverResultsEl() {
    return document.getElementById('discover-results');
}

function setLoading(on) {
    const el = loadingEl();
    if (!el) return;
    if (on) el.removeAttribute('hidden');
    else el.setAttribute('hidden', '');
}

function parseEventIdFromHref(href) {
    try {
        const u = new URL(href, window.location.origin);
        const m = u.pathname.match(/\/events\/(\d+)(?:\/|$)/);
        return m ? Number(m[1]) : null;
    } catch {
        return null;
    }
}

function isEventShowPath(href) {
    try {
        const u = new URL(href, window.location.origin);
        if (u.origin !== window.location.origin) return false;
        // /events/{id} or /events/{id}/{slug} or /ar/events/...
        return /^(?:\/ar)?\/events\/\d+(?:\/[\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)*)?$/u.test(u.pathname);
    } catch {
        return false;
    }
}

function isDiscoverDetailsCta(anchor) {
    const text = (anchor.textContent || '').replace(/\s+/g, ' ').trim();
    if (/view details|عرض التفاصيل/i.test(text)) return true;
    // Explicit opt-in for future markup
    return anchor.hasAttribute('data-event-qv-details');
}

/**
 * Whether this anchor should open Quick View (vs map select / full navigation).
 */
function shouldOpenQuickView(anchor) {
    if (!anchor || anchor.tagName !== 'A') return false;
    if (!isEventShowPath(anchor.href) && !anchor.hasAttribute('data-event-id')) {
        // Map floating CTAs always have href set to show URL once populated.
        if (!anchor.matches('[data-lm-card-link], .lm-rail__go, .lm-card__go, .lm-card__cta')) {
            return false;
        }
    }

    if (anchor.matches('a[data-lm-card-link], a.lm-rail__go, a.lm-card__go, a.lm-card__cta')) {
        return true;
    }

    if (anchor.hasAttribute('data-event-qv-open')) {
        const card = anchor.closest('[data-discover-result-card]');
        if (card) {
            // Discover right-rail SSR list: only View details opens QV.
            return isDiscoverDetailsCta(anchor);
        }
        return true;
    }

    // Dashboard social cards (no data-event-qv-open until Blade publish).
    if (anchor.closest('article[class*="rounded-[1.15rem]"]') && isEventShowPath(anchor.href)) {
        return true;
    }

    return false;
}

function collectIds(fromEl) {
    const list =
        fromEl?.closest?.('[data-event-qv-list]') ||
        fromEl?.closest?.('#discover-results') ||
        document.getElementById('discover-results') ||
        document;
    const ids = [];
    const seen = new Set();
    const nodes = list.querySelectorAll(
        '[data-event-qv-open], [data-discover-result-card], [data-lm-list-event], a[data-lm-card-link], a.lm-rail__go'
    );
    nodes.forEach((el) => {
        const raw =
            el.getAttribute('data-event-id') ||
            el.getAttribute('data-lm-list-event') ||
            el.dataset?.eventId ||
            parseEventIdFromHref(el.getAttribute('href') || '');
        const id = Number(raw);
        if (!Number.isFinite(id) || id <= 0 || seen.has(id)) return;
        seen.add(id);
        ids.push(id);
    });
    return ids;
}

function updateNavButtons(currentId) {
    const root = dialog();
    if (!root) return;
    const idx = state.ids.indexOf(currentId);
    const prev = root.querySelector('[data-event-qv-prev]');
    const next = root.querySelector('[data-event-qv-next]');
    const has = idx >= 0 && state.ids.length > 1;
    if (prev) prev.disabled = !has || idx <= 0;
    if (next) next.disabled = !has || idx >= state.ids.length - 1;
}

function renderError(eventId, message) {
    const content = bodyEl();
    if (!content) return;
    const safeMsg = message || 'Unable to load this event.';
    content.innerHTML = `
        <div class="event-qv-error" data-event-qv-error data-event-id="${eventId}">
            <p class="event-qv-error__text">${safeMsg}</p>
            <div class="event-qv-error__actions">
                <button type="button" class="event-qv-error__retry" data-event-qv-retry data-event-id="${eventId}">Retry</button>
                <a class="event-qv-error__full" data-event-qv-full href="/events/${eventId}">Open full page</a>
            </div>
        </div>`;
}

function lockScroll() {
    const results = discoverResultsEl();
    if (results) {
        state.listScroll = results.scrollTop;
    }
    const gap = window.innerWidth - document.documentElement.clientWidth;
    state.scrollbarGap = gap > 0 ? gap : 0;
    document.documentElement.dataset.eventQvOpen = '1';
    if (state.scrollbarGap) {
        document.documentElement.style.setProperty('--event-qv-scrollbar-gap', `${state.scrollbarGap}px`);
        document.documentElement.style.paddingInlineEnd = `${state.scrollbarGap}px`;
    }
}

function unlockScroll() {
    delete document.documentElement.dataset.eventQvOpen;
    document.documentElement.style.paddingInlineEnd = '';
    document.documentElement.style.removeProperty('--event-qv-scrollbar-gap');
    const results = discoverResultsEl();
    if (results && state.listScroll != null) {
        results.scrollTop = state.listScroll;
    }
    state.listScroll = null;
    state.scrollbarGap = 0;
}

async function loadPanel(eventId) {
    const content = bodyEl();
    if (!content) return;

    const gen = ++state.loadGen;
    state.abort?.abort();
    state.abort = new AbortController();
    setLoading(true);
    content.setAttribute('aria-busy', 'true');
    if (!content.querySelector('[data-event-qv-panel]')) {
        content.innerHTML = '';
    }

    try {
        const res = await fetch(`/events/${eventId}/quick-view`, {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: state.abort.signal,
        });
        if (gen !== state.loadGen || !state.open) return;
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const html = await res.text();
        if (gen !== state.loadGen || !state.open) return;
        content.innerHTML = html;
        const title = content.querySelector('#event-qv-title')?.textContent?.trim();
        if (title) document.title = title;
        updateNavButtons(eventId);
        const panel = content.querySelector('[data-event-qv-panel]');
        if (panel && typeof panel.focus === 'function') {
            try {
                panel.focus({ preventScroll: true });
            } catch {
                panel.focus();
            }
        }
        // Keep Discover list scroll pinned after panel paint / focus.
        const results = discoverResultsEl();
        if (results && state.listScroll != null) {
            results.scrollTop = state.listScroll;
        }
    } catch (err) {
        if (err?.name === 'AbortError') return;
        if (gen !== state.loadGen || !state.open) return;
        renderError(eventId, 'Unable to load this event.');
    } finally {
        if (gen === state.loadGen) {
            setLoading(false);
            content.removeAttribute('aria-busy');
        }
    }
}

function syncHistory(canonicalUrl, { replace = false } = {}) {
    const payload = { [STATE_KEY]: true, returnUrl: state.returnUrl };
    if (replace) {
        history.replaceState(payload, '', canonicalUrl);
    } else {
        history.pushState(payload, '', canonicalUrl);
    }
}

function openDialog() {
    hidePageLoader();
    const d = dialog();
    if (!d || typeof d.showModal !== 'function') return;
    if (!d.open) {
        d.classList.toggle('event-qv-dialog--instant', REDUCED());
        lockScroll();
        d.showModal();
    } else {
        document.documentElement.dataset.eventQvOpen = '1';
    }
    state.open = true;
}

function closeDialogUi() {
    state.loadGen += 1;
    state.abort?.abort();
    state.abort = null;
    setLoading(false);

    const d = dialog();
    if (d?.open) d.close();
    state.open = false;
    unlockScroll();
    const content = bodyEl();
    if (content) content.innerHTML = '';

    if (state.listTitle) {
        document.title = state.listTitle;
    }

    hidePageLoader();

    const t = state.trigger;
    state.trigger = null;
    if (t && typeof t.focus === 'function') {
        try {
            t.focus({ preventScroll: true });
        } catch {
            t.focus();
        }
    }
}

async function openFromTrigger(anchor, { fromHistory = false } = {}) {
    const id = Number(anchor.getAttribute('data-event-id') || parseEventIdFromHref(anchor.href));
    if (!Number.isFinite(id) || id <= 0) return;
    await openEventQuickViewById(id, {
        trigger: anchor,
        fromHistory,
        canonicalHint: anchor.getAttribute('href') || null,
    });
}

/**
 * Programmatic open (map floating card, spotlight, tests).
 * @param {number|string} eventId
 * @param {{ trigger?: Element|null, fromHistory?: boolean, canonicalHint?: string|null }} [opts]
 */
export async function openEventQuickViewById(eventId, opts = {}) {
    const id = Number(eventId);
    if (!Number.isFinite(id) || id <= 0) return;

    hidePageLoader();

    state.trigger = opts.trigger || state.trigger;
    state.ids = collectIds(opts.trigger || document.getElementById('discover-results') || document.body);
    if (!opts.fromHistory) {
        state.returnUrl = window.location.pathname + window.location.search + window.location.hash;
        state.listTitle = document.title;
    }

    openDialog();
    await loadPanel(id);

    if (!state.open) return;

    const panel = bodyEl()?.querySelector('[data-event-qv-panel]');
    const canonical =
        panel?.getAttribute('data-canonical-url') ||
        opts.canonicalHint ||
        `/events/${id}`;

    if (!opts.fromHistory) {
        const already = history.state && history.state[STATE_KEY];
        if (already) syncHistory(canonical, { replace: true });
        else syncHistory(canonical, { replace: false });
    }
}

async function navigateSibling(delta) {
    if (!state.open) return;
    const panel = bodyEl()?.querySelector('[data-event-qv-panel]');
    const current = Number(panel?.getAttribute('data-event-id'));
    const idx = state.ids.indexOf(current);
    if (idx < 0) return;
    const nextId = state.ids[idx + delta];
    if (!nextId) return;

    await loadPanel(nextId);
    if (!state.open) return;
    const nextPanel = bodyEl()?.querySelector('[data-event-qv-panel]');
    const canonical = nextPanel?.getAttribute('data-canonical-url') || `/events/${nextId}`;
    syncHistory(canonical, { replace: true });
}

function requestClose() {
    if (!state.open) return;

    const hadQvHistory = Boolean(history.state && history.state[STATE_KEY]);
    const returnUrl = state.returnUrl || '/events';

    closeDialogUi();

    if (hadQvHistory) {
        const onPop = () => {
            window.removeEventListener('popstate', onPop);
        };
        window.addEventListener('popstate', onPop, { once: true });
        history.back();
        window.setTimeout(() => {
            window.removeEventListener('popstate', onPop);
            if (history.state && history.state[STATE_KEY]) {
                history.replaceState(null, '', returnUrl);
            }
        }, 250);
    }
}

function onDocumentClick(e) {
    const retry = e.target.closest?.('[data-event-qv-retry]');
    if (retry) {
        e.preventDefault();
        e.stopPropagation();
        const id = Number(retry.getAttribute('data-event-id'));
        if (Number.isFinite(id) && id > 0 && state.open) {
            loadPanel(id);
        }
        return;
    }

    const full = e.target.closest?.('[data-event-qv-full]');
    if (full && state.open) {
        return;
    }

    // Spotlight CTA is a <button> — open QV for the spotlighted event id if present.
    const spotlightBtn = e.target.closest?.('[data-lm-spotlight-open]');
    if (spotlightBtn) {
        const sid =
            spotlightBtn.getAttribute('data-event-id') ||
            document.querySelector('[data-lm-spotlight]')?.getAttribute('data-event-id');
        if (sid) {
            e.preventDefault();
            e.stopPropagation();
            hidePageLoader();
            openEventQuickViewById(sid, { trigger: spotlightBtn });
            return;
        }
    }

    const openLink = e.target.closest?.('a[href]');
    if (openLink && shouldOpenQuickView(openLink)) {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        hidePageLoader();
        openFromTrigger(openLink);
        return;
    }

    const d = dialog();
    if (d?.open && e.target === d) {
        e.preventDefault();
        requestClose();
    }
}

function onPopState() {
    if (history.state && history.state[STATE_KEY]) {
        const id = parseEventIdFromHref(window.location.href);
        if (id) {
            state.returnUrl = history.state.returnUrl || state.returnUrl;
            if (!state.listTitle) {
                state.listTitle = document.title;
            }
            openDialog();
            loadPanel(id);
            return;
        }
    }
    if (state.open) {
        closeDialogUi();
    }
}

export function initEventQuickView() {
    const d = dialog();
    if (!d || d.dataset.qvBound === '1') return;
    d.dataset.qvBound = '1';

    d.querySelector('[data-event-qv-close]')?.addEventListener('click', (e) => {
        e.preventDefault();
        requestClose();
    });
    d.querySelector('[data-event-qv-prev]')?.addEventListener('click', (e) => {
        e.preventDefault();
        navigateSibling(-1);
    });
    d.querySelector('[data-event-qv-next]')?.addEventListener('click', (e) => {
        e.preventDefault();
        navigateSibling(1);
    });
    d.addEventListener('cancel', (e) => {
        e.preventDefault();
        requestClose();
    });
    d.addEventListener('close', () => {
        if (state.open) {
            state.open = false;
            unlockScroll();
            hidePageLoader();
        }
    });

    document.addEventListener('click', onDocumentClick, true);
    window.addEventListener('popstate', onPopState);

    // Expose for living-map / tests without circular imports in older bundles.
    window.funnityOpenEventQuickView = openEventQuickViewById;
}
