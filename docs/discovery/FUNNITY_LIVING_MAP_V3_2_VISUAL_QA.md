# Living Map V3.2 — Visual QA

**Verdict:** READY FOR OWNER VISUAL REVIEW (preview only).

## P0 Arabic root cause

MapLibre GL JS **4.7.1** exposes `setRTLTextPlugin(url, lazy)` (2 args → Promise).

V3.1 called the older 3-arg form `(url, callback, deferred)`. The **callback function was treated as truthy `lazy`**, so the plugin stayed on `requested` and **never shaped Arabic**. Vector labels painted letter-reversed.

### Exact fix

1. Blade preloads: `maplibregl.setRTLTextPlugin(rtlUrl, false)` before the Vite module.
2. Module `ensureRtlTextPlugin()` awaits `setRTLTextPlugin(url, false)`, polls until `loaded`, retries once if stuck.
3. If the plugin finishes after first paint, `map.setStyle(map.getStyle())` reshapes glyphs.
4. HTML UI uses `dir="auto"` + `unicode-bidi: isolate` — **no string reversing**.

### Verified labels (plugin status `loaded`)

القاهرة · الإسكندرية · بورسعيد · الزقازيق · السويس · دمنهور · العلمين

## P0 Event-discovery root cause

V3.1 painted dominant purple aggregate circles and a city-only tour. Strip mixed cities as primary cards.

### V3.2 event-first

| Layer | Treatment |
|---|---|
| Primary | Exact venue teardrop pins + event strip cards |
| Secondary | Floating event preview + category theming |
| Tertiary | Compact white **city collection** chips (`City · N events · Open`) |

Selecting a collection calls `GET /api/discovery/city-events`, opens an in-map panel of **individual events**, syncs the strip, and opens the first event preview. Approximate locations stay labeled. No fabricated coordinates.

## Screenshots (`docs/discovery/v3_2_qa/`)

| File | State |
|---|---|
| `01-egypt-discovery-en.jpg` | Egypt Discovery overview |
| `02-city-collection-expanded.jpg` | City panel + 6 selectable events |
| `03-living-city-port-said.jpg` | Living City framing |
| `04-living-city-cairo-3d.jpg` | Cairo extrusions (158 features) |
| `05-floating-event-preview.jpg` | Event preview card |
| `06-motion-1..3.jpg` | Event Discovery Tour frames |
| `07-arabic-desktop.jpg` | Arabic RTL + shaped map labels |
| `08-arabic-city-collection.jpg` | Arabic city panel / event title |
| `09-arabic-mobile-390.jpg` | Arabic 390px |

## Motion

Timed tour frames captured. Smooth frame-by-frame video: **CONDITIONAL**.

## Performance (`perf-sample.json`)

| Metric | Value |
|---|---|
| RTL plugin | `loaded` |
| Pins / collections | 1 / 11 |
| Heap | ~20 MB |
| Buildings @ Cairo z14 | 158 |

## Production safety

Home Mini Map + Discover Leaflet unchanged. Preview only: `/preview/living-map-v3`. Flutter rollout blocked on owner approval.
