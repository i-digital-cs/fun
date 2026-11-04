# FUNNITY — Living Map V4 Architecture Proposal

**Status:** Architecture approved (Option C Hybrid). **V4.0 visual prototype shipped** at `/preview/living-map-v4` — awaiting owner visual acceptance before further V4 work.  
**Date:** 2026-11-02  
**Scope:** Web Living Map preview / future discovery surface  
**Out of scope until further approval:** Production homepage replacement · database writes · Flutter · full multi-city rollout  

**Evidence:** `docs/discovery/v4_0_qa/VISUAL_QA.md`

---

## Reference assets inspected

| Role | Path | Notes |
|---|---|---|
| **Approved cinematic / isometric reference** | `docs/Explore Around You Events Dashboard.png` | Owner “Explore Around You” mockup — isometric coastal city, rich architecture, multicolor category pins, glass controls, floating spotlight, carousel |
| Duplicate of same file | `docs/a_clean_modern_colorful_ui_web_dashboard_mockup.png` | Identical bytes (`md5 4879c12e…`) — same reference under a second name |
| **Current Living Map (rejected direction)** | `docs/discovery/v3_5_7_qa/acceptance-01-fresh-desktop-load.jpg` | V3.5.7 preview — MapLibre + OpenFreeMap geographic basemap, glowing purple aggregate pins |

V3 docs already cited this mockup (`FUNNITY_LIVING_MAP_V3_ARCHITECTURE.md`, `FUNNITY_LIVING_MAP_V3_VISUAL_SPEC.md`) and **explicitly deferred** illustrated ships, palms, stadium meshes, and decorative landmarks. V3.5.x polished that deferred path. The owner has now rejected continuing that path.

---

## 1. Root cause of visual mismatch

The gap is **not** “MapLibre needs more polish.” It is a **product architecture mismatch**.

| Layer | Approved reference | V3 → V3.5.x shipping direction |
|---|---|---|
| World model | Stylized **isometric illustrated city** (Port Said–like coastal scene) | Real **geographic cartography** (OSM / OpenFreeMap Liberty remapped) |
| Buildings | Hand-crafted architecture, beaches, ships, palms, skyline | OSM footprints + optional decorative `building-3d` extrusion |
| Lighting | Soft sunny cinematic lighting baked into art | Style paint + fog / pitch on a flat map |
| Markers | Premium **multicolor category icon pins** + count clusters | Mostly purple teardrop / glow aggregates |
| Spatial language | Discovery diorama with arcs / relationships | Lat/lng projection of Egypt coastline |
| Trust framing | Illustration reads as **experience**, not survey accuracy | Map reads as **geography**, so decorative OSM shields / labels compete with event pins |

V3.5.x correctly improved:

- Discovery API integrity (exact vs city aggregate)
- Spotlight, carousel, location glass panel, framing
- Marker–spotlight association and honesty copy (“Approximate city location”)

Those are **UX/data wins**. They do not convert a geographic basemap into the approved isometric city-discovery experience. Further MapLibre remapping, pitch, glow, or fog will remain **cartography with brand paint** — visually closer to Google/Apple Maps than to the reference.

**Root cause (one sentence):** Funnity chose a geographic MapLibre engine to *approximate* a cinematic mockup that is fundamentally an **illustrated isometric scene**, then optimized that approximation until the owner rejected the category of solution.

---

## 2. Detailed reference comparison

Inspected side-by-side: approved PNG vs V3.5.7 acceptance capture.

### 2.1 What matches today (keep / reuse)

| Element | Reference | Current | Verdict |
|---|---|---|---|
| Page chrome | Explore Around You + Map / List / Nearby | Same pattern on preview | Keep |
| Glass location panel | City + “Use my location” + distance | Present (radius chips) | Keep; restyle to match reference density |
| Floating event spotlight | Card over selected pin | Present (V3.5.6–7) | Keep; tighten typography / media |
| Events carousel | Horizontal rich cards under stage | Present | Keep; align card chrome to reference |
| Brand colors | Purple `#7138F5` · orange `#FF762E` | In tokens / CTAs | Keep |
| EN / AR bilingual chrome | Present in mockup | Preview + Tajawal path | Keep |
| Discovery honesty | (Mock invents some social proof) | Real API + approximate disclaimers | **Prefer current integrity** over mock inventiveness |

### 2.2 What does **not** match (must change for V4)

