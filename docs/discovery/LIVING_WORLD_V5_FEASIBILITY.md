# Living World V5 — Feasibility Report

**Status:** Feasibility prototype complete — **stopped for owner review**  
**Preview:** `/preview/living-world-v5` (auth, signed URL, or preview token)  
**Date:** 2026-11-02  

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-world-v5', now()->addDays(7));"
```

**Preserved:** Production Home V3.4 · Living Map V3.5.x · Living Map V4 illustrated hybrid  
**Out of scope:** Production cutover · paid map APIs · Flutter · database writes  

**Primary visual reference inspected:** `docs/Explore Around You Events Dashboard.png`

---

## Recommendation

### **CONDITIONAL**

| Verdict | Meaning |
|---|---|
| **GO** for geographic direction | Continuous MapLibre + OpenFreeMap + OSM building extrusion is the correct architecture for “real Egypt map you can pan/zoom,” and is a clear improvement over V3.5 flat basemap framing. |
| **NOT GO** for reference parity | Available free geographic data **cannot** reproduce the approved mock’s illustrated city diorama (ships, palms, architectural kits, soft art lighting, landmark meshes). |
| **CONDITIONAL product path** | Proceed with Living World **only if** the owner accepts **premium cartographic 3D massing** as the visual target — not the V4 static plate, and not pixel-matching the mock. |

Owner decision needed: accept geographic cinematic massing as the brand look, or fund a different (non-free / custom 3D world) approach.

---

## 1. Screenshots (evidence)

All under `docs/discovery/v5_0_qa/`:

| File | Content |
|---|---|
| `01-cairo-desktop.jpg` / `02-cairo-stage.jpg` | Cairo — dense extrusion, Nile, bilingual labels, Discovery pin |
| `01-port-said-desktop.jpg` / `02-port-said-stage.jpg` | Port Said — waterfront/canal, thinner building massing |
| `01-alexandria-desktop.jpg` / `02-alexandria-stage.jpg` | Alexandria — Mediterranean edge, moderate massing |
| `03-egypt-overview-continuity.jpg` | Continuous Delta / coast pan (no city plate swap) |
| `04-mobile-cairo.jpg` | Basic mobile web |
| `metrics.json` | Per-city building sample + timing |

**No static city background images** are used in the map stage.

---

## 2. Data coverage assessment

Samples from `queryRenderedFeatures` on `building` / `building-3d` after city framing (desktop capture). Counts are **viewport samples**, not city-wide OSM totals.

| City | Rendered building feats | With `render_height` > 0 | Height p50 / p90 / max (m) | Distinct heights | Coverage read |
|---|---:|---:|---|---:|---|
| **Cairo** | 123 | 121 | 26 / 105 / 187 | 53 | **Strong** — varied massing, usable cinematic 3D |
| **Port Said** | 10 | 10 | 5 / 30 / 37 | 5 | **Weak–moderate** — sparse footprints in framed waterfront view; streets/water clear |
| **Alexandria** | 28 | 28 | 11 / 41 / 55 | 13 | **Moderate** — waterfront + some extrusion; thinner than Cairo |
| Cairo mobile | 62 | 61 | 33 / 110 / 166 | 39 | Extrusion still present on narrow viewport |

### Building heights

OpenFreeMap OpenMapTiles `building` layer fields: `render_height`, `render_min_height`, `colour`, `hide_3d`.

- Heights are **OSM-derived** (tags / levels heuristics inside the tile pipeline), **not** a surveyed Egypt DEM.
- V5 style uses OSM `render_height` when &gt; 0; otherwise a **visualization fallback of 11 m**, labeled in UI as not surveyed.
- Cairo shows genuine height diversity → extrusion reads “city-like.”
- Port Said / Alexandria often sit nearer default/low heights → flatter skyline vs the mock.

### Streets & water

| Layer | Observation |
|---|---|
| Street geometry | Present and continuous in all three cities (OpenMapTiles roads). |
| Waterfront | Nile (Cairo), Mediterranean + canal geometry (Port Said / Alexandria) render clearly with cinematic water paint. |
| Landmarks as 3D models | **Not available** in free tiles — no ships, palms, stadium meshes. |

---

## 3. Technical implementation

### Stack (free / open)

| Layer | Choice |
|---|---|
| Engine | MapLibre GL JS 4.7.1 |
| Tiles / style base | OpenFreeMap Liberty → remapped `funnity-style-v5.js` |
| Buildings | OSM footprints via OpenMapTiles `building` + `fill-extrusion` (`building-3d`) |
| Events | Existing `GET /api/discovery/map` only |
| 3D frameworks | **None** for this prototype (Three.js evaluated, not required for feasibility) |

### Preview isolation

- Route: `preview.living-world-v5` → `/preview/living-world-v5`
- Vite entry: `resources/js/living-world-v5.js` (not imported by production Home)
- City switcher: Cairo · Port Said · Alexandria (real centroids from cities table)

### MapLibre 3D capabilities used

- Pitch up to ~58° (maxPitch 72)
- `fill-extrusion` with vertical gradient
- `map.setLight` for soft directional massing
- Optional sky (when supported)
- Continuous pan/zoom across Egypt tiles

### Three.js / custom models (evaluation only)

| Approach | Feasibility | Risk |
|---|---|---|
| MapLibre-only extrusion | **Practical now** | Visual ceiling = extruded footprints |
| Three.js overlay georeferenced models | Possible later | Alignment drift, perf, licensing of models; easy to fake geography if misused |
| Per-city illustrated plates (V4) | Rejected by owner for this requirement | Breaks continuous geography |

**V5 prototype stays MapLibre-only** to prove continuous geography without decorative substitution.

---

## 4. Performance results

| Metric | Result |
|---|---|
| Style remap fetch/build | ~7 ms (cached network path in capture) |
| Initial map → idle (Cairo desktop) | ~2.3 s |
| Mobile Cairo idle (after fly) | ~1.0 s |
| V5 JS gzip | ~5.6 KB |
| V5 CSS gzip | ~2.0 KB |
| Heavy 3D engine | Not loaded |

**Qualitative:** Desktop Cairo extrusion is smooth in headless Chromium. Mid-range mobile will need marker caps + optional building minzoom raise in production. OpenFreeMap hosted tiles are **not** a guaranteed unlimited production CDN — plan self-host / Protomaps before scale.

---

## 5. Reference comparison (honest)

| Reference trait | V5 geographic prototype | Gap |
|---|---|---|
| Isometric / cinematic camera | Strong pitch + bearing | Cartographic tilt ≠ illustrated diorama |
| Rich architectural detail | Extruded footprints only | **Large** — no façades, ships, palms, stadium kits |
| Pastel palette + brand color | Yes (Funnity remap) | Closer to premium map than to concept art |
| Soft lighting / shadows | Light + extrusion gradient | No rich directional soft shadows / tilt-shift art |
| Glowing category pins | Yes (Discovery-backed) | Density follows real data, not mock |
| Floating event cards | Yes | Same integrity rules as Living Map |
| Continuous pan/zoom Egypt | **Yes** | Reference is a single city plate |
| No per-city illustration dependency | **Yes** | — |

**Vs V3.5:** V5 is a real improvement — earlier/more assertive extrusion, cinematic light, higher pitch, softer pastel roads/water, city test harness, explicit height honesty, shield suppression.

**Vs V4:** V5 meets the owner’s **geographic continuity** requirement; V4’s static Port Said plate does not.

---

## 6. Licensing & hosting

| Component | Terms (summary) | Production note |
|---|---|---|
| MapLibre GL JS | BSD-3 | OK |
| OpenStreetMap data | ODbL — share-alike on DB extracts; attribution required | Keep attribution visible |
| OpenMapTiles schema | See OpenMapTiles license | Used via OpenFreeMap |
| OpenFreeMap hosted tiles | Free community service + attribution | **Fair-use / capacity limits** — do not assume unlimited traffic |
| Self-host Protomaps / PMTiles | Infra cost only | **Recommended** before Home cutover at scale |
| Paid Mapbox / Google | Not used | Do not introduce without owner approval |

---

## 7. Limitations

1. **Visual ceiling:** Cannot match the approved illustrated reference with OSM extrusions alone.  
2. **Uneven Egypt coverage:** Cairo ≫ Alexandria &gt; Port Said (in tested frames).  
3. **Heights are not ground truth:** OSM/`render_height` + visualization fallback.  
4. **No landmark 3D library** in free tiles.  
5. **Hosted tile scalability** unknown — treat OpenFreeMap as prototype-grade.  
6. **Accessibility:** pitched 3D maps need strong List/Nearby fallbacks (existing product modes).  
7. **Flutter:** not built; tracked in `FLUTTER_WEB_PARITY.md` §10 item 38.

---

## 8. Integrity checklist

- [x] Continuous geographic tiles (no city JPG basemap)  
- [x] Real pan/zoom between Egyptian locations  
- [x] Discovery API events only (no invented pins/counts/images)  
- [x] Approximate vs exact honesty in spotlight copy  
- [x] Height fallback disclosed as visualization  
- [x] Production Home untouched  
- [x] V3.5 / V4 preserved  

---

## 9. Owner decision options

1. **Accept CONDITIONAL GO** — iterate Living World geographic cinematic style toward Home (still preview-gated until explicit cutover).  
2. **Reject visual ceiling** — keep searching for a different medium (custom 3D world / licensed city kits) knowing that breaks or burdens continuous free geography.  
3. **Hybrid product** — Living World geographic default **plus** optional illustrated “poster” mode (V4-like) clearly labeled as art, never as navigation truth.

---

## Stop

No further V5 production work without owner review of this feasibility package and an explicit choice among the options above.
