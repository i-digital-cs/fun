# Funnity Secure Media System & Cloudflare R2

**Date:** 2026-11-01  
**Scope:** Web UI lightbox + R2 architecture / access control / inventory. **No Flutter code changes or publish.** Mobile API gateway URLs remain compatible.  
**Production DB audited (read-only):** `d14098_fun`  
**Owner gates:** No destructive migration, no local original deletion, no bulk reference rewrite without explicit approval.

---

## 1. Media inventory (production snapshot)

| Category | Count / disk | DB reference | Classification | Delivery |
|---|---|---|---|---|
| Profile photos | 6 with `profile_image_path` (mix of R2 public URL + Google avatars) | `users.profile_image_path` | Public (or third-party) | Direct public URL |
| Event covers | 54 with image; **r2=53**, **media(local)=1**, null disk rows without image | `events.image_path` + `image_disk` | Public when event is published/listable | Public disk / R2 URL helpers |
| Identity documents | **18 on `r2_private`** | `identity_documents.disk` + `path` | **Private** | Admin/owner stream via `PrivateMediaAccessService` |
| DM attachments | **20** (image=4, voice=16) | `direct_messages.media_*` | **Private** | Auth gateway `/dms/media/{id}` + `/api/dms/media/{id}` |
| Event-chat attachments | **4** (image=1, voice=3) | `event_messages.media_*` | **Private** | Auth gateway `/events/{event}/messages/{message}/media` |
| Community images | Not present as a separate upload surface | — | — | N/A |
| Event gallery | Not a separate table; covers only today | — | — | N/A |

**Configured disks (production):** `MEDIA_DISK=r2`, `MEDIA_PRIVATE_DISK=r2_private` (credentials via env; never logged).

**Object key samples (existing):**

- Chat: `chat/{userId}/{kind}/{uuid}.ext` (legacy) → **new uploads** use `chat/direct/{conversationId}/{uuid}` / `chat/events/{eventId}/{uuid}`
- Identity: `identity/{userId}/{type}_{uuid}.ext`
- Event covers: `events/{id}/cover_{uuid}.webp` (+ `_640` card sibling)
- Profiles (new): `profiles/{userId}/{uuid}.ext` (legacy `avatars/avatar_*.ext` still valid)

**Legacy migration candidates:**

- 1 event cover still on local `media` disk
- Some manual event keys under `events/manual/` with original filenames (non-UUID)
- Profile photos still using `avatars/` prefix on public R2
- Chat media already on private disk / R2 private path prefix `chat/`

---

## 2. Current security posture & residual risks

### Strengths already in place

- Private chat/identity media use **private disks** (`r2_private` / `media_private`).
- Chat streams authorize via **conversation participation** / **event chat policy**, then stream bytes with `Cache-Control: private, no-store` — **not** long-lived R2 bearer URLs in payloads.
- API `media_url` values are application gateways (`/api/dms/media/{id}`), preserving Flutter clients.
- Upload allowlists for chat voice/image; executables blocked.

### Gaps addressed in this task

- Web chat opened images in a **new browser tab** (`target="_blank"`) → replaced with **in-page premium media viewer**.
- Centralized `MediaUploadValidator` (finfo MIME, category policies, SVG/HTML/exe blocks).
- Predictable scoped object keys for new chat uploads.
- Inventory artisan command (`media:inventory`) with execute path **hard-refused**.

### Residual risks (owner awareness)

- An **authorized** user can still screenshot / save what their browser can render (cannot be prevented honestly).
- Public event covers and profile photos remain **public CDN/R2 URLs** by design; private events must continue to avoid leaking restricted covers via public listing (product rule, not object ACL alone).
- One local `media` event cover + `events/manual/*` names need Stage E migration after approval.
- Malware scanning / quarantine not yet wired (recommend ClamAV or Cloudflare scanning later).

---

## 3. R2 architecture

```text
Public disk  (MEDIA_DISK=r2)           Private disk (MEDIA_PRIVATE_DISK=r2_private)
─────────────────────────────────      ────────────────────────────────────────────
profiles/{userId}/{uuid}.ext           chat/direct/{conversationId}/{uuid}.ext
events/{eventId}/cover_{uuid}.webp     chat/events/{eventId}/{uuid}.ext
events/{eventId}/cover_{uuid}_640.webp identity/{userId}/{type}_{uuid}.ext
site branding (admin)                  legacy chat/{userId}/{kind}/{uuid}.ext
```

