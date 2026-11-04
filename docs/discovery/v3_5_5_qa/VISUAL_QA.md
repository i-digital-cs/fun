# Living Map V3.5.5 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (not claimed approved)  
**Production homepage:** **UNCHANGED** (V3.4 — no `lm-shell--v35` on `/`)

## Preview URL

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Example:

`https://funnity.com/preview/living-map-v35?expires=1794158149&signature=6e978ecdc3cf1ac7c1f00af04c561e15030979213b22125f66b630fce0379fce`

---

## Geographic findings — initial Port Said viewport

Framing (instrumented): center ≈ `31.27, 32.22`, zoom ≈ `9.3`, bounds N/S/E/W ≈ `31.85 / 30.92 / 33.07 / 31.26`.  
Origin: Port Said · `25 km` · `include_approximate=1`.

API payload in this viewport: **9 approximate markers**, **1 city_aggregate** (Port Said · 4). No exact venue markers in the Port Said 25 km frame.

### UI markers after V3.5.5 synthesis

| Marker | Kind | Count | Coords (source) | Geographic source | Venue vs city | Event IDs | City / gov. | Plausible? |
|---|---|---|---|---|---|---|---|---|
| Port Said aggregate | city-aggregate (`city_id=11`) | **4** | `31.2653, 32.3019` | `cities` row 11 (Port Said center) | City-level aggregate — not a venue | 421, 423, 445, 453 | Port Said | **Yes — coastal city center.** Sits on Port Said land/canal fringe; may look “near water” because Port Said is coastal. **Not** a bogus Mediterranean offshore invent. |
| Damietta aggregate | city-aggregate (`city_id=14`, client-synthesized; API omitted from `city_aggregates` under Port Said radius) | **3** | `31.4165, 31.8133` | `cities` row 14 (Damietta center) | City-level aggregate | 413, 433, 416 | Damietta | **Yes.** Matches Damietta city-table centroid. Venue labels name Damietta; coords are city-level, not desert random scatter. |
| Mansoura aggregate | city-aggregate (`city_id=8`, client-synthesized) | **2** | `31.0409, 31.3785` | `cities` row 8 (Mansoura center) | City-level aggregate | 482, 497 | Mansoura | **Yes geographically.** Screen position often **west of the stage** (`translate X ≈ -241px`) under Port Said framing — **viewport-boundary clip**, not bad coordinates. |

### Approximate events (API markers — not painted as individual pins)

| Event ID | Title | Coords | Precision | Venue label | Members | Notes |
|---|---|---|---|---|---|---|
| 421 | Friendly tennis match | 31.2653, 32.3019 | approximate | Rooftop Gathering Spot · Port Said | 6 | City centroid 11 |
| 423 | Console night with friends | 31.2653, 32.3019 | approximate | Neighborhood Playground · Port Said | 4 | City centroid 11 |
| 445 | Blockbuster movie outing | 31.2653, 32.3019 | approximate | Rooftop Gathering Spot · Port Said | 2 | City centroid 11 |
| 453 | Morning tennis drills | 31.2653, 32.3019 | approximate | Community Sports Club · Port Said | 2 | City centroid 11 |
| 413 | Evening jog around the lake | 31.4165, 31.8133 | approximate | Desert Road Courts · Damietta | 5 | City centroid 14 — “Desert Road” is venue **name**, not a desert pin drop |
| 433 | Friendly football at the pitch | 31.4165, 31.8133 | approximate | Local Café Terrace · Damietta | 4 | City centroid 14 |
| 416 | Courtside rally night | 31.4165, 31.8133 | approximate | Downtown Arena · Damietta | 3 | City centroid 14 |
| 482 | Courtside rally night | 31.0409, 31.3785 | approximate | Community Sports Club · Mansoura | 2 | City centroid 8 |
| 497 | Catan + strategy night | 31.0409, 31.3785 | approximate | University Pitch · Mansoura | 6 | City centroid 8 |

### Exact venue (outside initial Port Said frame)

| Event ID | Title | Coords | Source | City | Plausible? |
|---|---|---|---|---|---|
| 605 | تجمع ملاك جيب شروكي | `29.9803057, 30.9532648` | Event lat/lng (exact) | 6th of October (`city_id=6`) | **Yes** — real venue axis near 6th of October; visible after expanding radius to **All** and flying to the point |

**No invented coordinates. No DB writes. Port Said aggregate count remains 4.**

---

## Root causes addressed

