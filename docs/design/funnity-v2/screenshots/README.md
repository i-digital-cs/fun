# Funnity V2 / V2.1 screenshots (durable)

Captured 28 October 2026 against local `php artisan serve` at `http://127.0.0.1:8766/` (not production). Light-only V2 identity.

## Baseline (V2 visual)

| File | Viewport | Notes |
| --- | --- | --- |
| `00-approved-reference.png` | — | Copy of Visual Source of Truth |
| `00-web-baseline-before.png` | — | Pre-redesign baseline |
| `web-desktop-1440.png` | 1440×1600 | Desktop viewport |
| `web-desktop-1440-tall.png` | 1440×3200 | Tall desktop for lower sections |
| `web-laptop-1280.png` | 1280×1400 | Laptop |
| `web-tablet-768.png` | 768×1400 | Tablet |
| `web-mobile-390.png` | 390×1200 | Mobile |
| `flutter-home-hero.png` | 390×720 | Flutter golden of V2 hero tokens (not full-app emulator) |

## V2.1 discovery / GPS / RTL

| File | Viewport | Notes |
| --- | --- | --- |
| `v2-1-web-desktop-1440.png` | 1440×900 | Desktop hero |
| `v2-1-web-desktop-1440-full.png` | 1440 full | Full-page desktop |
| `v2-1-web-desktop-geo-discovery.png` | 1440×900 | Happening Near You + Use my location |
| `v2-1-web-desktop-geo-lower.png` | 1440×900 | Lower geo / event cards |
| `v2-1-web-mobile-390.png` | 390×844 | Mobile hero |
| `v2-1-web-mobile-390-mid.png` | 390×844 | Mobile geo + Use my location |
| `v2-1-web-desktop-ar-rtl.png` | 1440×900 | Arabic `lang=ar` `dir=rtl` |
| `v2-1-web-desktop-ar-geo.png` | 1440×900 | Arabic geo region |
| `v2-1-web-desktop-ar.png` | 1440×900 | Earlier AR attempt (pre-locale POST) |

**Not captured here:** Flutter emulator full home with people suggestions (no Android AVD); authenticated people-cards strip (guest suggestions empty).

Tooling: Playwright Chromium headless for web; Flutter 3.47 golden for hero.
