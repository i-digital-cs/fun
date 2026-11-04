# Living Map V3.5.5 — Marker Integrity Audit

**Mode:** Read-only investigation (no DB writes, no homepage deploy, no Flutter)  
**Verdict for owner:** The screenshot does **not** contain dozens of extra Funnity event markers. Live DOM enumeration finds **exactly 3** MapLibre event markers (2 on-canvas, 1 off-canvas). Apparent “extra / offshore / desert markers” are **basemap highway shields, place labels, the origin radius overlay, and the origin dot** — not events.

**Evidence artifacts**

| File | Purpose |
|---|---|
| `integrity-01-live-viewport.jpg` | Live initial viewport |
| `integrity-02-markers-highlighted.jpg` | Only `.maplibregl-marker` outlined (lime) |
| `integrity-03-pois-hidden.jpg` | POI/shield layers hidden — event pins remain |
| `integrity-04-basemap-only.jpg` | Event markers hidden — shields/labels/radius remain |
| `integrity-05-radius-and-labels-only.jpg` | Markers + shields hidden |
| `marker_integrity_raw.json` | DOM marker dump + API payload |
| `marker_integrity_poi.json` | Basemap symbol probe + custom layers |

Preview used:

`https://funnity.com/preview/living-map-v35?expires=1794158574&signature=38349c59121f3893af317728574e9242769c41d5ba35c10dd3ade57b857adeb1`

---

## Executive resolution of the discrepancy

| Claim | Finding |
|---|---|
| Audit listed 3 aggregates (Port Said 4 / Damietta 3 / Mansoura 2) | **True in DOM** — 3 `.lm-marker--agg` nodes |
| Screenshot “shows many additional markers” | **Misread basemap + overlays** as event pins |
| Offshore event markers | **None.** Mediterranean coverage is the **25 km radius fill/line** + coastal Port Said city-center pin |
| Desert event markers | **None.** Inland dots/numbers are **road shields / place labels** |
| Fake / decorative event markers | **None** |
| Invented counts | **None** — digit on each agg = distinct real event IDs |
| Forced viewport-edge row (V3.5.3 bug) | **Absent** — transforms match geographic `setLngLat` |

---

## Method

1. Load signed V3.5.5 preview; wait `data-ready=1`.
2. Capture every `.maplibregl-marker` (class, `data-marker-kind`, `data-count`, `data-city-id`, aria, transform, on-canvas?).
3. Capture last `/api/discovery/map` JSON (markers, city_aggregates, clusters).
4. `queryRenderedFeatures` for symbol layers (`poi_*`, `airport`, `highway-shield-*`, city/town/village labels).
5. List custom Living Map layers (`lm-home-*`).
6. A/B screenshots: highlight event markers · hide basemap shields · hide event markers.

---

## A. Every Funnity event marker (MapLibre Marker DOM)

**Total rendered event markers: 3**  
**Exact-location markers: 0**  
**Clusters: 0**  
**Decorative/demo event markers: 0**

Viewport (instrumented): center ≈ `31.2701, 32.2234`, zoom ≈ `9.29`, pitch `36`, bearing `-6`,  
bounds N/S/E/W ≈ `31.847 / 30.916 / 33.069 / 31.255`.

| # | Classification | Kind | Displayed count | `city_id` | Intended coords (data SSOT) | Screen tip (canvas px) | On-canvas? | Event IDs represented | Count verified? | Why here | Status |
|---|---|---|---|---|---|---|---|---|---|---|---|
| M0 | Approximate **city aggregate** | `city-aggregate` | **4** | 11 | `31.2653, 32.3019` = `cities.id=11` Port Said | ≈ (684, 329) | Yes | **421, 423, 445, 453** | Yes — 4 distinct published city-level events | City-table centroid; coastal Port Said (canal/Mediterranean fringe). Radius circle centered here extends over water. | **VERIFIED** |
| M1 | Approximate **city aggregate** | `city-aggregate` | **3** | 14 | `31.4165, 31.8133` = `cities.id=14` Damietta | ≈ (360, 181) | Yes | **413, 433, 416** | Yes | City-table centroid near Damietta label. Venue strings name Damietta (incl. “Desert Road Courts” — **venue name**, not a desert coordinate invent). | **VERIFIED** |
| M2 | Approximate **city aggregate** | `city-aggregate` | **2** | 8 | `31.0409, 31.3785` = `cities.id=8` Mansoura | ≈ (−129, 461) | **No** (west of stage) | **482, 497** | Yes | Honest Mansoura centroid; Port Said · 25 km framing leaves pin **off the left edge**. Not forced-to-edge bug (transform still geographic). | **QUESTIONABLE (UX)** — geo OK, visibility poor |

