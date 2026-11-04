# Living Map V3.5 — Web-only visual prototype

**Status:** Isolated preview only. **Production Home / Dashboard / Discover remain on V3.4.**  
**Flutter:** Not in scope for this phase.

## Preview URL

Authenticated, signed, or `?preview_token=` (same gate as V3):

- `/preview/living-map-v35`
- Signed example: `php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"`

## What is isolated

| Surface | Version |
|---|---|
| `/preview/living-map-v35` | **V3.5** (`variant=v35`) |
| Public `/`, `/dashboard` | V3.4 (unchanged) |
| `/events` Discover map | V3.4 / Discover mode (unchanged) |
| `/preview/living-map-v3` | V3.3/V3.4 preview chrome |

## Visual upgrades (preview)

- Stronger lavender/blue OpenFreeMap remap (`funnity-style-v35.js`)
- Higher cinematic pitch + fog
- Larger glowing category pins + pulse halos
- Circular glowing city/cluster badges (not white OSM-like chips)
- Larger glass location panel + distance chips
- Larger floating preview + horizontal carousel cards
- Tajawal on Arabic preview shell

## Evidence

`docs/discovery/v3_5_qa/`

## Owner gate

Do **not** set Home to V3.5 until the owner explicitly approves screenshots.
