# Living Map V3.4 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (implemented on Home when gate is on; not owner-approved yet).

## Screenshots

| File | Case |
|---|---|
| `01-en-desktop-1440.jpg` | Guest EN desktop |
| `02-en-mobile-390.jpg` | Guest EN mobile |
| `03-ar-desktop-1440.jpg` | Guest AR RTL desktop |
| `04-ar-mobile-390.jpg` | Guest AR RTL mobile |
| `05-en-desktop-selected.jpg` | EN desktop after carousel select |
| `06-en-desktop-list-mode.jpg` | EN desktop List mode |

Capture script: `capture.mjs`.

## Remaining differences vs reference image

1. **Not a static 3D Port Said illustration** — real MapLibre + OpenFreeMap geography. Soft pitch/extrusion only where tile building heights exist; nationwide guest framing is a wide Egypt overview (less “isometric city”).
2. **No fabricated “Live Now” badges** or attendee avatar stacks — API provides `members_count` only; cards show “N going” when > 0.
3. **Reference distance slider (1/5/10/20 km)** vs product SSOT chips **5 / 10 / 25 / 50 / 100 / All**.
4. **Exact-venue teardrop pins** appear when markers are exact; city-level discovery still uses honest city collection chips / numbered cluster pins (no fake street pins).
5. **Guest nationwide** hides radius chips until an origin exists (GPS or saved city) — avoids misleading distance UI.
6. **MapLibre attribution** remains visible (license requirement).

## Functional smoke (automated + live HTML)

- Living Map mounts on `/` with `data-living-map`, Map/List/Nearby tabs, Use my location, carousel region.
- Explore V4 `/api/discovery/map` still drives markers + list.
- Discover gate unchanged (map-only stage).