| Element | Reference | Current V3.5.x | Gap severity |
|---|---|---|---|
| Basemap / world | Isometric illustrated city, water craft, palms, white buildings, sandy coast | Flat / pitched OSM streets, Arabic/English toponyms, road shields | **Critical** — defines the entire first impression |
| Depth & camera | Fixed cinematic isometric “diorama” | Geographic pitch (~40–55°) still reads as a map | **Critical** |
| Event markers | Colorful category icons (music, coffee, game, theater, etc.) | Purple glow teardrops + numeric aggregates | **High** |
| Connection arcs | Soft purple/orange relationship arcs | Optional tour arcs only; not a visual language | Medium |
| Ambient detail | Ships, stadium, beach life as art | Absent (intentionally deferred in V3 visual spec) | **Critical** for fidelity |
| Marker density language | Mixed pin + cluster choreography across the scene | Sparse aggregates on real Nile Delta geography | High (art + placement model) |
| Typography hierarchy | Bold bilingual title over immersive scene | Good, but sits over geographic map | Medium |

### 2.3 Data integrity warning from the reference

The approved mockup shows:

- Invented-looking participant faces / “+8 going”
- “Live Now” badges
- Precise-looking distances and pin placements on decorative landmarks

**V4 must not ship those as real data.** The visual language may inspire UI chrome; event titles, images, counts, and coordinates must continue to come from existing Discovery APIs. Decorative landmarks in an illustration are **not** venues.

---

## 3. Three architecture options

### Option A — MapLibre 3D (enhanced geographic)

**Idea:** Stay on MapLibre GL. Push 3D buildings, custom lighting, richer HTML/WebGL markers, stronger Funnity style, optional terrain.

| Criterion | Assessment |
|---|---|
| Visual fidelity to reference | **Low–medium.** Can look premium-cartographic; cannot become hand-illustrated isometric Port Said without abandoning OSM truthfulness. |
| Geographic accuracy | **High.** Real tiles, real bounds, real pins. |
| Performance | **Good** on desktop; 3D buildings + many HTML markers cost mobile GPU. Already proven in V3.x. |
| Mobile responsiveness | **Good** with careful marker caps; pitch/3D harder on low-end Android. |
| Accessibility | MapLibre + DOM overlays can be made accessible; basemap itself is visual-only. |
| Maintenance | Medium — style JSON, tile provider, RTL plugin, marker CSS (known pain: MapLibre transform ownership). |
| Implementation effort | **Low–medium** relative to B/C — extends existing `create-living-map.js`. |
| Cost / licensing | MapLibre BSD; OpenFreeMap / OSM attribution; self-host Protomaps later. Low cash cost. |

**Verdict:** Best continuity with V3.5.x. **Fails the owner’s visual rejection** if treated as the primary destination look. Suitable as a **secondary “Navigate / Precise map” mode**, not as the cinematic hero.

---

### Option B — Interactive isometric scene

**Idea:** Replace the geographic stage with a high-quality illustrated (or 3D-rendered) city environment. Place interactive hotspots for events. Cinematic camera / parallax. No OSM basemap in the hero.

| Criterion | Assessment |
|---|---|
| Visual fidelity to reference | **Highest** if art matches the mockup quality. |
| Geographic accuracy | **Low** by design. Hotspots are scene coordinates mapped from city/event data — must be labeled as illustration, never as survey pins. |
| Performance | Depends on tech: static illustration + DOM/SVG hotspots = excellent; full WebGL city = heavier. |
| Mobile responsiveness | Illustration + limited hotspots scales well; heavy 3D scenes need LODs. |
| Accessibility | Requires strong non-visual path (List / Nearby already exist) + labeled hotspots + reduced-motion. |
| Maintenance | **High** — art pipeline per city (or procedural kit); hotspot layout tools; asset updates when cities expand. |
| Implementation effort | **High** — new renderer, asset pipeline, hotspot editor, integrity UX. |
| Cost / licensing | Art commission or licensed pack; possible 3D engine; ongoing city coverage cost. |

**Tech sub-options for B:**

1. **2.5D illustrated canvas** — High-res isometric PNG/WebP (or layered parallax) + SVG/DOM hotspot layer. Closest to mockup, lowest runtime cost.  
2. **WebGL diorama** (Three.js / Babylon) — Reusable building kits, day lighting, camera dolly. Higher fidelity motion; higher build cost.  
3. **Hybrid art + light 3D markers only** — Flat art plate + floating 3D/CSS pins.

**Verdict:** Wins the “wow” test for one or few launch cities. Weak as a nationwide Egypt discovery map unless art coverage is planned. Must never pretend decorative buildings are GPS-accurate venues.

---

