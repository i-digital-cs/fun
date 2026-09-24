# Flutter: Required sex (user + event audience)

**Audience:** Flutter developer  
**Status:** Backend + API **shipped** (user profile **and** event audience). Wire / verify Flutter against this contract.  
**Parity:** Web Blade + `docs/FLUTTER_WEB_PARITY.md`  
**Related:** `.cursor/rules/web-flutter-parity.mdc`, `mobile/docs/ENGINEERING_CONSTITUTION.md`

---

## Goal

Sex uses the **same three API values** everywhere:

| API value | EN | AR |
|-----------|----|----|
| `male` | Male | ذكر |
| `female` | Female | أنثى |
| `mix` | Mix | مختلط |

### 1) User profile sex (who the member is)

| Surface | Rule |
|---------|------|
| Email **register** | `sex` **required** |
| **Profile** settings | `sex` **required** on save |
| Google auth | may leave `sex: null` → `needs_sex: true` until profile sets it |

### 2) Event audience sex (who the event is for)

| Surface | Rule |
|---------|------|
| **Create / edit event** | `sex` **required** (`male` / `female` / `mix`) |
| Event API payloads | always include `sex` |
| Join | if event is `male` or `female`, only users with matching profile `sex` may request to join; `mix` allows anyone. Mismatch → `join_action: "sex_mismatch"` |

Do **not** invent other values.

---

## API — user

### `POST /api/register`

```json
{
  "name": "Sara",
  "email": "sara@example.com",
  "password": "Password1!",
  "password_confirmation": "Password1!",
  "sex": "female",
  "locale": "en"
}
```

### `PUT /api/settings/profile`

Include `"sex": "female"` (required).

### `GET /api/user`

| Key | Type | Notes |
|-----|------|--------|
| `sex` | `string?` | `male` / `female` / `mix` |
| `needs_sex` | `bool` | true when unset |

---

## API — events

### `POST /api/events` / `PUT /api/events/{id}`

```json
{
  "title": "Football night",
  "category_id": 1,
  "city_id": 1,
  "starts_at": "2026-10-01T18:00:00+03:00",
  "max_participants": 10,
  "sex": "mix"
}
```

| Field | Rules |
|-------|--------|
| `sex` | **required** on create; **required** when sent on update (`male` \| `female` \| `mix`) |

### Event resource (list / detail)

```json
{
  "id": 42,
  "title": "Football night",
  "sex": "male",
  "join_action": "sex_mismatch",
  "max_participants": 10
}
```

### Join actions (Bearer required for personalized `join_action`)

| Value | Meaning |
|-------|---------|
| `request_to_join` | Can request |
| `request_pending` | Pending |
| `joined` | Member |
| `request_rejected` | Rejected (can retry if still allowed) |
| `event_full` | Full |
| **`sex_mismatch`** | User sex does not match event audience (or user has no sex and event is not mix) |
| `not_available` | Not published / etc. |

`POST /api/events/{id}/join` returns **422** with message when sex mismatches (do not fake success).

---

## Flutter screens

### Register `/register`

- Required sex selector (Male / Female / Mix).
- Pass `sex` on register API.

### Profile `/settings/profile`

- Required sex selector; prefill from `user.sex`.
- Send `sex` on update.

### Create / edit event

**Files:** `create_event_page.dart`, `EventDraft`, `events_remote_source.dart`

- Required audience dropdown after max participants (match web `_form.blade.php`).
- Default new draft to `mix` if you want a sensible default; still **required** (must be explicit in payload).
- Prefill from `event.sex` when editing.
- Form field → `EventDraft.sex` → multipart/JSON `"sex"`.

### Event detail + cards

- Show audience chip: `Audience: Male|Female|Mix`.
- Handle `join_action == sex_mismatch` with banner (no Join CTA) — same as web.

### Domain

```dart
// User
final String? sex;
final bool needsSex;

// EventItem
final String? sex; // male | female | mix

// EventDraft
final String sex; // required
```

---

## L10n

| Key | EN | AR |
|-----|----|----|
| `sex` | Sex | الجنس |
| `sexMale` | Male | ذكر |
| `sexFemale` | Female | أنثى |
| `sexMix` | Mix | مختلط |
| `sexRequired` | Please select your sex. | يرجى اختيار الجنس. |
| `eventSex` | Audience | الجمهور |
| `eventSexHelp` | Who this event is for: Male, Female, or Mix. | لمن هذه الفعالية: ذكر، أنثى، أو مختلط. |
| `eventSexMismatch` | This event is limited to a different audience than your profile sex. | هذه الفعالية مخصصة لجمهور يختلف عن الجنس في ملفك. |

Then `flutter gen-l10n`.

---

## Files checklist

| Area | Path |
|------|------|
| User model | `features/auth/domain/user.dart` |
| Register / profile | auth + profile presentation + remotes |
| Event model / draft | `features/events/domain/event_item.dart` |
| Event API | `features/events/data/events_remote_source.dart` |
| Create/edit UI | `features/events/presentation/create_event_page.dart` |
| Detail join UI | `event_detail_page.dart` → `EventJoinActions` |
| Cards | `widgets/event_card.dart` |
| L10n | `app_en.arb` / `app_ar.arb` |
| Tests | Fake repos + `event_join_actions_test.dart` for `sex_mismatch` |

---

## QA checklist

**User**

- [ ] Register without sex → blocked / `422`
- [ ] Register + profile with each of male/female/mix
- [ ] Google user `needs_sex` until profile sets sex

**Event**

- [ ] Create without sex → `422`
- [ ] Create with `male` / `female` / `mix` → stored; list/detail show `sex`
- [ ] Male user cannot join female-only event → `sex_mismatch` / 422
- [ ] Mix event joinable by any sex (including unset user)
- [ ] Edit event changes audience and persists
- [ ] EN + AR + RTL on create + detail

---

## Out of scope

- Admin-only UI (unless admin event edit form is updated separately)
- Discover filter-by-sex (nice-to-have later)
- Hard gate blocking home until `needs_sex` (unless product asks)

---

## Web reference

- User register: `resources/views/auth/register.blade.php`
- User profile: `resources/views/profile/edit.blade.php`
- Event form: `resources/views/events/_form.blade.php`
- Event show / cards: `sex` chip + `sex_mismatch` join state
- Enum: `app/Enums/UserSex.php`
- Join: `EventJoinAction::SexMismatch` = `sex_mismatch`

---

*Updated 2026-09-24 — user sex + event audience sex.*
