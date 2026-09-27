/**
 * Live search: show wifi "Searching" only while typing and waiting on the API.
 * Not a page loader — appears after debounce when a suggest/page request is in flight.
 *
 * Markup:
 *   <div class="live-search" data-live-search data-live-search-url="..." data-live-search-type="events">
 *     <input type="search" data-live-search-input ...>
 *     <div class="live-search__panel" data-live-search-panel hidden>
 *       <div class="live-search__searching" data-live-search-searching hidden>...</div>
 *       <div class="live-search__results" data-live-search-results></div>
 *     </div>
 *   </div>
 *
 * Page mode (Discover filters): data-live-search-mode="page" + data-live-search-target="#id"
 */
const DEBOUNCE_MS = 350;
const MIN_CHARS = 2;

function debounce(fn, wait) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), wait);
    };
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/** First strong letter → rtl for Arabic/Hebrew, else ltr (stable mixed titles). */
function bidiDir(text) {
    const value = String(text ?? '');
    const match = value.match(/[\p{L}\p{N}]/u);
    if (!match) {
        return 'ltr';
    }
    return /[\u0590-\u05FF\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF]/.test(match[0])
        ? 'rtl'
        : 'ltr';
}

function buildSuggestUrl(root, query) {
    const url = new URL(root.dataset.liveSearchUrl, window.location.origin);
    url.searchParams.set('q', query);
    const type = root.dataset.liveSearchType || 'events';
    url.searchParams.set('type', type);
    return url.toString();
}

function searchingMarkup(root) {
    const source = root.querySelector('[data-live-search-searching]');
    const inner = source?.querySelector('.wifi-loader')?.outerHTML || source?.innerHTML?.trim();
    if (inner) {
        return `<div class="live-search__searching">${inner}</div>`;
    }
    const label = root.dataset.liveSearchSearchingLabel || 'Searching';
    return `<div class="live-search__searching"><div class="wifi-loader" role="status" aria-live="polite">
        <svg class="circle-outer" viewBox="0 0 86 86" aria-hidden="true">
            <circle class="back" cx="43" cy="43" r="40"></circle>
            <circle class="front" cx="43" cy="43" r="40"></circle>
            <circle class="new" cx="43" cy="43" r="40"></circle>
        </svg>
        <svg class="circle-middle" viewBox="0 0 60 60" aria-hidden="true">
            <circle class="back" cx="30" cy="30" r="27"></circle>
            <circle class="front" cx="30" cy="30" r="27"></circle>
        </svg>
        <svg class="circle-inner" viewBox="0 0 34 34" aria-hidden="true">
            <circle class="back" cx="17" cy="17" r="14"></circle>
            <circle class="front" cx="17" cy="17" r="14"></circle>
        </svg>
        <div class="text" data-text="${escapeHtml(label)}"></div>
    </div></div>`;
}

function setSearching(root, on) {
    const panel = root.querySelector('[data-live-search-panel]');
    const searching = root.querySelector('[data-live-search-searching]');
    const results = root.querySelector('[data-live-search-results]');
    if (!panel || !searching) {
        return;
    }
    panel.hidden = false;
    searching.hidden = !on;
    if (results) {
        results.hidden = on;
        if (on) {
            results.innerHTML = '';
        }
    }
    root.classList.toggle('is-searching', on);
    root.setAttribute('aria-busy', on ? 'true' : 'false');
}

function hidePanel(root) {
    const panel = root.querySelector('[data-live-search-panel]');
    const searching = root.querySelector('[data-live-search-searching]');
    if (panel) {
        panel.hidden = true;
    }
    if (searching) {
        searching.hidden = true;
    }
    root.classList.remove('is-searching');
    root.setAttribute('aria-busy', 'false');
}

function renderSuggestResults(root, payload) {
    const results = root.querySelector('[data-live-search-results]');
    if (!results) {
        return;
    }

    const items = Array.isArray(payload?.data) ? payload.data : [];
    const seeAll = payload?.meta?.see_all_url;
    const emptyLabel = root.dataset.liveSearchEmpty || 'No results';
    const seeAllLabel = root.dataset.liveSearchSeeAll || 'See all results';

    results.hidden = false;

    if (items.length === 0) {
        results.innerHTML = `<p class="live-search__empty">${escapeHtml(emptyLabel)}</p>`;
        return;
    }

    const list = items
        .map(
            (item) => `
        <a href="${escapeHtml(item.url)}" class="live-search__item">
            <span class="live-search__item-title bidi-text" dir="${bidiDir(item.title)}">${escapeHtml(item.title)}</span>
            ${item.subtitle ? `<span class="live-search__item-sub bidi-text" dir="${bidiDir(item.subtitle)}">${escapeHtml(item.subtitle)}</span>` : ''}
            ${item.meta ? `<span class="live-search__item-meta">${escapeHtml(item.meta)}</span>` : ''}
        </a>`
        )
        .join('');

    const footer = seeAll
        ? `<a href="${escapeHtml(seeAll)}" class="live-search__see-all">${escapeHtml(seeAllLabel)}</a>`
        : '';

    results.innerHTML = list + footer;
}

