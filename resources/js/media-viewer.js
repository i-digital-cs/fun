/**
 * Premium in-page media viewer for Funnity Web.
 * Opens authenticated media gateway URLs inside a modal — never navigates to raw storage.
 *
 * Markup: [data-media-viewer][data-media-src][data-media-type="image"|"video"]
 * Optional: data-media-group for next/prev within a set.
 */
const STATE = {
    open: false,
    items: [],
    index: 0,
    scale: 1,
    root: null,
    bound: false,
};

function ensureRoot() {
    if (STATE.root) return STATE.root;

    const root = document.createElement('div');
    root.id = 'fun-media-viewer';
    root.className = 'fun-media-viewer';
    root.hidden = true;
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    root.setAttribute('aria-label', 'Media viewer');
    root.innerHTML = `
        <div class="fun-media-viewer__backdrop" data-mv-close></div>
        <div class="fun-media-viewer__stage">
            <button type="button" class="fun-media-viewer__close" data-mv-close aria-label="Close">×</button>
            <button type="button" class="fun-media-viewer__nav fun-media-viewer__nav--prev" data-mv-prev aria-label="Previous" hidden>‹</button>
            <button type="button" class="fun-media-viewer__nav fun-media-viewer__nav--next" data-mv-next aria-label="Next" hidden>›</button>
            <div class="fun-media-viewer__frame" data-mv-frame>
                <div class="fun-media-viewer__loading" data-mv-loading hidden>Loading…</div>
                <img class="fun-media-viewer__img" data-mv-img alt="" hidden>
                <video class="fun-media-viewer__video" data-mv-video controls playsinline hidden></video>
                <p class="fun-media-viewer__error" data-mv-error hidden>Unable to load media.</p>
            </div>
            <div class="fun-media-viewer__toolbar">
                <button type="button" data-mv-zoom-out aria-label="Zoom out">−</button>
                <button type="button" data-mv-fit aria-label="Fit">Fit</button>
                <button type="button" data-mv-zoom-in aria-label="Zoom in">+</button>
            </div>
        </div>
    `;
    document.body.appendChild(root);

    root.querySelectorAll('[data-mv-close]').forEach((el) => {
        el.addEventListener('click', close);
    });
    root.querySelector('[data-mv-prev]')?.addEventListener('click', () => step(-1));
    root.querySelector('[data-mv-next]')?.addEventListener('click', () => step(1));
    root.querySelector('[data-mv-zoom-in]')?.addEventListener('click', () => zoom(0.25));
    root.querySelector('[data-mv-zoom-out]')?.addEventListener('click', () => zoom(-0.25));
    root.querySelector('[data-mv-fit]')?.addEventListener('click', () => setScale(1));

    const img = root.querySelector('[data-mv-img]');
    img?.addEventListener('load', () => {
        root.querySelector('[data-mv-loading]')?.setAttribute('hidden', '');
        img.hidden = false;
    });
    img?.addEventListener('error', () => {
        root.querySelector('[data-mv-loading]')?.setAttribute('hidden', '');
        root.querySelector('[data-mv-error]')?.removeAttribute('hidden');
    });

    let pinchStart = null;
    root.addEventListener(
        'touchstart',
        (e) => {
            if (e.touches.length === 2) {
                const [a, b] = e.touches;
                pinchStart = {
                    dist: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY),
                    scale: STATE.scale,
                };
            }
        },
        { passive: true },
    );
    root.addEventListener(
        'touchmove',
        (e) => {
            if (!pinchStart || e.touches.length !== 2) return;
            const [a, b] = e.touches;
            const dist = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
            setScale(pinchStart.scale * (dist / Math.max(1, pinchStart.dist)));
        },
        { passive: true },
    );
    root.addEventListener('touchend', () => {
        pinchStart = null;
    });

    STATE.root = root;
    return root;
}

function setScale(value) {
    STATE.scale = Math.min(4, Math.max(0.5, value));
    const img = STATE.root?.querySelector('[data-mv-img]');
    if (img) {
        img.style.transform = `scale(${STATE.scale})`;
    }
}

function zoom(delta) {
    setScale(STATE.scale + delta);
}

