# Funnity Explore V4 — Map Performance Report

## Mitigations shipped

| Risk | Mitigation |
|------|------------|
| Loading all Egypt markers | BBox + max 250 points; reject oversized boxes |
| Heavy cluster payloads | Grid clusters without full event bodies |
| Flutter rebuild storms | Map controller isolated Riverpod provider; Search-this-area explicit |
| Web bundle weight | Lazy `import('./discover-map')` + CDN Leaflet |
| Image decode pressure | Map markers are icons only; card images stay on list/preview open |
| Stale responses | Request id / AbortController cancel |

## Explore V4 FINAL mitigations (additive)

| Risk | Mitigation |
|------|------------|
| City-only pin flood | Separate `city_aggregates` (not per-event approximate by default) |
| Nationwide low-zoom 422 | Zoom-aware bbox caps (wider at zoom ≤ 7) |
| Reverse-geocode storms | One-shot after Detect; not on pan/scroll |
| Web map re-init | Single lazy Leaflet init + `dataset.ready` |
| Flutter marker rebuilds | Isolated `discoverMapControllerProvider`; Search this area explicit |

## Measurements

| Metric | Value |
|--------|-------|
| UI frame time | NOT MEASURED |
| Dropped frames | NOT MEASURED |
| Memory | NOT MEASURED |
| Map init time | Web: Leaflet ready in isolated verify (~2–4s incl. tiles); NOT Profile Mode |
| Cluster API time | Feature tests only |
| Network request count (pan) | Designed: 0 until “Search this area” |
| DEVICE VERIFICATION | NOT VERIFIED |

Do not treat CODE mitigations as a DEVICE PASS.

## Verdict

**PERFORMANCE: CONDITIONAL** — safeguards landed; Flutter Profile Mode + hardware still required.
