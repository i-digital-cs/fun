# Admin Dashboard V2 — QA screenshots

Captured 2026-10-31 via Cursor browser against **static signed-path snapshots** of the live dashboard payload (temporary `public/qa-dash/<token>/` — removed after QA).

Live `/admin` HttpOnly session cookies could not be injected into the Cursor browser (CDP `Network.setCookie` denied). Authenticated HTML was therefore rendered server-side from production data and opened as static pages for viewport verification. Charts loaded from Chart.js CDN (7/7 instances).

| File (Cursor screenshots store) | Viewport | Locale |
|----------------------------------|----------|--------|
| `admin-v2-desktop-en.png` | ~1440×900 full / viewport | EN LTR |
| `admin-v2-desktop-en-viewport.png` | Desktop viewport | EN |
| `admin-v2-desktop-en-main.png` | Main content crop | EN |
| `admin-v2-desktop-ar.png` | Desktop / narrow | AR RTL |
| `admin-v2-tablet-ar.png` | 768×1024 | AR |
| `admin-v2-mobile-en.png` | 390×844 | EN |

Paths on the operator workstation: Cursor `screenshots/` temp directory (filenames above).
