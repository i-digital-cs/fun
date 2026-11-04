# Living Map V3.5.4 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (not claimed approved)  
**Production homepage:** **UNCHANGED** (V3.4 — no `lm-shell--v35` on `/`)

## Preview URL

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

Example:

`https://funnity.com/preview/living-map-v35?expires=1794157615&signature=41fabc614f5f21a00e2157cf3dc76b60ef2b8fdc2149cafd20ae3d8bed60a39f`

---

## Root cause — marker row along top-left / filter overlap

### Diagnosis

MapLibre positions markers by setting **inline `transform`** on the marker root (`.maplibregl-marker` / `.lm-marker`), e.g.:

`translate(-50%, -100%) translate(684px, 329px)`

V3.5.3 CSS (and shared `stage.blade.php` styles) applied:

```css
.lm-marker.is-float { animation: lmFloat … } /* keyframes animate transform */
.lm-marker.is-selected { transform: translateY(-8px) scale(1.12); }
```

CSS animations **override inline `transform`**. Computed style collapsed to roughly `translateY(-6px)`, so every pin rendered at the map container’s default origin (top-start) — forming an artificial row that overlapped the location filter.

**Not** caused by fake coordinates, clustering, or viewport clipping. Geographic `setLngLat` values were correct; the screen projection was destroyed by CSS.

### Before / after (instrumented)

| | V3.5.3 (broken) | V3.5.4 (fixed) |
|---|---|---|
| Root `animation` | `lmFloat` | `none` |
| Computed transform | `matrix(…, 0, -6)` | `matrix(…, 656, 257)` matches MapLibre px |
| Screen vs filter | Overlapped loc panel (`x≈105`) | No overlap (`x≈761`) |
| Artificial edge row | Yes | **No** |

Screenshots: `00-before-v353-desktop.jpg` / `before-marker-row.jpg` → `01-desktop-initial.jpg`, `08-markers-stage.jpg`

### Exact fix

1. **Never** animate or set `transform` on the MapLibre marker root.
2. Wrap pin chrome in `.lm-marker__visual`; float / hover / selected transforms apply only there.
3. Update shared `stage.blade.php` inline CSS the same way (affects V3.4 chrome safely with the wrapper).
4. Keep aggregate = one Port Said teardrop with count **4**; no invented coordinates.

---

## Viewport (Egypt-first)

- Lowered V3.5 pitch (~36°) so land/events dominate vs empty Mediterranean.
- Tighter `fitBounds` radius (~0.82× selected km) with asymmetric padding (room for loc filter).
- Does **not** lock exploration to Cairo; pan/zoom unrestricted; distant events remain loadable via discovery.

---

## Location filter polish

- Smaller panel, GPS button, icon, and padding.
- Distance pill retained (`within 25 km`).
- Slider labels: `5 / 10 / 25 / 50 / 100 / All` (no truncated “All distances”).
- Higher z-index; mobile compact rules.
- Behavior unchanged (slider, city, GPS).

---

## Screenshots

| File | Case |
|---|---|
| `00-before-v353-desktop.jpg` | Before (V3.5.3 desktop) |
| `01-desktop-initial.jpg` | Desktop initial viewport |
| `02-desktop-event-selected.jpg` | Desktop event selected |
| `02b-floating-card.jpg` | Floating card close-up |
| `03-desktop-location-filter.jpg` | Location filter |
| `04-mobile-initial.jpg` | Mobile initial |
| `05-mobile-event-selected.jpg` | Mobile selected |
| `06-desktop-ar-rtl.jpg` | Arabic RTL desktop |
| `07-port-said-aggregate.jpg` | Port Said aggregate **4** |
| `08-markers-stage.jpg` | Markers on stage |

---

## Geographic validation

- Port Said aggregate: API `city_id=11`, count `4`, real lat/lng via MapLibre `setLngLat`.
- After fix: inline px ≈ computed matrix translation; pin sits on Port Said, not the viewport edge.
- **Confirmed: no artificial marker row along viewport edges.**

## Functional tests

| Test | Result |
|---|---|
| Aggregate click → city list → card | Pass |
| Floating card real data | Pass (no invented live/avatars) |
| Slider / distance readout | Pass |
| Desktop EN / AR RTL | Pass |
| Mobile EN | Pass |
| Homepage isolation | Pass (`homeOk`) |

## Files changed

- `resources/views/components/living-map/stage.blade.php` — stop root transform animation; short “All” label
- `resources/css/living-map-v35.css` — visual-wrapper transforms; filter polish
- `resources/js/living-map/create-living-map.js` — `wrapMarkerVisual`; Egypt-first framing
- `resources/views/preview/living-map-v35.blade.php` — V3.5.4 banner

## Remaining limitations

- Coastal cities (Port Said) still show some sea by geography — framing improved, not sea-free.
- Headless JPEG may soften the digit “4”; live DOM count is `4`.
- Exact-coordinate category teardrops appear when data has exact venues; Port Said demo is aggregate-led.

**STOP FOR OWNER VISUAL APPROVAL. Do not deploy to homepage.**