function collectGroup(el) {
    const group = el.getAttribute('data-media-group');
    if (!group) {
        return [
            {
                src: el.getAttribute('data-media-src') || el.getAttribute('href') || '',
                type: (el.getAttribute('data-media-type') || 'image').toLowerCase(),
            },
        ];
    }

    const selector = `[data-media-viewer][data-media-group="${group.replace(/"/g, '')}"]`;
    return [...document.querySelectorAll(selector)].map((node) => ({
        src: node.getAttribute('data-media-src') || node.getAttribute('href') || '',
        type: (node.getAttribute('data-media-type') || 'image').toLowerCase(),
    }));
}

function paint() {
    const root = ensureRoot();
    const item = STATE.items[STATE.index];
    if (!item?.src) return;

    const img = root.querySelector('[data-mv-img]');
    const video = root.querySelector('[data-mv-video]');
    const loading = root.querySelector('[data-mv-loading]');
    const error = root.querySelector('[data-mv-error]');
    const prev = root.querySelector('[data-mv-prev]');
    const next = root.querySelector('[data-mv-next]');
    const toolbar = root.querySelector('.fun-media-viewer__toolbar');

    error?.setAttribute('hidden', '');
    setScale(1);

    const multi = STATE.items.length > 1;
    prev.hidden = !multi;
    next.hidden = !multi;

    if (video) {
        video.pause();
        video.removeAttribute('src');
        video.load();
        video.hidden = true;
    }
    if (img) {
        img.hidden = true;
        img.removeAttribute('src');
        img.style.transform = 'scale(1)';
    }

    if (item.type === 'video') {
        toolbar?.setAttribute('hidden', '');
        loading?.setAttribute('hidden', '');
        if (video) {
            video.hidden = false;
            video.src = item.src;
        }
    } else {
        toolbar?.removeAttribute('hidden');
        loading?.removeAttribute('hidden');
        if (img) {
            img.src = item.src;
        }
    }
}

function openFromElement(el) {
    const items = collectGroup(el).filter((i) => i.src);
    if (!items.length) return;

    const src = el.getAttribute('data-media-src') || el.getAttribute('href') || '';
    const index = Math.max(
        0,
        items.findIndex((i) => i.src === src),
    );

    STATE.items = items;
    STATE.index = index === -1 ? 0 : index;
    STATE.open = true;

    const root = ensureRoot();
    root.hidden = false;
    document.documentElement.classList.add('fun-media-viewer-open');
    paint();
    root.querySelector('.fun-media-viewer__close')?.focus();
}

function step(delta) {
    if (STATE.items.length < 2) return;
    STATE.index = (STATE.index + delta + STATE.items.length) % STATE.items.length;
    paint();
}

function close() {
    if (!STATE.open) return;
    STATE.open = false;
    const root = STATE.root;
    if (!root) return;

    const video = root.querySelector('[data-mv-video]');
    if (video) {
        video.pause();
        video.removeAttribute('src');
        video.load();
    }
    root.hidden = true;
    document.documentElement.classList.remove('fun-media-viewer-open');
}

function onDocumentClick(event) {
    const el = event.target.closest('[data-media-viewer]');
    if (!el) return;
    const src = el.getAttribute('data-media-src') || el.getAttribute('href');
    if (!src) return;
    event.preventDefault();
    openFromElement(el);
}

function onKeydown(event) {
    if (!STATE.open) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        close();
    } else if (event.key === 'ArrowLeft') {
        step(document.documentElement.dir === 'rtl' ? 1 : -1);
    } else if (event.key === 'ArrowRight') {
        step(document.documentElement.dir === 'rtl' ? -1 : 1);
    } else if (event.key === '+' || event.key === '=') {
        zoom(0.25);
    } else if (event.key === '-') {
        zoom(-0.25);
    }
}

export function initMediaViewer() {
    if (STATE.bound) return;
    STATE.bound = true;
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
}

export function openMediaViewer(src, type = 'image') {
    const el = document.createElement('button');
    el.setAttribute('data-media-src', src);
    el.setAttribute('data-media-type', type);
    openFromElement(el);
}