async function runSuggest(root, query, abortController) {
    setSearching(root, true);

    try {
        const response = await fetch(buildSuggestUrl(root, query), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: abortController.signal,
        });

        if (!response.ok) {
            throw new Error('Search failed');
        }

        const payload = await response.json();
        if (abortController.signal.aborted) {
            return;
        }
        setSearching(root, false);
        renderSuggestResults(root, payload);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        setSearching(root, false);
        const results = root.querySelector('[data-live-search-results]');
        if (results) {
            results.hidden = false;
            results.innerHTML = `<p class="live-search__empty">${escapeHtml(root.dataset.liveSearchError || 'Search failed')}</p>`;
        }
    }
}

async function runPageSearch(root, input, abortController) {
    const targetSel = root.dataset.liveSearchTarget;
    const target = targetSel ? document.querySelector(targetSel) : null;
    if (!target) {
        return;
    }

    const form = input.form || input.closest('form') || root.querySelector('form');
    const url = new URL(form?.action || window.location.pathname, window.location.origin);
    if (form) {
        new FormData(form).forEach((value, key) => {
            // Keep empty filter values (e.g. city_id=) so "All cities" stays nationwide.
            url.searchParams.set(key, String(value));
        });
    } else {
        url.searchParams.set(input.name || 'q', input.value.trim());
    }
    url.searchParams.set('fragment', '1');

    target.classList.add('is-searching', 'live-search-page-target');
    let overlay = target.querySelector('[data-live-search-overlay]');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'live-search-page-overlay';
        overlay.dataset.liveSearchOverlay = '';
        target.appendChild(overlay);
    }
    overlay.innerHTML = searchingMarkup(root);
    overlay.hidden = false;

    try {
        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Fragment': 'results',
            },
            credentials: 'same-origin',
            signal: abortController.signal,
        });
        if (!response.ok) {
            throw new Error('Search failed');
        }
        const html = await response.text();
        if (abortController.signal.aborted) {
            return;
        }
        target.innerHTML = html;
        target.classList.add('live-search-page-target');
        url.searchParams.delete('fragment');
        window.history.replaceState({}, '', url.pathname + url.search);
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }
        overlay.hidden = true;
        target.classList.remove('is-searching');
    }
}

function bindRoot(root) {
    if (root.dataset.liveSearchBound === '1') {
        return;
    }
    root.dataset.liveSearchBound = '1';

    const input = root.querySelector('[data-live-search-input]') || root.querySelector('input[type="search"]');
    if (!input) {
        return;
    }

    const mode = root.dataset.liveSearchMode || 'suggest';
    let abortController = null;

    const run = () => {
        const query = input.value.trim();

        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();

        if (mode === 'page') {
            // Empty query refreshes the unfiltered list; short queries wait for more typing.
            if (query.length === 1) {
                return;
            }
            runPageSearch(root, input, abortController);
            return;
        }

        if (query.length < MIN_CHARS) {
            hidePanel(root);
            return;
        }

        runSuggest(root, query, abortController);
    };

    const handle = debounce(run, DEBOUNCE_MS);

    input.addEventListener('input', handle);
    input.addEventListener('focus', () => {
        if (mode !== 'page' && input.value.trim().length >= MIN_CHARS) {
            handle();
        }
    });

    if (mode === 'page') {
        const form = input.form || input.closest('form') || root.querySelector('form');
        form?.querySelectorAll('select, input:not([data-live-search-input])').forEach((el) => {
            el.addEventListener('change', handle);
        });
    }

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            hidePanel(root);
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hidePanel(root);
        }
    });
}

export function initLiveSearch(scope = document) {
    scope.querySelectorAll('[data-live-search]').forEach(bindRoot);
}

document.addEventListener('DOMContentLoaded', () => {
    if (!document.documentElement.dataset.liveSearchReady) {
        initLiveSearch();
        document.documentElement.dataset.liveSearchReady = '1';
    }
});
