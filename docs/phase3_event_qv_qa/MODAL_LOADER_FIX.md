# Event Quick View — centered modal + stuck loader fix

## Root cause (confirmed)

`resources/js/app.js` initializes **page-loader before** Event Quick View.

1. `initPageLoader()` (`resources/js/page-loader.js` ~L50–57) listens for bubble-phase clicks on `a[href]` and calls `showPageLoader()`.
2. `initEventQuickView()` (`resources/js/event-quick-view.js`) later intercepts `[data-event-qv-open]`, calls `preventDefault()`, and opens an in-page `<dialog>` — **no document navigation**.
3. `hidePageLoader()` only runs on `pageshow` (`page-loader.js` ~L70). With no navigation, the full-page blurred `#fun-page-loader` stays visible forever.

Side drawer was intentional CSS (`margin-inline-start: auto; width: 28rem`) in `resources/css/app.css` Event Quick View block — replaced with a centered modal.

## Fixes

| Layer | Change | Live without Vite? |
|--|--|--|
| Blade cards | `data-no-loader="1"` on QV triggers | **Yes** (hot-fix for stuck loader) |
| `page-loader.js` | Ignore `data-event-qv-open`; idempotent bind | Needs Vite |
| `event-quick-view.js` | Capture-phase click; `hidePageLoader()`; AbortController + loadGen; close UI before `history.back()`; in-modal error/retry | Needs Vite |
| `app.css` + QV shell | Centered 850–980px modal; mobile sheet; in-modal skeleton; hide `#fun-page-loader` while QV open | Needs Vite |

## Activation

1. Stuck loader: mitigated on live HTML via `data-no-loader` (already deployed with Blade).
2. Centered modal + hardened JS: **requires owner-approved `npm run build`**.
3. Do not enable enforcing CSP / HSTS here.

## Verification (browser, 2026-11-05)

- Open → loader stays hidden; dialog opens.
- Close → list URL restored; loader hidden.
- Rapid open/close ×10 → loader never stuck (`rapidOk: true`).
- Next + Back + Forward → loader hidden; Forward reopens.
- Close during fetch → loader hidden.