### Option C — Hybrid (recommended)

**Idea:** Two complementary modes sharing one discovery state machine and the same APIs:

1. **Discover (Cinematic)** — Interactive isometric / illustrated city scene (Option B engine) as the default Living Map experience.  
2. **Navigate (Geographic)** — MapLibre precise map (Option A) for “Search this area,” turn-by-turn-ish framing, exact venue pins, nationwide / cross-city exploration.

| Criterion | Assessment |
|---|---|
| Visual fidelity to reference | **High** in Discover mode. |
| Geographic accuracy | **High** in Navigate mode; Discover mode explicitly non-cartographic. |
| Performance | Split budgets: light art scene by default; MapLibre loaded on demand when Navigate opens. |
| Mobile responsiveness | Discover-first is kinder on mobile; Navigate optional. |
| Accessibility | List / Nearby + mode switch + clear “Illustrated view” labeling. |
| Maintenance | Higher than A alone; lower risk than B-only nationwide claims. City kits roll out progressively. |
| Implementation effort | **Medium–high** — shared state + two renderers; phased city art. |
| Cost / licensing | Art for priority cities + existing free MapLibre/OFM path. |

```
┌─────────────────────────────────────────────────────────┐
│  Living Map shell (chrome, filters, Map|List|Nearby)    │
│  Location origin + distance (existing discovery state)  │
└───────────────┬─────────────────────────┬───────────────┘
                │                         │
     ┌──────────▼──────────┐   ┌──────────▼──────────┐
     │  DISCOVER mode      │   │  NAVIGATE mode      │
     │  Isometric scene    │   │  MapLibre GL        │
     │  Hotspots ↔ events  │   │  Exact / aggregate  │
     │  “Illustrated” badge│   │  Real geography     │
     └──────────┬──────────┘   └──────────┬──────────┘
                │                         │
                └──────────┬──────────────┘
                           ▼
              GET /api/discovery/map
              (+ city-events / places as today)
                           ▼
         Shared: spotlight · carousel · selection · i18n
```

**Verdict:** Matches owner visual direction **without** discarding geographic integrity or existing systems. Clear mental model: *illustration for feeling, map for truth.*

---

## 4. Recommended architecture

**Recommend Option C — Hybrid**, with these product rules:

1. **Default mode = Discover (cinematic isometric)** for Living Map preview / future Home Explore stage.  
2. **Navigate (MapLibre)** available via control (layer toggle / “Precise map”) — reuse V3.5.x map work rather than deleting it.  
3. **Single SSOT** for events: existing `GET /api/discovery/map` (+ existing city / places endpoints). No parallel fake event store.  
4. **Integrity chrome (mandatory):**  
   - Discover: persistent “Illustrated city view · not exact street positions” (EN/AR).  
   - Aggregates / approximate: keep current honesty copy.  
   - Exact venues: only shown as exact in Navigate (or when API `exact` and user opts into geographic framing).  
5. **City coverage strategy:** Launch with **1–2 hero cities** (e.g. Port Said art matching reference, then Cairo) with a graceful fallback: if no scene kit exists for the selected city → auto Navigate mode or a branded abstract “region plate” that still does not invent venues.  
6. **Flutter:** Remains out of scope for V4 web proposal; document parity later per workspace rules when member web ships.  
7. **Production Home:** Stays on current approved version until a **separate** owner approval after V4 preview acceptance.

This recommendation accepts that V3.5.x was the right *data and chrome* investment and the wrong *world renderer* for the approved mockup.

---

## 5. Technical stack (proposed)

### 5.1 Preserve (no rewrite)

| System | Role in V4 |
|---|---|
| `GET /api/discovery/map` | Markers, clusters, city aggregates, bbox, zoom |
| ResolveDiscoveryOrigin / location state | Manual / device / city / distance |
| Living Map chrome | Map · List · Nearby, search, glass location panel patterns |
| Event cards / spotlight / carousel concepts | Shared presenters; restyle only |
| Auth / preview gates | Same isolation pattern as V3/V3.5 until approval |
| i18n `living_map.*` | Extend copy for illustrated vs geographic modes |

### 5.2 Discover mode (new)

| Layer | Proposal | Notes |
|---|---|---|
| Scene asset | Layered WebP/AVIF isometric plate **or** glTF city kit | Prefer layered 2.5D for v1 speed-to-beauty |
| Hotspot engine | SVG/DOM overlay with scene-normalized coordinates `(u,v)` | Map `city_id` / event ids → hotspot anchors via config JSON — **not** lat/lng projected onto art without an explicit mapping table |
| Motion | CSS/WAAPI + optional lightweight canvas particles | Respect `prefers-reduced-motion` |
| Arcs | SVG cubic paths between hotspots | Decorative relationship only; never imply routing |
| Markers | Multicolor category icon pins (SVG sprite) | Align with reference; counts from API only |