### Event ↔ aggregate membership (no double representation on the map)

| Event ID | Title | API precision | API lat/lng | Aggregate |
|---|---|---|---|---|
| 421 | Friendly tennis match | approximate | 31.2653, 32.3019 | Port Said · 4 |
| 423 | Console night with friends | approximate | 31.2653, 32.3019 | Port Said · 4 |
| 445 | Blockbuster movie outing | approximate | 31.2653, 32.3019 | Port Said · 4 |
| 453 | Morning tennis drills | approximate | 31.2653, 32.3019 | Port Said · 4 |
| 413 | Evening jog around the lake | approximate | 31.4165, 31.8133 | Damietta · 3 |
| 433 | Friendly football at the pitch | approximate | 31.4165, 31.8133 | Damietta · 3 |
| 416 | Courtside rally night | approximate | 31.4165, 31.8133 | Damietta · 3 |
| 482 | Courtside rally night | approximate | 31.0409, 31.3785 | Mansoura · 2 |
| 497 | Catan + strategy night | approximate | 31.0409, 31.3785 | Mansoura · 2 |

- **9 API approximate markers → 3 UI aggregates.** Individual approx pins are **not** painted (by design in V3.5.5).
- Each event ID appears in **exactly one** aggregate. No duplicate map representation.
- Digits **4 / 3 / 2** are real event counts, not decorations.

---

## B. Non-event visuals commonly mistaken for markers

These are **not** Funnity event markers. They remain visible when event markers are hidden (`integrity-04-basemap-only.jpg`).

| Visual | Source | Example | Looks like | Status | Correction |
|---|---|---|---|---|---|
| Highway shields | OpenFreeMap / style layer `highway-shield-non-us` | refs **10**, **15**, **37** (coastal / Ismailia / Kafr Saad roads) | Numbered white badges near Port Said / Damietta | **VERIFIED** (basemap) | Optional: lower shield prominence under `.lm-shell--v35` or document in UI copy that road numbers ≠ events |
| Place labels | `label_city` / `label_town` / `label_village` | Damietta, Al-Manzala, Al Bar Cape, villages inland | “Pins” in sparse land | **VERIFIED** (basemap) | None required for integrity; labels are cartography |
| POI icons | `poi_r*` / `airport` | (sparse / none dominating this zoom in probe) | Icon pins | **VERIFIED** (basemap) | Keep as-is unless owner wants quieter basemap |
| 25 km radius fill + stroke | Custom layers `lm-home-radius-fill`, `lm-home-radius-line` | Purple translucent disc from Port Said into Mediterranean + inland | “Glow / offshore cluster” | **VERIFIED** (discovery UI) | Optional: clip radius rendering or reduce opacity over water; clarify tooltip “search radius, not events” |
| Origin blue dot | Custom layer `lm-home-origin-dot` | Port Said origin point | Small blue “marker” on coast | **VERIFIED** (discovery UI) | Optional: distinguish style from event teardrops more strongly |
| Teardrop halo | CSS `.lm-marker-halo` under agg pins | Soft purple glow at pin base | Extra blob under pin | **VERIFIED** (chrome) | None for integrity |

**Instrumented shield refs in viewport:** `10`, `15`, `37` (not event counts).  
**No authentic offshore event coordinates** exist in this payload — nothing to verify as a sea venue.

