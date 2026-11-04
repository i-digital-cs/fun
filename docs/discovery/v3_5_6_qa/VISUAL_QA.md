# Living Map V3.5.6 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (not claimed approved)  
**Production homepage:** **UNCHANGED** (V3.4 — no `lm-shell--v35` on `/`)  
**Geo note:** V3.5.5 marker integrity findings stand — no reopened coordinate investigation.

## Preview URL

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Example:

`https://funnity.com/preview/living-map-v35?expires=1794163661&signature=a65759f91a870d0ad3ea64e9aaf175ccc7b78336b98f4b4499576fa97a877269`

---

## What changed (preview-only)

### 1. Event spotlight
- Glass floating panel (bottom-end of stage) shows **one real discovery event**.
- Real image / title / date / category when API provides them.
- Approximate events show: *“Approximate city location · not an exact venue pin”* (never implies exact venue).
- Dismissible (✕); persisted for the browser session via `sessionStorage`.
- Does **not** auto-fly the camera.
- CTA opens the floating event card with `skipCamera`.
- Positioned away from loc filter (start) and HUD (end/top).

### 2. Events carousel
- Existing horizontal rail polished (spacing, focus rings, RTL scroll).
- Cards use real images, titles, dates, approximate/exact place semantics, categories, members when present.
- Selecting a card highlights the matching **city aggregate** teardrop (approx events) or exact pin, then opens the floating card.
- Keyboard: ArrowLeft/ArrowRight on the rail; cards are `tabindex=0` + Enter/Space.
- Empty copy preserved when no events.

### 3. Initial viewport framing
- After paint, `fitBounds` includes **all painted city-aggregate centroids** (verified city-table points — no invented offsets).
- Prefetch uses a wider bbox so Damietta / Mansoura approx points remain available under Port Said origin.
- Result: Port Said · 4, Damietta · 3, Mansoura · 2 are **on-canvas** in the initial desktop frame.
- Additional real city aggregates (e.g. Zagazig, Ismailia) may appear when the wider envelope includes their city-level events — still honest API data, not decorations.

### Preserved
- Teardrop markers, aggregates, aggregate → list → card, location filter, GPS optional, Map/List/Nearby, EN/AR, reduced-motion.

---

## Screenshots

| File | Case |
|---|---|
| `01-desktop-initial-spotlight.jpg` | Desktop initial + spotlight + three+ city aggregates |
| `02-desktop-carousel.jpg` | Desktop carousel close-up |
| `03-selected-event-card.jpg` | Selected carousel event → floating card |
| `04-aggregate-event-list.jpg` | Port Said aggregate list (4 real events) |
| `05-mobile-initial.jpg` | Mobile initial |
| `06-mobile-carousel.jpg` | Mobile carousel scroll |
| `07-arabic-rtl-desktop.jpg` | Arabic RTL desktop |
| `08-empty-state.jpg` | Empty carousel messaging |

---

## Interaction / isolation tests

| Test | Result |
|---|---|
| Spotlight visible with real API fields | Pass (`spotlightVisible`) |
| Spotlight approx disclaimer present | Pass (screenshot 01) |
| Carousel → floating card | Pass (`selectedCard=1`) |
| Aggregate → list (Port Said 4 rows) | Pass |
| Port Said / Damietta / Mansoura on-canvas | Pass (capture marker transforms all positive X) |
| Homepage isolation | Pass (no `.lm-shell--v35` on `/`) |
| Arabic `lang=ar` `dir=rtl` | Pass |
| `DiscoverMapApiTest` (13) | Pass |
| API contracts unchanged | Pass (no backend edits) |

---

## Files touched

| File | Role |
|---|---|
| `resources/js/living-map/create-living-map.js` | Spotlight, aggregate framing, carousel→agg highlight |
| `resources/css/living-map-v35.css` | Spotlight + carousel polish (scoped `.lm-shell--v35`) |
| `resources/views/components/living-map/stage.blade.php` | Spotlight markup + i18n attrs |
| `resources/js/living-map/i18n.js` | Spotlight strings |
| `lang/en/living_map.php` / `lang/ar/living_map.php` | Spotlight copy |
| `resources/views/preview/living-map-v35.blade.php` | Banner → V3.5.6 |

---

## Remaining limitations

- Wider framing can reveal **additional honest city aggregates** beyond the three named cities when their approx events fall in the prefetch envelope.
- Spotlight picks one scored event (image + focus city preference); dismiss persists for the tab session only.
- Empty-state screenshot is UI-forced for QA evidence (live empty regions still use the same empty copy).
- Exact venue pins remain rare in current published data; approx aggregates remain the primary map language.

---

## Recommendation for owner approval

Approve V3.5.6 preview if spotlight + carousel + three-city framing feel like a modern social discovery surface while keeping geographic honesty.  
**Do not promote to homepage** until explicit approval.

**Stop for owner visual review.**