1. **Missing city aggregates for Damietta / Mansoura in Port Said radius mode**  
   API `city_aggregates` is origin/radius restricted (only Port Said · 4). Approximate markers for Damietta/Mansoura still arrive via bbox `include_approximate`, but `markerPayload` strips `city_id` / `city_name`. V3.5.4 therefore painted only Port Said.  
   **V3.5.5:** group approximate markers by shared city-centroid coordinates + venue city suffix → one honest aggregate per city. Unresolvable cities stay list-only (no unreliable pin).

2. **Aggregate click auto-opened first event card**  
   Blocked list-first discovery. **V3.5.5:** close card, open city panel list, user picks an event → floating card.

3. **Marker semantics / a11y**  
   Counts = real events; aria/title distinguish aggregate vs exact. MapLibre’s default `"Map marker"` aria overwritten after `addTo`.

4. **No fake fan-out**  
   Approximate events never get individual jittered pins.

---

## Changes implemented (preview-only)

| File | Change |
|---|---|
| `resources/js/living-map/create-living-map.js` | City-centroid aggregate synthesis; list-first `enterCity`; pin/agg aria + tooltips; card/list `when`; MapLibre `ariaLabel` re-assert; `wrapMarkerVisual` preserved |
| `resources/js/living-map/i18n.js` | `aggregateEventsInCity`, `exactEventPin`, `cityClusterCount` |
| `lang/en/living_map.php` / `lang/ar/living_map.php` | Matching strings |
| `resources/views/components/living-map/stage.blade.php` | New `data-i18n-*` attrs |
| `resources/css/living-map-v35.css` | Premium city-panel polish (scoped `.lm-shell--v35`) |
| `resources/views/preview/living-map-v35.blade.php` | Banner → V3.5.5 |

**Not changed:** production `/` homepage, DB, event coordinates, Flutter, factories.

---

## Screenshots

| File | Case |
|---|---|
| `01-desktop-initial.jpg` | Desktop initial viewport (Port Said · 4, Damietta · 3; Mansoura clipped west) |
| `02-exact-location-marker.jpg` | Exact venue pin + card (event 605 · 6th of October, radius All) |
| `03-approximate-city-aggregate.jpg` | Port Said aggregate selected (count 4) |
| `04-aggregate-event-list.jpg` | Aggregate event list panel |
| `05-floating-event-card.jpg` | Floating event card (real image/title/when/place/members) |
| `05b-desktop-card-context.jpg` | Card in map context |
| `06-location-filter.jpg` | Location filter (city prominent, GPS optional, radius labels) |
| `07-mobile-layout.jpg` | Mobile layout |
| `08-arabic-rtl.jpg` | Arabic RTL layout |

Before (V3.5.4): see `docs/discovery/v3_5_4_qa/01-desktop-initial.jpg` (Port Said only).  
After (V3.5.5): Damietta · 3 + Port Said · 4 honest aggregates; list-first drill-down.

---

## Interaction tests

| Test | Result |
|---|---|
| Initial UI shows Port Said **4** | Pass |
| Damietta **3** aggregate from city centroid (no fan-out) | Pass |
| Aggregate click → city list (no auto card) | Pass |
| List row → floating card with real fields only | Pass |
| Exact pin (605) after All + fly | Pass |
| Location filter readable / non-overlapping | Pass |
| Mobile EN | Pass |
| Arabic `lang=ar` `dir=rtl` | Pass |
| Homepage isolation (no `.lm-shell--v35` on `/`) | Pass |
| `DiscoverMapApiTest` (13) | Pass |

---

## Remaining limitations

- Mansoura · 2 is geographically correct but often **clipped** under Port Said 25 km framing (west of stage).
- Damietta/Mansoura aggregates in this origin mode are **client-synthesized** from verified city-table centroids (API omits them from `city_aggregates` under Port Said radius). `city_id` is recovered from that verified centroid map for city-events drill-down — still no invented venue coordinates.
- Only **one** live exact-coordinate event (605) in published upcoming data — exact-pin QA requires leaving the Port Said 25 km frame.
- Coastal Port Said pin can still sit near water by city-center geography — intentional, not a Mediterranean invent.
- Headless JPEG may soften pin digits; live DOM counts are authoritative.

---

## Recommendation for owner approval

Approve V3.5.5 preview if:

1. Port Said **4** / Damietta **3** aggregates read as honest city-level (not venue pins).  
2. Aggregate → list → card flow feels clear.  
3. Exact pin (605) + filter + mobile + RTL look acceptable.  
4. Homepage remains V3.4.

**Stop for owner visual review.** Do not promote to production homepage until explicit approval.
