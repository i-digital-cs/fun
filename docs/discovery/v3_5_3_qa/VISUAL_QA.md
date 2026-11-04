# Living Map V3.5.3 — Visual QA (final pin design)

**Verdict for owner:** READY FOR OWNER VISUAL REVIEW (approval not claimed)  
**Production homepage:** **UNCHANGED** — still V3.4 (`lm-shell--v35` absent on `/`)

## Preview URL

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Captured example:

`/preview/living-map-v35?expires=…&signature=…`

## Screenshots

| # | File | Subject |
|---|---|---|
| 1 | `01-teardrop-pin-closeup.jpg` | Glowing teardrop pin close-up |
| 1b | `01-teardrop-pin-stage.jpg` | Pins on map stage |
| 2 | `02-port-said-aggregate-4.jpg` | Port Said aggregate pin (count **4**) |
| 3 | `03-floating-event-card.jpg` | Selected floating event card |
| 4 | `04-location-filter.jpg` | Location filter |
| 5 | `05-full-desktop-en.jpg` | Full desktop EN |
| 6 | `06-full-mobile-en.jpg` | Full mobile EN |
| 7 | `07-full-desktop-ar.jpg` | Full desktop AR / RTL |

## What changed (V3.5.3)

1. **Premium teardrop pins** — pointed tip, gradient fill, white face, 3D highlight, outer glow, ground halo, float animation, strong selected state.
2. **City aggregation** — approximate city-level events no longer fan out as overlapping discs. Port Said shows **one** teardrop with **4**. Click opens city event list → selecting an event opens the floating card.
3. **Exact events only** get individual category teardrops (when precise coordinates exist).
4. **Floating card polish** — larger thumb, softer layered shadow, clearer purple CTA, pointer alignment.
5. **Location filter copy** — `Events around Port Said · Approximate locations` / `فعاليات حول بورسعيد · المواقع تقريبية`, plus visible `within 25 km` pill and active slider label.

## Files changed

| File | Role |
|---|---|
| `resources/js/living-map/create-living-map.js` | Teardrop SVG, aggregate-only approx pins, focus copy, card place |
| `resources/css/living-map-v35.css` | Pin/card/filter polish (scoped `.lm-shell--v35`) |
| `resources/js/living-map/i18n.js` | `eventsAround` |
| `lang/en/living_map.php`, `lang/ar/living_map.php` | Friendly around-city strings |
| `resources/views/components/living-map/stage.blade.php` | Distance readout + i18n attr |
| `resources/views/preview/living-map-v35.blade.php` | Banner → V3.5.3 |

Flutter: **not touched**.

## Tests performed

| Case | Result |
|---|---|
| Desktop EN | Pass |
| Desktop AR / RTL | Pass — Arabic sub copy verified |
| Mobile EN | Pass |
| City aggregation | Pass — **1** agg pin, count **4**, **0** discs |
| Exact event pins | N/A on Port Said demo (no exact coords in range) |
| Aggregate click → city list → card | Pass |
| Distance slider + active label | Pass |
| GPS button wired | Pass (existing flow) |
| Keyboard on markers | Enter/Space handlers present |
| Reduced motion | Float/glow disabled via CSS media query |
| Homepage isolation | Pass — no `lm-shell--v35` on `/` |

## Remaining visual differences

1. Owner reference images were not on disk; polish matched the written brief + prior approved component language.
2. Port Said demo has **no exact-coordinate events**, so category teardrops appear when those exist elsewhere; here the hero pin is the aggregate **4**.
3. After opening the city list, the floating card may cover the pin tip until dismissed — expected selection UX.
4. JPEG close-ups can soften thin digit strokes; live UI shows crisp HTML count `4` on the white disc.

## Production safety

- Preview-only V3.5.3 skin.
- Production homepage remains V3.4.
- No user/event data mutated.
- No Flutter changes.
- No paid services added.

**STOP FOR OWNER VISUAL APPROVAL.**