---

## C. API vs rendered markers

| Channel | Count | Contents |
|---|---|---|
| API `markers` | **9** | All `location_precision=approximate` at city centroids (city_id stripped by `markerPayload`) |
| API `city_aggregates` | **1** | Port Said · 4 only (origin+radius filter) |
| API `clusters` | **0** | — |
| UI MapLibre markers | **3** | Port Said 4 + Damietta 3 + Mansoura 2 |

### Discrepancy explanation (not fraud)

1. **API omits Damietta/Mansoura from `city_aggregates`** under Port Said · 25 km origin filter, but still returns their approximate markers via bbox `include_approximate`.
2. **V3.5.5 client** groups those approx points by verified city-table centroids and paints **one aggregate each** (counts 3 and 2) so they are not fanned out as fake venue pins.
3. Therefore UI aggregate count (**3**) **>** API `city_aggregates` (**1**), while UI event membership (**9**) **=** API approximate markers (**9**).

This is an intentional honesty layer on top of incomplete origin-scoped aggregates — **not** invented events.

---

## D. Critical checks

| Check | Result |
|---|---|
| No fake markers | **Pass** — only 3 event markers; all backed by real event IDs |
| No decorative markers presented as events | **Pass** — shields/labels/radius are other layers |
| No invented event counts | **Pass** — 4+3+2 = 9 API approx events |
| No artificial geographic offsets / fan-out | **Pass** — shared city centroids only |
| No markers forced to viewport edges (V3.5.3 bug) | **Pass** — Port Said/Damietta transforms match land; Mansoura off-canvas by framing, not CSS collapse |
| No duplicate representation of same event | **Pass** — disjoint ID sets |
| No misleading offshore **event** positions | **Pass** — no event markers in open Mediterranean; coastal Port Said centroid is city-table truth |

---

## E. Classification summary

| Item | Class | Recommended correction |
|---|---|---|
| Port Said aggregate · 4 | **VERIFIED** | None. Optional copy: “city center (coastal)”. |
| Damietta aggregate · 3 | **VERIFIED** | None for integrity. Prefer API `city_aggregates` to include nearby cities when approx markers are returned (backend consistency) so client synthesis is unnecessary. |
| Mansoura aggregate · 2 | **QUESTIONABLE (UX)** | Geo **VERIFIED**. Correction options: (a) omit off-canvas aggregates until pan/zoom includes them; (b) slightly widen framing when their centroids fall just outside; (c) keep but accept clip. **Do not** invent inland offsets to force visibility. |
| Highway shields 10 / 15 / 37 | **VERIFIED** (not events) | Soften basemap shields in V3.5 skin if owner finds them confusing. |
| Radius disk over Mediterranean | **VERIFIED** (UI overlay) | Clarify as search radius; optional opacity tweak. |
| Origin blue dot | **VERIFIED** (UI overlay) | Differentiate from event teardrops if needed. |
| Any “row of offshore event pins” | **INVALID as an event-marker claim** | No such event markers exist in DOM/API. Treat as misidentification of shields/radius/labels. **No coordinate rewrite.** |

---

## F. Why the prior geographic audit felt incomplete

- `VISUAL_QA.md` correctly listed **three** aggregates including Mansoura · 2.
- The **screenshot** only clearly shows Port Said · 4 and Damietta · 3 because Mansoura is **off-stage**.
- Numbered **road shields** and the **purple radius** create a strong false impression of “many more pins,” especially offshore and over sparse land.

---

## G. Recommendation for owner

1. Treat **only the purple teardrop aggregates** as event markers (counts 4 and 3 on-canvas; 2 off-canvas).
2. Do **not** treat highway numbers, place labels, the blue origin dot, or the purple radius as events.
3. Optional product follow-ups (still preview-only until approved): quieter shields; hide off-canvas aggregates; backend `city_aggregates` parity with returned approx cities.
4. **No database coordinate changes required** for this integrity finding.

**Stop for owner review.**
