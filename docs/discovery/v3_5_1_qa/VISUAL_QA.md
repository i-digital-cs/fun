# Living Map V3.5.1 — Visual QA (exact components)

**Verdict for owner:** READY FOR OWNER VISUAL REVIEW (not claimed approved)  
**Production homepage:** **UNCHANGED** — still V3.4 (`lm-shell--v35` absent on `/`)

## Preview URL

Signed (regenerate if expired):

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Example captured at QA time:

`/preview/living-map-v35?expires=…&signature=…`

## Screenshots

| # | File | Component |
|---|---|---|
| 1 | `01-floating-event-card.jpg` | Floating event card close-up |
| 2 | `02-location-filter.jpg` | Location filter close-up |
| 3 | `03-glowing-pins-stage.jpg` | Glowing pins on map stage |
| 4 | `04-full-desktop-en.jpg` | Full map desktop EN |
| 5 | `05-full-mobile-en.jpg` | Full map mobile EN (390) |
| 6 | `06-full-desktop-ar.jpg` | Full map desktop AR / RTL |

## Files changed (V3.5.1 scope)

| File | Role |
|---|---|
| `resources/css/living-map-v35.css` | Premium skin (card, loc filter, pins) — scoped `.lm-shell--v35` |
| `resources/js/living-map/create-living-map.js` | V3.5 pins, approx discs, slider, card placement |
| `resources/js/living-map/funnity-style-v35.js` | V3.5 map style |
| `resources/js/living-map/i18n.js` | live_now / within_km strings |
| `resources/views/components/living-map/stage.blade.php` | `variant=v35` markup (card + loc panel + slider) |
| `resources/views/preview/living-map-v35.blade.php` | Isolated preview page |
| `app/Http/Controllers/Web/Preview/LivingMapV35PreviewController.php` | Preview controller |
| `routes/web.php` | Preview route |
| `vite.config.js` | Vite entry for `living-map-v35.css` |
| `lang/en/living_map.php`, `lang/ar/living_map.php` | Copy |

Flutter: **not touched** (per brief).

## Interaction tests

| Test | Result |
|---|---|
| Desktop EN | Pass — card, filter, pins visible |
| Desktop AR / RTL | Pass — filter flips to start edge; Tajawal applied |
| Mobile EN | Pass — compact filter; card docks bottom |
| Marker select | Pass — floating card + selected pulse |
| Card ↔ filter overlap | Pass after fix — card anchors to pin, avoids filter |
| Cluster select | Pass — count circles zoom/enter city |
| City label / chevron | Pass — Port Said + SVG chevron |
| Distance slider | Pass — `within 25 km` → `within 10 km` on index change; discovery refreshes |
| GPS Use my location | Wired to existing permission/fallback flow (not fabricated) |
| Missing images | Fallback emoji tile |
| Missing avatars | Count text only (no invented faces) |
| Live Now | Only when `starts_at`/`ends_at` genuinely live |
| Empty / approx honesty | “Approximate city location” shown; no fake venue teardrops |
| Reduced motion | Halo/pulse animations disabled via `prefers-reduced-motion` |
| Negative / ~0 km | Distances `< 0.15 km` suppressed |

## Performance

- Port Said demo: **11 approx category discs + 1 cluster** (0 exact teardrops — data is city-level).
- Markers are MapLibre DOM markers with fan-out presentation only; clustering preserved.
- No paid tile/API services introduced.
- Vite build OK (`living-map-v3` ~48 kB gzip ~15.6 kB).

## Remaining differences vs owner references

1. **Teardrop vs disc:** Owner reference emphasizes colorful **teardrop** pins. Exact-coordinate events use luminous teardrops. Port Said’s live data is mostly **approximate city-level**, so V3.5.1 honestly renders **glowing category discs** (not fake venue pins). Teardrops appear when `location_precision === 'exact'`.
2. **Participant avatars:** Shown only when API returns `member_avatars`; many events show count text only.
3. **Live Now badge:** Hidden unless event is genuinely in progress — correct, may differ from static mockups that always show it.
4. **Distance labels like ~49 / ~91 / ~135 km:** Honest haversine from current origin to event/city coordinates — not invented; reflects real geo data quirks, not UI fabrication.
5. **Owner “attached” reference images** were not on disk in-repo; implementation matched the written V3.5.1 brief + Explore dashboard language.

## Production safety confirmation

- `/` does **not** include `lm-shell--v35` or `living-map-v35.css`.
- Homepage Living Map remains **V3.4**.
- Preview requires signed URL / auth / preview token.
- No user/event data mutated.
- No Flutter publish.
- Auth/registration protections untouched by this visual work.
