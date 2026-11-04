# Mapbox Standard 3D — Provider Trial

**Status:** Token activated · city captures complete · **stopped for owner visual review**  
**Date:** 2026-11-02  
**Preview:** `/preview/mapbox-standard-trial`  

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.mapbox-standard-trial', now()->addDays(7));"
```

**Preserved:** Production Home V3.4 · Living Map V3.5/V4 · Living World V5 · procedural POC  
**Restrictions honored:** No account creation · no billing changes · token not printed in docs/logs · no Flutter · no DB writes · trial not set as default Discover  

Evidence: `docs/discovery/mapbox_trial_qa/`  
Reference inspected: `docs/Explore Around You Events Dashboard.png`

---

## Recommendation

### **CONDITIONAL**

| Lens | Verdict |
|---|---|
| Ready-made 3D (no custom engine) | **Strong fit** — Mapbox GL JS + Standard loads and pans across Egypt without Three.js / procedural meshes. |
| Visual match to approved cinematic reference | **Weak** — Standard is a polished **cartographic** 3D basemap, not an illustrated isometric city diorama (ships, palms, art-directed lighting). |
| Egypt coverage | **Uneven** — Cairo shows usable 3D massing + some landmark presence; Alexandria/Port Said are mostly light streets/POIs with **limited** building extrusion richness. |
| Discovery integrity | **Pass** — real API pins + approximate labels + event cards. |
| Cost / ops | Free tier may cover early traffic; 100k loads ≈ recurring OpEx (see §6). Requires owner billing policy. |

**CONDITIONAL GO for “ready-made geographic 3D provider” experiments** — only if the owner accepts Standard’s look (not the mock’s art style) and uneven Egypt detail.  
**Not a visual GO** against the approved PNG reference.

---

## 1. Activation checklist

| Step | Result |
|---|---|
| `MAPBOX_ACCESS_TOKEN` present | **Yes** (`pk.*`, length recorded only) |
| Wired via `config('services.mapbox.public_token')` | **Yes** |
| Secret `sk.*` rejected for browser | **Yes** (guard remains) |
| Config/view caches cleared | **Yes** |
| Token gate dismissed | **Yes** (`tokenGateVisible: false`) |
| Mapbox HTTP 4xx in capture | **None observed** (tile requests 200) |
| Token printed in this doc | **No** |

---

## 2. Screenshots (real captures)

| File | City / viewport |
|---|---|
| `mapbox_trial_qa/01-cairo-desktop.jpg` / `02-cairo-stage.jpg` | Cairo downtown (Nile / Tahrir area) |
| `mapbox_trial_qa/01-alexandria-desktop.jpg` / `02-alexandria-stage.jpg` | Alexandria coastal core |
| `mapbox_trial_qa/01-port-said-desktop.jpg` / `02-port-said-stage.jpg` | Port Said waterfront (retry after tile idle) |
| `mapbox_trial_qa/03-mobile-cairo.jpg` | Mobile web Cairo |
| `mapbox_trial_qa/network_errors.json` | Diagnostic (no 4xx; token redacted) |
| `mapbox_trial_qa/capture_report.json` | Metrics per city |

**Note:** First Port Said pass was a blank canvas (style loaded, markers visible, tiles not yet painted). Longer `idle` wait fixed it — capture script updated. No Mapbox auth errors detected.

---

## 3. City-by-city visual assessment

### Cairo

- Mapbox Standard loads with pitched 3D view; roads, Nile, parks, and POI labels readable.  
- Building extrusions present in the core; some landmark labeling (e.g. museum area) visible.  
- Discovery pin + “LAN party meetup” card with **approximate** disclaimer — correct.  
- **Best of the three** for 3D massing among tested Egyptian cities.

### Alexandria

- Continuous Standard basemap; Mediterranean edge and street grid clear.  
- **3D building richness appears thinner** than Cairo in the tested frame — more flat/low footprints than cinematic towers.  
- Discovery tennis event card + approximate label — correct.  
- Useful as navigation/discovery chrome; not mock-level architecture.

### Port Said

- After proper tile wait: streets, water, POIs (stadium/hotel labels), Discovery PlayStation event card.  
- **Little high-detail 3D architecture** vs the illustrated reference of Port Said.  
- First-pass blank frame is a timing/QA hazard, not proof of zero coverage.

---

## 4. Comparison to approved reference

| Reference trait | Mapbox Standard trial | Gap |
|---|---|---|
| Illustrated isometric city art | Cartographic Standard 3D | **Large** |
| Ships / palms / stadium kits | Not present as art assets | **Large** |
| Soft cinematic baked lighting | Dynamic Standard lighting | Different language |
| Glowing category event pins | Funnity HTML markers on top | Close enough (product chrome) |
| Floating event cards | Implemented | Close |
| Continuous pan/zoom Egypt | **Yes** | Match requirement |
| No custom 3D engine | **Yes** | Match requirement |

**Bottom line:** Mapbox Standard solves the **engine/maintenance** problem. It does **not** reproduce the **approved concept-art look**.

---

## 5. Discovery + performance

Measured in headless Chromium (`capture_report.json` / live HUD). Values vary by machine; treat as trial evidence, not production SLOs.

| City | Event markers (API) | Notes |
|---|---:|---|
| Cairo | 10 | Spotlight card from live Discovery |
| Alexandria | 5 | Approximate labels preserved |
| Port Said | 4 | Approximate labels preserved |

| Metric | Observed (trial) |
|---|---|
| Initial map idle (Cairo first load) | ~7–8.5 s in capture environment |
| Mobile Cairo idle (HUD) | ~4.6 s |
| FPS samples | Roughly teens→~45–50 after settle; mobile HUD showed ~15 during sample window |
| JS heap | ~88–125 MB (`performance.memory`) |
| Mapbox API errors | **0** 4xx in Port Said tile probe (117× HTTP 200) |

Mobile layout: city switcher + map stage + metrics stack correctly; event card overlays map.

---

## 6. Cost reminder (unchanged model; verify live)

| Monthly map loads | Est. GL JS cost* |
|---|---:|
| 10,000 | $0 (inside common free tier w/ payment method) |
| 50,000 | $0 (at common free ceiling) |
| 100,000 | ~$250 (50k free + 50k × ~$5/1k) |

\*Published-band estimate — confirm in Mapbox console before production. Card-free trial caps are much lower (~5k). **No billing was enabled by this trial.**

---

## 7. Honest product implications

1. **If the owner wants the PNG look:** Mapbox Standard alone is insufficient; would still need art/hybrid (previously rejected for continuity) or accept a different brand visual.  
2. **If the owner wants ready-made continuous 3D without maintaining Three.js:** Mapbox Standard is the strongest evaluated option.  
3. **Egypt detail is city-dependent:** do not market “cinematic landmarks everywhere.”  
4. **Production cutover** still needs: token URL restrictions, billing policy, Home gate approval, Flutter plan (out of scope here).

---

## 8. Owner visual review asks

Please review the screenshots in `docs/discovery/mapbox_trial_qa/` and choose:

1. **Accept CONDITIONAL** — keep Mapbox Standard as the Living World provider candidate (preview-only until cutover approval).  
2. **Reject visually** — stay on OpenFreeMap / V5 path or revisit art direction.  
3. **Request more framing** — e.g. specific districts, night `lightPreset`, or closer zooms before deciding.

---

## Stop

Trial remains **preview-only**. Production Home / Discover defaults unchanged. Awaiting owner visual decision.
