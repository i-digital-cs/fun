# Living Map V3 — Visual Spec

Primary reference: `docs/Explore Around You Events Dashboard.png`

## Brand tokens

| Token | Value |
|---|---|
| Primary purple | `#7138F5` |
| CTA orange | `#FF762E` |
| Canvas | `#F8F9FE` |
| Soft lavender / peach / sky | Used in marker soft fills & card gradients |

## Basemap

- Soft pastel land, vivid water (vector style liberty as base).
- Pitch ~38–50°, light bearing for cinematic depth.
- Fog / horizon blend when style supports it.
- **Not** isometric 3D ships/palm props from the artboard — those are illustration. Geographic truth remains OSM-derived vector data.

## Markers

| Kind | Treatment |
|---|---|
| Exact event | Teardrop pin, category color + emoji, white rim, soft ground glow |
| Selected / tour | Scale up + gentle float (disabled under `prefers-reduced-motion`) |
| City aggregate | Purple pill with count + outer glow ring |
| Cluster (API) | Treated as exact pin when precision exact |

### Category colors (examples)

- Sports / Football / Tennis → orange–green family  
- Gaming / PlayStation → blue  
- Cinema → coral/rose  
- Social → pink  
- Outdoor → mint  
- Default → Funnity purple  

## Cards

Floating white card: media (or emoji fallback), category chip, title, meta (time · venue · precision · members), View Event CTA. Anchored near projected marker; clamped to stage bounds.

## Motion language

- Ease-in-out cubic camera moves (2–2.5s).
- Temporary curved GeoJSON arc between tour hops (visual only — not a route).
- Auto-tour pauses on drag/zoom/click/hidden tab/offscreen/`prefers-reduced-motion`.
- Play/Pause control in HUD.

## What the reference shows that we intentionally do not fake

- 3D cargo ships, palm trees as mesh props.
- Invented “Live Now” badges without live data.
- Fabricated distances or attendee faces.
- City-centroid treated as exact venue pins.

## Layout

Keep Funnity sidebar + widgets. Map lives in a rounded stage (~340–560px tall on preview), not a new full-page chrome.