**Scene ↔ data contract (conceptual):**

```json
{
  "city_id": 123,
  "scene_id": "port-said-v1",
  "disclaimer": "illustrated",
  "hotspots": [
    { "id": "hs_marina", "u": 0.62, "v": 0.48, "bind": "city_aggregate" },
    { "id": "hs_stadium", "u": 0.71, "v": 0.55, "bind": "category_slot", "category": "sports" }
  ]
}
```

Binding rules: hotspots receive **real** events filtered by city/category from the API. Empty hotspot → hidden or muted empty state. **Never** invent an event to fill a decorative slot.

### 5.3 Navigate mode (evolve V3.5.x)

| Layer | Proposal |
|---|---|
| Engine | MapLibre GL 4.x (existing) |
| Tiles | OpenFreeMap → monitor; Protomaps self-host at scale |
| Style | Keep Funnity remap; reduce highway-shield competition with pins |
| Markers | Exact + aggregate integrity model already proven |
| Load | Lazy-init when user opens Navigate (code-split) |

### 5.4 Explicitly not required for V4 hero fidelity

- Paid Mapbox / Google Maps (unless owner later mandates Navigate quality)  
- Inventing OSM building heights as “architecture”  
- Treating Option A alone as visual equivalence to the reference  

---

## 6. Asset requirements

### 6.1 Must-have for Discover v1

| Asset | Spec | Source |
|---|---|---|
| Hero city isometric plate | 2× DPR, wide stage aspect (~16:9 or match Living Map stage), soft sunny lighting, Port Said–class coastal detail | Commission or licensed pack adapted to Funnity palette |
| Parallax layers (optional v1) | Sky / water / mid / foreground | Same artist |
| Category pin icon set | Music, food, gaming, sports, cinema, social, outdoor, default — purple/orange family + accents | Design system |
| Cluster / count badge | Multicolor, legible at small size | Design |
| Glass control kit | Location card, zoom stack, mode toggle “Discover / Precise map” | Extend current CSS tokens |
| Empty / loading / error plates | Branded, non-geographic | Design |
| EN + AR type specimens | Title lockups as in reference | Tajawal + existing Latin stack |

### 6.2 Mapping / tooling assets

| Asset | Purpose |
|---|---|
| Hotspot layout JSON per scene | Bind API entities to art without geo projection lies |
| Editor checklist (internal) | QA: every visible pin traces to API id |
| “No scene for city” fallback art | Abstract Funnity region — still not fake venues |

### 6.3 Do **not** treat as production data

- Mock participant avatars in the PNG  
- Mock “Live Now” unless product has a real live flag  
- Decorative stadium / ships as POIs  

### 6.4 Licensing checklist (before commission)

- Exclusive or app-wide license for web use  
- Right to crop / recolor / add UI overlays  
- No third-party trademarked landmarks unless cleared  
- Source files (PSD/Figma/Blender) retained by Funnity  

---

## 7. Performance targets

Targets for Living Map **preview** on mid-tier mobile (≈2022 Android) and desktop Chromium.

| Metric | Discover mode | Navigate mode |
|---|---|---|
| Time to interactive stage | ≤ 2.5 s on 4G after HTML | ≤ 3.5 s (lazy MapLibre OK) |
| Hero asset weight | ≤ 800 KB initial (responsive srcset); ≤ 1.5 MB full plate | N/A |
| JS heap steady | ≤ 40 MB | ≤ 60 MB with buildings off on mobile |
| Marker/hotspot DOM nodes | ≤ 40 interactive | ≤ 60 HTML markers (existing discipline) |
| Animation | 30–60 fps; pause when offscreen / reduced motion | Same tour pause rules as V3 |
| CLS | No layout jump when spotlight opens | Same |
| Production Home impact | **Zero** until explicit cutover (code-split preview entry only) | Same |

**Strategy:** Discover-first so most users never pay MapLibre + vector tile cost on first paint.

---

## 8. Cost and licensing implications

