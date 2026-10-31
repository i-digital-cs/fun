# Flutter QA — Event publishing (OPTION A)

**Date:** 2026-10-31  
**App ID:** `com.funnity.app`  
**Emulator:** `bidex_api34` (`emulator-5554`)  
**Verdict:** **CONDITIONAL**

---

## 1. Windows Git baseline

| Item | Value |
|------|-------|
| Branch | `main` |
| Local HEAD | `d3e5df51a4d0797de2eee54e0c2b677991953f6a` |
| Message | `Add production deployment verification documentation for multilingual SEO and direct publishing` |
| Dirty before this task | Clean (matched `origin/main`) |
| Destructive git | None (`reset --hard` / `clean` / force-push not used) |
| Commit/push | Not performed (per instructions) |

Uncommitted work from this task is preserved locally (see §6).

---

## 2. Remote Git comparison

| Item | Value |
|------|-------|
| `git fetch origin` | OK |
| `origin/main` | `d3e5df5` (identical to local HEAD at start) |
| Local commits ahead of origin | None at start |
| Server-reported HEAD `85b84cc` | Ancestor of `d3e5df5` (present on `origin/main`) |
| Windows vs remote | In sync before edits; publishing Flutter changes remain uncommitted |

---

## 3. API handoff availability

| Doc | Present locally |
|-----|-----------------|
| `docs/flutter/EVENT_PUBLISHING_API_HANDOFF.md` | **Yes** |
| `docs/backend/PRODUCTION_DEPLOYMENT_VERIFICATION.md` | **Yes** |

No invented endpoints or fields.

---

## 4. Exact backend contract used

- **Endpoint:** `POST /api/events` (multipart / FormData)
- **Field:** `publish_intent` as string `"1"` or `"0"` (Laravel `FILTER_VALIDATE_BOOLEAN`)
- **Create Event:** `publish_intent=true` → expect `data.status == published`
- **Save Draft:** `publish_intent=false` → expect `data.status == draft`
- **Omit intent:** draft (backward compatible; Flutter always sends explicit `"1"`/`"0"` on create)
- **Update:** `PUT /api/events/{id}` — Flutter **omits** `publish_intent` on update
- **Discover eligibility:** published only; drafts via My Events / authorized edit

---

## 5. Existing Flutter root cause

1. Create flow **omitted** `publish_intent` → backend defaulted to **draft**.
2. Primary CTA used `l10n.createDraft` (“Save draft”), not Create Event.
3. Post-create UX used a dismissible **Publish offer dialog** instead of direct publish.
4. Success toast used draft-oriented copy after HTTP success without verifying `status`.
5. Draft navigation used public `/events/{id}` (risk of 403 for non-owners / gate edge cases).

---

## 6. Files changed

| Path | Change |
|------|--------|
| `mobile/lib/features/events/domain/event_item.dart` | `EventDraft.publishIntent` + `copyWith` |
| `mobile/lib/features/events/data/events_remote_source.dart` | `eventDraftMultipartFields`; `"1"`/`"0"` on create only |
| `mobile/lib/features/events/presentation/create_event_controller.dart` | Outcome enum; status gate; double-submit lock; Discover `markStale` + home refresh |
| `mobile/lib/features/events/presentation/create_event_page.dart` | Dual CTAs; status-aware UX; draft → edit route |
| `mobile/lib/l10n/app_en.arb` / `app_ar.arb` + generated | Create Event / Save Draft / success / mismatch / Continue Editing |
| `mobile/test/features/events/event_publish_intent_test.dart` | **New** contract tests |
| `docs/FLUTTER_WEB_PARITY.md` | §2.5 + §10 item 1b updated |
| `docs/flutter/EVENT_PUBLISHING_FLUTTER_QA.md` | This report |
| `docs/flutter/event_publishing_qa/emulator_launch.png` | Emulator launch evidence |

Discover / OTP / public-profile code paths were not reworked.

---

## 7. Create Event implementation

- Primary orange `GradientButton` label: **Create Event** / **إنشاء الفعالية**
- Submits `EventDraft(publishIntent: true)` → multipart `publish_intent=1`
- Success only when `outcome == published`
- Snackbar: “Event published successfully” / “تم نشر الفعالية بنجاح” + View Event
- Navigation: `/my-events` then push `/events/{id}`
- Feeds: Discover `markStale()`; best-effort `homeNearbyController.load()`

---

## 8. Save Draft implementation

- Secondary `OutlinedButton`: **Save Draft** / **حفظ كمسودة**
- Submits `publishIntent: false` → `publish_intent=0`
- Success only when `outcome == draft`
- Snackbar: “Draft saved successfully” / “تم حفظ المسودة بنجاح” + Continue Editing
- Navigation: `/my-events` then push `/events/{id}/edit` (authorized edit; not public show)
- Does **not** call Discover `markStale`

---

## 9. Request serialization evidence

Unit tests assert:

