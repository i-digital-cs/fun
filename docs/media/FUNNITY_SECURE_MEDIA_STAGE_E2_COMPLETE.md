# Stage E2 — Complete (two-file controlled migration)

**Date:** 2026-11-01  
**Lightbox gate:** PASS (owner manual verification)  
**Scope executed:** exactly two DB-referenced local public assets → R2  
**Not done:** local deletion, bulk migration, Flutter, chat auth changes

Artifacts under `storage/app/migrations/e2/`:
- `backup_2026-11-01_pre_r2.json`
- `copy_verify_2026-11-01.json`
- `db_flip_2026-11-01.json`

---

## Exact files migrated

| Asset | Object key | Bytes | MD5 |
|---|---|---|---|
| Event 501 cover | `events/501/cover_7b74a7dd-c885-4577-b46b-1e213e3afeeb.png` | 76466 | `9b6ff858224ed7f69123c9b488c85720` |
| Branding logo | `branding/funity-mark.svg` | 447 | `57314c89fb480e2ebb621e92c9483872` |

No conflicting R2 objects existed before copy. Paths unchanged; only disk columns updated.

---

## Database references before / after

| Record | Field | Before | After |
|---|---|---|---|
| `events.id=501` | `image_disk` | `media` | **`r2`** |
| `events.id=501` | `image_path` | `events/501/cover_7b74a7dd-….png` | unchanged |
| `site_settings.id=1` | `logo_disk` | `media` | **`r2`** |
| `site_settings.id=1` | `logo_path` | `branding/funity-mark.svg` | unchanged |

---

## R2 verification

| Check | Result |
|---|---|
| Put to public R2 disk | PASS |
| Exists after put | PASS |
| Size match | PASS (76466 / 447) |
| Content MD5 match (local ↔ R2 ↔ public HTTP body) | PASS |
| Public HTTP | **200** `image/png` / **200** `image/svg+xml` |
| App URL resolution | R2 public host + same object paths |

---

## Production image-loading

- Event 501 `imageUrl()` resolves to R2 public URL; HTTP **200**, MD5 match.
- Site logo resolves to R2 public URL; HTTP **200**, MD5 match.
- Local originals **still present** on `media` disk (unchanged).

---

## Remaining local inventory (read-only)

`php artisan media:inventory`:

- Event image disks: **r2: 54** (was 53+1 local) — `event_covers_non_r2: 0`
- Local `media` files still on disk: **67** (duplicates/orphans; not deleted)
- Local `media_private` orphans: **3**
- Identity: 18 on `r2_private`

---

## Rollback readiness

1. Restore DB disks from backup:
   - `events.id=501` → `image_disk=media`
   - `site_settings.id=1` → `logo_disk=media`
2. Local files remain at the same paths on the `media` disk.
3. R2 objects can be left in place (harmless) or removed later with separate approval.

Backup: `storage/app/migrations/e2/backup_2026-11-01_pre_r2.json`

---

## Failures / warnings

- None during copy/verify/flip.
- Inventory command still prints a generic Stage E approval banner (static text); E2 two-file work is done.
- Local leftovers remain until a separate E3 deletion authorization.
