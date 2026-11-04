# Living Map V3.5 — Visual QA (prototype only)

**Verdict for owner:** READY FOR OWNER VISUAL REVIEW  
**Production homepage:** **NOT replaced** (still V3.4)

## Preview URL

Signed (7-day example generated at capture time; regenerate if expired):

`/preview/living-map-v35` — auth, signed URL, or `preview_token`

Generate:

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v35', now()->addDays(7));"
```

## Screenshots

| File | Case |
|---|---|
| `01-en-desktop-1440.jpg` | EN desktop 1440 |
| `02-en-desktop-1920.jpg` | EN desktop 1920 |
| `03-en-mobile-390.jpg` | EN mobile 390 |
| `04-ar-desktop-1440.jpg` | AR RTL desktop |
| `05-ar-mobile-390.jpg` | AR RTL mobile |
| `06-en-desktop-selected.jpg` | Selected event + floating preview |

## Comparison vs approved reference

| Reference intent | V3.5 prototype | Gap |
|---|---|---|
| Immersive Port Said framing | Real Port Said centroid + 25 km radius | OK (real geo) |
| Soft blue/lavender atmosphere | Stronger V3.5 palette + fog + stage glow | Closer than V3.4; not painted sunset illustration |
| True isometric 3D city | MapLibre pitch + building extrusion where tiles allow | **Cannot match AI isometric mesh** without paid 3D tiles |
| Large glowing category pins | Larger teardrop + halo; city/cluster as glowing circles | Fewer exact-venue pins in Port Said seed than mock |
| Glass location panel | Premium glass panel + distance chips | OK |
| Map / List / Nearby | Present | OK |
| Large horizontal cards | Larger media + badges + going count | No fake Live Now / avatar stacks |
| Floating preview | Large glass card on select | OK |

## Technical limitations

- OpenFreeMap / OpenMapTiles heights ≠ cinematic isometric buildings in the reference PNG.
- Event density in Port Said depends on real published events (no fake pins).
- MapLibre attribution required.
- Web-only; Flutter untouched.

## Confirmation

- Homepage `/` does **not** include `lm-shell--v35` / `data-variant="v35"`.
- V3.5 activates only on `/preview/living-map-v35` via `variant="v35"`.
