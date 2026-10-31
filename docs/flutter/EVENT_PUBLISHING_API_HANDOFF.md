# Flutter handoff — direct event publishing (OPTION A)

**Date:** 2026-10-31  
**Audience:** Cursor / Flutter on Windows  
**Backend status:** Implemented on `main` (web + API). Flutter UI changes are **not** deployed from this server.

---

## Business rule (approved)

| Action | Intent | Final `status` |
|--------|--------|----------------|
| **Create event / Publish** | `publish_intent=true` | `published` |
| **Save draft** | `publish_intent=false` or omitted | `draft` |

Do **not** silently save a draft when the user chooses the final Create/Publish action.

Existing drafts (including production event **605**) are **not** bulk-published.

---

## API contracts

### Create (draft or publish)

- **Endpoint:** `POST /api/events`
- **Auth:** Bearer Sanctum + `canParticipate`
- **Content-Type:** `multipart/form-data` (existing) or JSON without image

**Existing fields unchanged** (title, description, category_id, city_id, venue, lat/lng, maps_url, starts_at, ends_at, max_participants, sex, requirements, expected_cost, cost_per_person, image).

**New field:**

| Field | Type | Default | Notes |
|-------|------|---------|-------|
| `publish_intent` | boolean | `false` | When `true`, server creates as draft then runs the same `TransitionEventStatusService::publish` path (active category/city required). |

#### Success `201` — draft

```json
{
  "data": { "id": 123, "status": "draft", "...": "..." },
  "message": "Draft saved successfully",
  "published": false,
  "url": "https://funnity.com/events/123/slug"
}
```

#### Success `201` — published

```json
{
  "data": { "id": 123, "status": "published", "...": "..." },
  "message": "Event published successfully",
  "published": true,
  "url": "https://funnity.com/events/123/slug"
}
```

If published with empty description, response may include:

```json
{ "seo_warning": true, "message": "Event published. Tip: add a short description..." }
```

#### Errors `422`

- Validation failures (existing shape)
- Domain publish failures (inactive category/city, etc.) — event may already exist as draft if create succeeded and publish failed; prefer transactional UX: show error and offer Continue editing / Retry publish via `POST /api/events/{id}/publish`

**Note:** Current server implementation publishes after create in the same request; if publish throws, the draft may remain (create already committed). Flutter should:

1. On `422` after `publish_intent=true`, call `GET /api/events/{id}` or My Events and surface Continue editing.
2. Or retry `POST /api/events/{id}/publish`.

### Save draft only

Same `POST /api/events` with `publish_intent=0` / omitted / `false`.

### Explicit publish of an existing draft

- **Endpoint:** `POST /api/events/{id}/publish` (unchanged)
- Still required for drafts created earlier or when create-time publish failed.

---

## Event status values

`draft` | `published` | `cancelled` | `completed`

Discover eligibility: **published** (+ date/geo filters). Drafts appear in My Events only.

---

## URLs

- Public web: `/events/{id}/{slug}` (EN canonical) and `/ar/events/{id}/{slug}` (AR)
- API does not require locale prefixes
- Response `url` is the EN canonical show URL

---

## Required Flutter UI changes

1. Create form: two explicit actions — **Create event** (`publish_intent=true`) and **Save draft** (`publish_intent=false`).
2. Success copy must match backend state:
   - Published → “Event published successfully” / AR `تم نشر الفعالية بنجاح` + **View event**
   - Draft → “Draft saved successfully” / AR `تم حفظ المسودة بنجاح` + **Continue editing**
3. Prefer sending intent on create over relying only on the dismissible post-create publish dialog (dialog may remain as a safety net for older drafts).
4. After successful publish: call Discover `markStale()` / refresh (already wired when using publish endpoint).
5. Do not invent English titles/slugs client-side.

---

## Localization

- Event **content** remains a single title/description (no bilingual event fields required for this handoff).
- Web SEO locale prefixes are web-only; Flutter continues using API + app locale.

---

## Backward compatibility

- Omitting `publish_intent` → draft (previous behavior).
- `POST …/publish` remains valid.
- Older app versions that create then optionally publish still work.

---

## Regression tests (Flutter)

1. Create with `publish_intent=true` → status published; Discover shows event when filters match.
2. Create with `publish_intent=false` → draft; Discover excludes.
3. Publish failure surfaces error; draft not claimed published.
4. EN/AR success strings.
5. Discover refresh after publish.
6. Existing draft edit → publish endpoint still works.

---

## Related docs

- `docs/backend/ADMIN_DRAFT_PUBLISHING_MULTILINGUAL_SEO.md`
- `docs/FLUTTER_WEB_PARITY.md` (§ create / publish)
