/**
 * System page loader (Uiverse circle loader).
 * Shows on same-origin navigation and form submits; hide on pageshow.
 *
 * Must ignore SPA / overlay triggers (e.g. Event Quick View) — those call
 * preventDefault() in a later bubble listener or in capture. Showing the
 * full-page loader without a document navigation leaves it stuck forever.
 */
function shouldIgnoreLink(anchor) {
    if (!anchor || !anchor.href) return true;
    if (anchor.target && anchor.target !== '_self') return true;
    if (anchor.hasAttribute('download')) return true;
    if (anchor.dataset.noLoader === '1' || anchor.dataset.noLoader === 'true') return true;
    // Event Quick View intercepts these in-page (no document navigation).
    if (anchor.hasAttribute('data-event-qv-open')) return true;
    if (anchor.hasAttribute('data-lm-card-link')) return true;
    if (anchor.classList?.contains('lm-rail__go')) return true;
    if (anchor.classList?.contains('lm-card__go') || anchor.classList?.contains('lm-card__cta')) return true;
    if (anchor.getAttribute('href')?.startsWith('#')) return true;
    if (anchor.getAttribute('href')?.startsWith('mailto:')) return true;
    if (anchor.getAttribute('href')?.startsWith('tel:')) return true;
    if (anchor.getAttribute('href')?.startsWith('javascript:')) return true;

    try {
        const url = new URL(anchor.href, window.location.href);
        if (url.origin !== window.location.origin) return true;
        if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) {
            return true;
        }
    } catch {
        return true;
    }

    return false;
}

export function showPageLoader() {
    const host = document.getElementById('fun-page-loader');
    if (!host) return;
    host.hidden = false;
    host.setAttribute('aria-hidden', 'false');
    document.documentElement.dataset.pageLoading = '1';
}

export function hidePageLoader() {
    const host = document.getElementById('fun-page-loader');
    if (!host) return;
    host.hidden = true;
    host.setAttribute('aria-hidden', 'true');
    delete document.documentElement.dataset.pageLoading;
}

export function initPageLoader() {
    const host = document.getElementById('fun-page-loader');
    if (!host || host.dataset.loaderBound === '1') return;
    host.dataset.loaderBound = '1';

    hidePageLoader();

    document.addEventListener('click', (event) => {
        const anchor = event.target.closest?.('a[href]');
        if (!anchor || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        if (shouldIgnoreLink(anchor)) return;
        showPageLoader();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (event.defaultPrevented) return;
        if (form.dataset.noLoader === '1' || form.dataset.noLoader === 'true') return;
        if (form.getAttribute('target') && form.getAttribute('target') !== '_self') return;
        // AJAX forms (chat, live search) skip full-page loader
        if (form.id === 'chat-form' || form.closest('[data-live-search]')) return;
        // Forms inside Event Quick View still navigate (join) — keep loader.
        showPageLoader();
    });

    window.addEventListener('pageshow', hidePageLoader);
    window.addEventListener('pagehide', () => {
        // Keep visible during unload for smoother handoff
    });
}