| Item | Option A | Option B | Option C (rec.) |
|---|---|---|---|
| MapLibre | Free (BSD) | — | Free |
| Vector tiles (OFM) | Free + attribution; fair-use risk at scale | — | Same for Navigate |
| Self-host Protomaps | Infra later | — | Infra later |
| City illustration / 3D kit | — | **Major** (per city or kit) | **Major** for 1–N cities |
| Ongoing art updates | — | Yes | Yes for Discover cities |
| Engineering | Low–med | High | Med–high |
| Risk of visual rejection | **High** (already rejected) | Low if art matches | Low if Discover ships first |

**Budget framing for owner approval:**

1. Art + hotspot design for **one** hero city (match reference quality).  
2. Engineering for hybrid shell + integrity labeling.  
3. Reuse V3.5.x Navigate without new tile spend.  
4. Defer additional cities until hero city accepted.

Paid basemap providers remain **optional** and require separate owner approval.

---

## 9. Implementation phases

**Nothing below starts without owner approval of this proposal.**

### Phase 0 — Approval & assets (gate)

- Owner approves Hybrid (C) or selects A/B with recorded rationale.  
- Commission / lock hero city art + pin set.  
- Confirm integrity copy EN/AR.  
- Confirm preview URL strategy (e.g. `/preview/living-map-v4`) — production untouched.

### Phase 1 — Hybrid shell (no fake geography)

- Preview-only route + Vite entry (mirror V3.5 isolation).  
- Mode switch: Discover | Navigate.  
- Shared selection state wired to existing discovery fetch.  
- Discover placeholder plate + hotspot stubs bound only to real API rows.  
- Navigate = port of current V3.5 map behavior.

### Phase 2 — Cinematic Discover v1

- Final hero art layers, multicolor pins, arcs, spotlight/carousel restyle to reference.  
- Hotspot JSON for hero city.  
- Motion + reduced-motion.  
- Visual QA pack vs reference (layout, not pixel-perfect claim until signed art).

### Phase 3 — Integrity & fallback

- Cities without scenes → Navigate or abstract plate.  
- Empty states, approximate labeling, no invented fills.  
- Marker integrity audit (extend V3.5.5 methodology to hotspots).

### Phase 4 — Performance & a11y

- Meet §7 targets; keyboard List parity; screen reader mode name announcements.  

### Phase 5 — Owner visual acceptance

- Screenshot / video pack.  
- **Stop** — production Home cutover is a separate decision.

### Explicit non-goals this proposal

- No homepage replacement  
- No DB migrations / seed writes for fake events  
- No Flutter work  
- No claiming pixel-perfect match until final commissioned art is in-repo and compared  

---

## 10. Risks and limitations

| Risk | Impact | Mitigation |
|---|---|---|
| Continuing MapLibre-only “polish” | Repeat owner rejection | Hybrid; Discover is the hero |
| Users trust illustrated pins as GPS | Safety / trust harm | Persistent disclaimer; exact only in Navigate |
| Art covers only one city | Weak nationwide story | Fallback to Navigate; roadmap city kits |
| Hotspot binding invents density | Fake discovery | Hide empty hotspots; never fabricate events |
| Reference mock invents social proof | Temptation to copy | API-only participants/images/live flags |
| Dual renderers increase QA surface | Bugs / drift | Shared presenters for cards; one selection store |
| OpenFreeMap fair-use | Navigate degradation | Lazy load; Protomaps plan |
| Parallax / WebGL overreach | Jank on mobile | Prefer 2.5D plate for v1 |
| Maintenance of scene JSON | Stale hotspots | Version `scene_id`; CI check ids resolve |
| Flutter parity debt | Later product gap | Document in `FLUTTER_WEB_PARITY.md` when web member surface ships (out of scope now) |
| Scope creep into production Home | Unapproved cutover | Preview isolation until written owner OK |

### Limitations (honest)

- Option C will **not** make Discover mode geographically precise.  
- Option A alone will **not** match the approved isometric reference.  
- Nationwide illustrated fidelity is a **multi-city content project**, not a CSS sprint.  
- The reference PNG is a design aspiration; some mock details are intentionally non-shippable under Funnity’s data-integrity rules.

---

## Decision request (owner)

Please approve one path before any V4 build:

1. **Recommended:** Option **C — Hybrid** (cinematic Discover + MapLibre Navigate, shared Discovery APIs).  
2. Option **B only** (cinematic scene; geographic map deferred or abandoned).  
3. Option **A only** (accept cartographic direction — **not** recommended given current rejection).  

Also confirm:

- Hero city for first art commission (Port Said–class recommended to match reference).  
- Preview isolation (no Home cutover).  
- Budget band for illustration.

**Stop here until approval.** No V4 implementation, production changes, database writes, homepage replacement, or Flutter work under this proposal.
