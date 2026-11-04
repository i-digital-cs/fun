# Living Map V3.5.7 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (not claimed approved)  
**Production homepage:** **UNCHANGED** (V3.4 — no `lm-shell--v35` on `/`)  
**Scope:** Focused UX/visual polish of V3.5.6 preview — no map redesign, no API contract changes.

## Preview URL

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Example:

`https://funnity.com/preview/living-map-v35?expires=1794212414&signature=4cb89d5d85cb49a7ca07a7cce53809b3163ffefbcb659450237521e281254bf1`

---

## Changes implemented

### 1. Premium event spotlight
- Larger thumbnail (`7.1rem` desktop).
- Stronger title hierarchy (`1.12rem` / 800).
- Clearer date + approximate-location line (real API fields only).
- Full-width orange CTA; restrained multi-layer glass shadow.
- Category chip beside “Event spotlight” eyebrow.
- Dismiss control enlarged for touch.

### 2. Spotlight → marker connection
- After framing settles, spotlight prefers an event whose aggregate/exact pin is **on-canvas**.
- Matching marker receives `.is-spotlight` (orange glow / lift) — **coordinates unchanged**.
- Approximate events highlight the **city aggregate** (count preserved); never treated as exact venues.
- If the associated marker is off-canvas, **no fabricated pin** is drawn.
- Dismiss / open card clears the spotlight highlight.
- No camera auto-navigation from spotlight reveal.

### 3. Responsive spotlight
- **Desktop:** floating end/bottom card; avoids loc filter + HUD.
- **Mobile:** bottom-sheet presentation (grab bar, full-bleed width, safe-area padding, ≥44px CTA).

### 4. Carousel polish
- Consistent card `min-height`, clamped titles/meta, flex foot alignment.
- Existing keyboard scroll + card Enter/Space kept.
- Card select still highlights the correct aggregate and opens the floating event card.

### Preserved
Geographic accuracy, aggregates, location filter / GPS, Map·List·Nearby, aggregate → list → card, API contracts, reduced-motion, EN/AR.

---

## Screenshots

| File | Case |
|---|---|
| `01-desktop-initial-spotlight.jpg` | Full-page desktop: map + polished spotlight + lit aggregate |
| `02-desktop-selected-card.jpg` | Full-page: carousel selection → floating event card |
| `03-desktop-carousel.jpg` | Desktop carousel close-up |
| `04-mobile-spotlight.jpg` | Full-page mobile bottom-sheet spotlight |
| `05-mobile-carousel.jpg` | Mobile carousel |
| `06-arabic-rtl.jpg` | Full-page Arabic RTL |
| `07-spotlight-dismissed.jpg` | Spotlight dismissed (highlight cleared) |
| `08-aggregate-event-list.jpg` | Aggregate city event list |

---

## Instrumented checks (`capture_report.json`)

| Check | Result |
|---|---|
| Spotlight visible with real title/date/place | Pass — e.g. “Console night with friends” · Nov 3 · approx note |
| Associated marker highlighted | Pass — `city-aggregate` Port Said `city_id=11` (count from live API) |
| Dismiss hides spotlight + clears `.is-spotlight` | Pass |
| Carousel → floating card | Pass |
| Aggregate list rows | Pass (live Port Said count) |
| Homepage isolation | Pass |
| Arabic `lang=ar` `dir=rtl` | Pass |
| `DiscoverMapApiTest` (13) | Pass |

All spotlight / carousel / list fields are sourced from `/api/discovery/map` (and city-events for aggregate drill-down). No invented participants, images, or coordinates.

---

## Files touched

| File | Role |
|---|---|
| `resources/css/living-map-v35.css` | Spotlight + marker-spotlight + carousel polish |
| `resources/js/living-map/create-living-map.js` | Marker connection; deferred spotlight after frame |
| `resources/views/components/living-map/stage.blade.php` | Spotlight markup (grab / topline / full CTA) |
| `resources/views/preview/living-map-v35.blade.php` | Banner → V3.5.7 |

---

## Remaining limitations

- Live aggregate counts fluctuate with published events (e.g. Port Said may show 3 vs earlier 4).
- Spotlight picks one scored on-canvas event; session dismiss persists until the tab storage is cleared.
- Mobile sheet shares the stage bottom with map chrome — intentional compact tradeoff.

---

## Recommendation for owner approval

Approve V3.5.7 if the spotlight feels premium, clearly ties to its aggregate without implying an exact venue, and mobile sheet / carousel / RTL look acceptable.  
**Do not deploy to the homepage** until explicit approval.

**Stop for owner visual review.**