- Laravel Flysystem S3 driver → Cloudflare R2 endpoint.
- Credentials only in env (`R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, buckets, endpoint).
- Private bucket must **not** share a public CDN URL for sensitive objects.
- Browsers never receive R2 signing keys.

---

## 4. Public / private access policy

| Media | Auth | Mechanism |
|---|---|---|
| Published event cover | Optional / public product rules | Public disk URL |
| Profile photo | Public profile rules | Public disk URL (or Google) |
| DM image/voice/video/file | Participant only | Session or Sanctum → stream |
| Event-chat media | Event chat authorized members | Session or Sanctum → stream |
| Identity documents | Owner (limited) / admin moderation | `PrivateMediaAccessService` stream |
| Copied private gateway URL by outsider | **Denied** | 403 |
| Logged-out private gateway | **Denied** | 401/redirect |

**Copied-link property:** Gateway re-checks auth on every request. Short-lived R2 presigned URLs are **not** used for chat/identity HTML/API payloads.

---

## 5. Web media viewer

- JS: `resources/js/media-viewer.js` (bundled via `app.js`, `dm-chat.js`, `event-chat.js`)
- CSS: `.fun-media-viewer*` in `resources/css/app.css`
- Markup: `[data-media-viewer][data-media-src][data-media-type]`
- Features: overlay, Esc / backdrop close, zoom, fit, pinch, gallery next/prev, video controls (pause on close), RTL arrow awareness
- Chat Blade + realtime DOM builders no longer use `target="_blank"` for images/videos

**Build:** `npm run build` completed in this task (`public/build/assets/media-viewer-*.js` + rebuilt `dm-chat` / `event-chat` / `app` CSS).

**Screenshots:** Require an authenticated DM/event-chat session with an image attachment (private gateway). Capture after deploy by opening a chat image — expect overlay, not a new tab. Not captured here to avoid production account login automation.

---

## 6. Upload validation

`App\Services\Media\MediaUploadValidator` + `config/media.php` categories:

- Server MIME via `finfo` when available
- Category max bytes / allowlists / blocked executables & active content (SVG/HTML)
- Chat video category added (`media_kind=video`, up to 50MB request ceiling; service enforces policy)
- Identity uploads validated before store
- Profile photos validated + stored under `profiles/{userId}/`

---

## 7. Security tests

`tests/Feature/SecureMediaAccessTest.php` covers:

1. Authorized DM image view  
2. Copied URL outsider denied  
3. Logged-out denied  
4. Web session gateway parity  
5. Event-chat membership gate  
6. SVG/HTML rejection  
7. Oversized rejection  
8. Traversal-safe stored keys  
9. Missing object 404  
10. Cleared path 404  

Also retain `ChatV2ExtensionsTest` voice / executable coverage.

---

## 8. Migration dry-run

```bash
php artisan media:inventory
php artisan media:inventory --json
php artisan media:inventory --execute   # intentionally refuses
```

**Do not run Stage E copy/rewrite/delete without:**

1. Verified backup  
2. Written owner approval  
3. Checksum verification plan  
4. Rollback instructions  

Local originals must remain until a **separate** deletion authorization.

---

## 9. Production rollout plan

| Stage | Status | Action |
|---|---|---|
| A Audit | Done | Inventory + this document |
| B Secure R2 + AC | Done (existing + hardened) | Validator, scoped keys, cache headers |
| C Web viewer | Done (code) | Deploy + `npm run build` |
| D Migration tools | Done (dry-run only) | `media:inventory` |
| E Controlled rollout | **Blocked on owner** | Copy remaining local → R2, verify, keep locals |

---

## 10. Owner approval checklist

- [ ] Approve production asset build/deploy for web lightbox  
- [ ] Approve Stage E migration of remaining local event cover(s) / manual keys  
- [ ] Approve later deletion of local originals (separate ticket)  
- [ ] Optional: malware scanning workflow  
- [ ] Flutter: no change required for gateway auth; native viewers remain as-is (web-only lightbox task)