```text
publishIntent: true  → fields['publish_intent'] == '1'  (String)
publishIntent: false → fields['publish_intent'] == '0'  (String)
includePublishIntent: false → key omitted (update path)
```

Helper: `eventDraftMultipartFields` in `events_remote_source.dart`.

---

## 10. Response status validation

| Intent | Returned status | Outcome | User success? |
|--------|-----------------|---------|---------------|
| true | published | `published` | Yes |
| true | draft | `unexpectedDraft` | **No** — mismatch copy + edit |
| false | draft | `draft` | Yes (draft copy) |
| false | published | `unexpectedPublished` | **No** — mismatch copy |
| any | HTTP error | error message | No (form preserved) |

HTTP 200/201 alone never claims publication.

---

## 11. Discover integration

- On confirmed publish: `discoverController.markStale()` (existing mechanism)
- Map/List filter SSOT unchanged (city / radius / `q` preserved across stale reload)
- Drafts not injected into Discover
- Home nearby best-effort refresh after publish

---

## 12. Draft navigation

- Save Draft / unexpected status → `/events/{id}/edit` (`CreateEventPage(eventId:)`)
- Published → `/events/{id}` detail
- Back stack via `/my-events` shell landing (NAV-F3)

---

## 13. Automated tests

**File:** `mobile/test/features/events/event_publish_intent_test.dart`

Covered: multipart `"1"`/`"0"`; publish/draft outcomes; unexpected draft/published; 422/403/network; double-submit; Discover stale + filter preservation; EN/AR labels; RTL widget.

**Command results (this machine):**

```text
flutter pub get          → OK
flutter analyze (changed event files) → No issues found
flutter test test/features/events/event_publish_intent_test.dart → +14 All tests passed
flutter test (full suite) → +114 -2
```

**Full-suite failures (pre-existing / unrelated to publishing):**

1. `test/core/navigation/app_navigation_test.dart` — `sanitizeReturnTo` expects `/u/abc123` allowed; actual `null` (public-profile returnTo allowlist drift).
2. `test/visual/v2_home_hero_capture_test.dart` — golden pixel diff `flutter-home-hero.png` (0.09%). **Goldens not regenerated.**

Publishing contract tests: **PASS**.

---

## 14. Android emulator evidence

| Step | Evidence |
|------|----------|
| AVD | `bidex_api34` |
| Device | `emulator-5554` (`sdk_gphone64_x86_64`) |
| `ro.boot.qemu.avd_name` | `bidex_api34` |
| Debug APK build | Start `2026-10-31 12:26:40` → End `12:28:03` |
| APK path | `mobile/build/app/outputs/flutter-apk/app-debug.apk` |
| APK mtime | `2026-10-31 12:27:59` (~229 MB) |
| Install | `adb install -r` Success — Start `12:28:49` → End `12:28:58` |
| Package | `com.funnity.app` `versionName=1.0.2` `versionCode=3` `lastUpdateTime=2026-10-31 12:28:55` |
| Launch | `MainActivity` visible / process running |
| Screenshot | `docs/flutter/event_publishing_qa/emulator_launch.png` |

**Live Create Event / Save Draft against production:** **not executed** (default `API_BASE_URL=https://funnity.com/api`; no disposable prod events; event **605** untouched).

Scenario A–D end-to-end: **CONDITIONAL** — verified by unit/widget mocks; UI launch only on emulator.

---

## 15. Arabic / English QA

| String | EN | AR |
|--------|----|----|
| Primary CTA | Create Event | إنشاء الفعالية |
| Secondary CTA | Save Draft | حفظ كمسودة |
| Published success | Event published successfully | تم نشر الفعالية بنجاح |
| Draft success | Draft saved successfully | تم حفظ المسودة بنجاح |
| Continue Editing | Continue Editing | متابعة التحرير |
| Status mismatch | The event was not published as expected… | لم يتم نشر الفعالية كما هو متوقع… |

RTL Directionality covered by widget test. Emulator AR language switch: **not live-verified** (CONDITIONAL).

---

## 16. Remaining issues

1. Emulator E2E publish/draft against a **non-production** API (or approved mock interceptor) still needed for PASS.
2. Pre-existing full-suite failures: navigation `sanitizeReturnTo` `/u/…`; V2 hero golden drift.
3. `flutter install` defaults to missing release APK; use `adb install -r` of debug APK (documented above).
4. Optional: remove unused `PublishOfferDialog` later (left in file; no longer primary path).

---

## 17. Final verdict

### **CONDITIONAL**

**Why not PASS:** Critical emulator flows (Create Event → published, Save Draft → draft) were **not** live-verified against a safe API; production writes are forbidden.

**Why not BLOCKED:** Handoff present; Flutter sends explicit `publish_intent` `"1"`/`"0"`; status-gated UX; dual CTAs; Discover stale on publish; draft edit navigation; automated contract tests **all pass** (+14).

**Ready for:** Owner approval to run Scenario A/B on staging/`FLAVOR=dev`, then promote verdict to PASS.
