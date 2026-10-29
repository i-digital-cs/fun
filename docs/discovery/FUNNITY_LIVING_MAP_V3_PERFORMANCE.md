# Living Map V3.2 — Performance Notes

## Measured (headless Chromium, preview)

Source: `docs/discovery/v3_2_qa/perf-sample.json` + Cairo extrusion query.

| Metric | Result | Status |
|---|---|---|
| RTL plugin | `loaded` before interaction | VERIFIED |
| HTML markers (pins + collections) | ~12 | VERIFIED |
| Exact pins in featured frame | often 0–2 (data-dependent) | VERIFIED |
| City collections | ~11 | VERIFIED |
| JS heap mid city mode | ~20 MB | VERIFIED |
| `building-3d` @ Cairo z14.2 | 158 | VERIFIED |
| Vite chunk | preview entry only | VERIFIED |

## Guardrails

- RTL plugin starts in Blade before Vite module; module awaits `loaded`.
- Tour pauses offscreen / hidden tab / user pan / reduced motion.
- City panel fetches abortable; strip reuses shared `selectEvent`.
- Collections capped (named + few clusters) to limit HTML marker cost.

## Bundle impact on production Home

**None** — `app.js` does not import `living-map-v3.js`.

## Remaining before production rollout

- OpenFreeMap capacity or self-host.
- Home Web Vitals after any future integration.
- Flutter mid-tier FPS.
- Optional screen recording of tour easing.
