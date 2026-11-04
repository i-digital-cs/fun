# Living World — Procedural Cinematic 3D Feasibility

**Status:** Study + Cairo POC complete — **stopped for owner review**  
**Date:** 2026-11-02  
**Preview POC:** `/preview/living-world-procedural`  
**Baseline V5:** `/preview/living-world-v5`  
**Reference inspected:** `docs/Explore Around You Events Dashboard.png`

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-world-procedural', now()->addDays(7));"
```

**Preserved:** Production Home V3.4 · Living Map V4 · Living World V5  
**Out of scope:** Full product version · production cutover · paid APIs/assets · Flutter · DB writes  

Evidence pack: `docs/discovery/v5_procedural_qa/` (`metrics.json` + screenshots).

---

## Recommendation

### **CONDITIONAL**

| Decision lens | Verdict |
|---|---|
| Is procedural 3D on **real footprints** technically feasible with free stack? | **YES** — MapLibre custom layer + Three.js POC works in Cairo. |
| Does it **significantly** close the gap to the approved illustrated reference? | **NO** — modest uplift over V5 extrusion (materials, roof tint, optional trees/shadows), still cartographic massing, not a cinematic diorama. |
| Should Funnity invest here next? | **CONDITIONAL** — pursue as an **incremental Living World renderer upgrade** if the product target is “premium geographic 3D,” not “mock-quality illustrated city.” |

**Do not** promise reference parity from procedural façades alone.

---

## 1. Technical options evaluated

| # | Option | Feasibility | Notes |
|---|---|---|---|
| 1 | MapLibre native `fill-extrusion` (V5) | Proven | Fast, continuous Egypt; flat shading; limited roofs/materials |
| 2 | MapLibre **custom layer** + Three.js | **Proven in POC** | Full material/light/shadow control; must manage LOD + tile loading |
| 3 | Procedural façade generation | Proven (simple) | Canvas window grids + palette; readable mainly at closer zooms |
| 4 | Roof variations | Proven (simple) | Flat / thicker slab tints; not true hip/gable architecture |
| 5 | Stylized roads/sidewalks in Three.js | Possible, not in POC | Better left to vector style paint for scale; 3D roads expensive |
| 6 | Procedural vegetation | Proven (sparse) | Low-poly trees near large footprints — **artistic**, not surveyed flora |
| 7 | Ground lighting / shadows | Partial | Three.js directional + shadow maps on desktop; disabled on mobile POC |
| 8 | Cinematic pitch | Proven | Same Cairo camera as V5 (~pitch 58°) |
| 9 | LOD management | Minimal POC | Cap N largest footprints (100 desktop / 45 mobile); needs tile streaming rebuild on pan |
| 10 | Mobile GPU/memory | Measured | Caps + no shadows/trees; see §4 |

**Rejected for this free study:** Maptoolkit paid 3D tiles, TomTom ModelsLayer, paid glTF city packs.

---

## 2. Working proof of concept

### What shipped

- Isolated route `preview.living-world-procedural`
- Same Cairo downtown camera for A/B:
  - **Procedural 3D** — Three.js meshes from OSM rings
  - **V5 extrusion** — native `building-3d` toggle
- Invisible tile-keeper layer so footprints still load when extrusion is hidden
- Metrics HUD: building count, mesh build ms, FPS samples, JS heap (when Chromium exposes it)
- Disclaimer: artistic façades/roofs/trees — not surveyed architecture

### Geographic integrity

- Building **footprints** from OpenFreeMap / OpenMapTiles / OSM  
- Heights from `render_height` when present; otherwise visualization fallback  
- Decorative trees offset from footprint centroids — **not** GPS plants  
- No invented event pins in this POC (renderer study; Discovery can overlay as in V5)

### Screenshots

| File | Content |
|---|---|
| `v5_procedural_qa/01-procedural-desktop.jpg` | Procedural mode full page |
| `v5_procedural_qa/02-procedural-stage.jpg` | Procedural stage (colored roofs / Three.js massing) |
| `v5_procedural_qa/03-extrusion-stage.jpg` | Same camera, V5 fill-extrusion |
| `v5_procedural_qa/04-extrusion-desktop.jpg` | Extrusion full page |
| `v5_procedural_qa/05-v5-cairo-stage.jpg` | Separate V5 preview Cairo framing |
| `v5_procedural_qa/06-mobile-procedural.jpg` | Mobile procedural (45 building cap) |

---

## 3. Visual comparison (honest)

| Trait | Approved reference | V5 extrusion | Procedural POC |
|---|---|---|---|
| Continuous real geography | No (illustrated plate) | Yes | Yes |
| Building footprint truth | Artistic | OSM | OSM |
| Façade richness | High (concept art) | Solid color extrusion | Light window grid + palette (subtle at city zoom) |
| Roof variety | High | Flat extrusion tops | Tinted slab variants |
| Vegetation / ships / landmarks | Yes | No | Sparse low-poly trees only |
| Soft cinematic lighting | Baked art | MapLibre light | Three.js lights + optional shadows |
| Closeness to reference | Target | Far | **Still far** — closer than V5 in material variety, not in art direction |

**Net visual delta vs V5:** noticeable but **not transformative** at discovery zoom. Gains show more when zooming toward street scale (texture repeat / roof tint). Reference quality remains a different medium.

---

## 4. Performance evidence (measured)

Source: `docs/discovery/v5_procedural_qa/metrics.json` (headless Chromium).

### Desktop 1440×1100 — procedural

| Metric | Value |
|---|---|
| Procedural buildings | **100** (capped; 114 source feats in view) |
| Artistic trees | **21** |
| Mesh build | **56 ms** |
| Style load | **4 ms** |
| Map idle | **~1.8 s** |
| FPS samples | 11 → 45 → **58** (settle) |
| JS heap | **~73 MB** (`performance.memory`) |
| Shadows | on |

### Desktop — extrusion toggle (same session)

| Metric | Value |
|---|---|
| Rendered `building-3d` feats | **73** |
| FPS sample during switch | noisy (6–40); not a steady-state claim |
| Procedural layer | hidden (meshes retained in memory) |

### Mobile 390×844 — procedural

| Metric | Value |
|---|---|
| Buildings | **45** cap |
| Trees / shadows | **off** |
| Mesh build | **42 ms** |
| Map idle | **~1.1 s** |
| FPS samples | 38 → 59 → **60** |
| JS heap | **~51 MB** |

### Asset / bundle

| Asset | Size |
|---|---|
| POC JS (gzip) | ~5.6 KB |
| POC CSS (gzip) | ~1.0 KB |
| Three.js r160 CDN | shared browser cache (~CDN; not bundled) |
| MapLibre 4.7.1 CDN | same as V5 |
| Paid 3D assets | **none** |

**Stability:** No page errors in capture run after tile-keeper fix. Earlier failure mode (0 buildings) was MapLibre not loading building tiles when both building layers were `visibility: none` — fixed with opacity-0 keeper layer.

---

## 5. Licensing & hosting

| Component | License / cost |
|---|---|
| MapLibre GL JS | BSD-3 · free |
| Three.js r160 | MIT · free |
| OSM / OpenMapTiles / OpenFreeMap | ODbL + attribution · free hosted tiles with **fair-use limits** |
| Procedural code in-repo | Funnity product code |
| Paid map/3D APIs | **Not used** |

**Infra (separate from software licenses):** OpenFreeMap is fine for preview; production Living World still needs a self-host / Protomaps plan before scale (same as V5 report). Three.js adds client GPU/CPU cost, not a SaaS fee.

---

## 6. Limitations

1. **Reference gap remains large** — no ships, palms-as-art-direction, stadium kits, or hand-illustrated lighting.  
2. **Façade detail weak at discovery zoom** — window grids help locally, not skyline-wide.  
3. **LOD is naïve** — rebuild on idle; panning a metropolis needs chunked streaming / instancing.  
4. **Shadow maps** are desktop-only in POC; mobile uses unshadowed materials.  
5. **Memory** retains procedural meshes when toggling to extrusion (POC convenience).  
6. **Roads/sidewalks** not procedurally meshed (vector style still carries them).  
7. **Nationwide Egypt** not stress-tested with Three.js — Cairo pocket only.  
8. **Flutter** not addressed.

---

## 7. Paths if owner wants more beauty

| Path | Integrity | Cost | Likelihood of matching reference |
|---|---|---|---|
| A. Iterate procedural LOD + better materials + closer default zoom | High | Eng only | Low–medium |
| B. Sparse hand-authored glTF landmarks snapped to real coords | High if sparse + labeled art | Art + eng | Medium for hero POIs only |
| C. Illustrated plates (V4) | Breaks continuous geo | Art | High local beauty, wrong product requirement |
| D. Paid 3D city providers | Vendor-dependent | $$ | Medium–high, conflicts with “no paid APIs” |

Recommended if continuing: **A**, optionally light **B** for a few Cairo icons, keep Discovery SSOT.

---

## 8. Owner decision

1. **Accept CONDITIONAL** — treat procedural Three.js as a Living World enhancement track (not a mock replica).  
2. **Stay on V5 extrusion** — lower complexity; accept flatter look.  
3. **Re-open illustrated / hybrid art** — only with explicit acceptance that continuous free geography cannot equal the PNG.

---

## Stop

No further Living World versioning until the owner reviews this feasibility pack and chooses a path.
