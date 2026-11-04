# Living Map V3.4 — Premium Interactive Living Map

**Status:** Implemented on web Home/Dashboard/Preview when `LIVING_MAP_REPLACE_HOME=true`. Visual approval still required from owner screenshots.  
**Reference (visual target, not a tile source):** `docs/Explore Around You Events Dashboard.png`  
**Evidence:** `docs/discovery/v3_4_qa/`

## What changed vs V3.3

| Area | V3.3 | V3.4 |
|---|---|---|
| Layout | Side-by-side map + vertical list | Map-first full width + horizontal event carousel |
| View modes | None | Map / List / Nearby |
| Location chrome | Toolbar radius select + Change city | Glass location panel (city, Use my location, distance chips) |
| Map HUD | Tour + zoom + reset | Recenter + zoom + tour + reset |
| Event cards | Compact side rows | Horizontal media cards with category badge, distance (when known), going count |
| Discover | Unchanged contract | Still map-only (`show-list=false`) |

## Map technology

- **Engine:** MapLibre GL JS 4.7.1
- **Tiles / style base:** OpenFreeMap Liberty (`https://tiles.openfreemap.org/styles/liberty`)
- **Brand palette:** `resources/js/living-map/funnity-style.js` remaps paint (lavender land, soft blue water, decorative `building-3d` extrusion where tile height exists)
- **3D promise:** Soft pitch + extrusion where OpenMapTiles height is present — **not** true isometric city mesh. Reference image lighting/landmarks are inspirational only.

## API integration (unchanged SSOT)

| Endpoint | Use |
|---|---|
| `GET /api/discovery/map` | Markers, clusters, city aggregates (Explore V4) |
| `GET /api/discovery/city-events` | City collection expand |
| `GET /api/places/reverse` | Label after “Use my location” |
| `PUT /settings/location` | Persist radius for authenticated city users |
| `funnity:discovery-location` | Cross-component origin sync |

Distance labels on cards/floating preview are computed client-side with haversine **only when** an origin exists and the event has coordinates. No invented attendees, live badges, or fake pins.

## Preview location

- Public home: `https://funnity.com/` (guest) and authenticated `/` + `/dashboard`
- Design preview: `https://funnity.com/preview/living-map-v3` (auth / signed / preview token)
- Arabic SEO home: `https://funnity.com/ar`

## Rollback

```bash
LIVING_MAP_REPLACE_HOME=false
php artisan config:clear
```

Hard-refresh Home → Leaflet mini map returns.

## Flutter

Web layout is now map + carousel (not side list). Flutter Home map port remains pending — update parity checklist §10; do not invent a divergent API shape.
