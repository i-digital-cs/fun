# Flutter: Required user sex field

**Audience:** Flutter developer  
**Status:** Backend + API **shipped**. Wire / verify Flutter UI + models against this contract.  
**Parity:** Match web Blade (`auth/register.blade.php`, `profile/edit.blade.php`) and `docs/FLUTTER_WEB_PARITY.md` (§2.12, §2.15).  
**Related rules:** `.cursor/rules/web-flutter-parity.mdc`, `mobile/docs/ENGINEERING_CONSTITUTION.md`

---

## Goal

Every member must select **sex** with exactly three options:

| API value | EN label | AR label |
|-----------|----------|----------|
| `male` | Male | ذكر |
| `female` | Female | أنثى |
| `mix` | Mix | مختلط |

- **Email register:** `sex` is **required** (reject if missing / invalid).
- **Profile settings:** `sex` is **required** on save.
- **Google auth:** may create users with `sex: null` → API returns `needs_sex: true`. User must set sex on Profile (no hard nav gate required in v1 unless product asks).

Do **not** invent other values (`other`, `prefer_not_to_say`, etc.).

---

## API contract

Base: `{APP_URL}/api` · header `Authorization: Bearer {token}` when authenticated · optional `Accept-Language: en|ar`.

### Values

```text
sex ∈ { "male", "female", "mix" }
```

### `POST /api/register`

**Body (JSON):**

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

| Field | Rules |
|-------|--------|
| `sex` | **required**, one of `male` \| `female` \| `mix` |

**Success `201`:** Sanctum `token` + `user` including `sex`, `needs_sex`.

**Validation fail `422`:** standard Laravel errors, e.g. `errors.sex`.

### `PUT /api/settings/profile`

JSON or multipart (same as today for photo).

```json
{
  "name": "Sara",
  "email": "sara@example.com",
  "sex": "female",
  "locale": "en"
}
```

| Field | Rules |
|-------|--------|
| `sex` | **required**, `male` \| `female` \| `mix` |

Omit / invalid → `422`.

### `GET /api/user` (and login / Google `user` payloads)

Parse and keep:

| Key | Type | Notes |
|-----|------|--------|
| `sex` | `string?` | `male` / `female` / `mix`, or `null` |
| `needs_sex` | `bool` | `true` when sex not set (typical after Google) |

Example fragment:

```json
{
  "id": 12,
  "name": "Sara",
  "email": "sara@example.com",
  "sex": "female",
  "needs_sex": false,
  "locale": "en",
  "status": "active"
}
```

---

## Screens to update

### 1. Register — `/register`

**File:** `mobile/lib/features/auth/presentation/register_page.dart`

- Add required selector (dropdown or segmented control) **before** password fields (match web order: name → email → sex → password → confirm).
- Options: Male / Female / Mix (l10n).
- Client validate: cannot submit theater / register without selection.
- Pass `sex` through:

`AuthController.register` → `AuthRepository.register` → `AuthRemoteSource.register` → body key `"sex"`.

### 2. Profile edit — `/settings/profile`

**File:** `mobile/lib/features/profile/presentation/profile_edit_page.dart`

- Same three options; preselect from `user.sex`.
- If `user.sex == null` / `needsSex`, show empty + required error until chosen.
- Include `"sex"` in `ProfileRepository.updateProfile` / remote PUT (and multipart FormData when photo is sent).

### 3. Domain model

**File:** `mobile/lib/features/auth/domain/user.dart`

```dart
final String? sex;       // male | female | mix
final bool needsSex;     // from needs_sex, or true if sex null/empty
```

- `User.fromJson`: read `sex`, `needs_sex`.
- `copyWith` / `props`: include both.
- Do **not** put sex on public profile UI unless product asks (web public card does not show it).

---

## L10n (EN + AR)

Add to `mobile/lib/l10n/app_en.arb` and `app_ar.arb`, then `flutter gen-l10n`:

| Key | EN | AR |
|-----|----|----|
| `sex` | Sex | الجنس |
| `sexMale` | Male | ذكر |
| `sexFemale` | Female | أنثى |
| `sexMix` | Mix | مختلط |
| `sexRequired` | Please select your sex. | يرجى اختيار الجنس. |

Use logical layout (`EdgeInsetsDirectional`, etc.) — A11Y-RTL-001.

---

## UX / design

- Match existing auth/profile form chrome (rounded fields, indigo focus, canvas/surface).
- Register CTA stays orange→amber gradient.
- Prefer `DropdownButtonFormField` or equivalent already used on profile (locale).
- Show server `422` field errors via existing failure / SnackBar mapping (`failureUserMessage`).

---

## Optional (v1 nice-to-have)

If `user.needsSex` after Google / restore:

- Soft prompt on Profile hub (“Select your sex”) linking to `/settings/profile`, **or**
- Banner on home — **not** a hard block of identity / join unless product requests a gate.

Do **not** change `routeAfterAuth` order (deactivated → email verify → identity → home) unless explicitly asked.

---

## Files checklist

| Layer | Path | Change |
|-------|------|--------|
| Domain | `features/auth/domain/user.dart` | `sex`, `needsSex` |
| Auth API | `features/auth/data/auth_remote_source.dart` | send `sex` on register |
| Auth repo | `features/auth/domain/auth_repository.dart` + impl | `sex` param |
| Auth UI | `features/auth/presentation/auth_controller.dart` | pass `sex` |
| Auth UI | `features/auth/presentation/register_page.dart` | selector + validate |
| Profile API | `features/profile/data/profile_remote_source.dart` | send `sex` |
| Profile repo | domain + impl | `sex` param |
| Profile UI | `features/profile/presentation/profile_edit_page.dart` | selector + validate |
| L10n | `lib/l10n/app_en.arb`, `app_ar.arb` | keys above |
| Tests | Fake `AuthRepository` in `test/` | add `sex` to `register(...)` |

---

## QA checklist

- [ ] Register without sex → client blocks; API alone → `422` on `sex`
- [ ] Register with `male` / `female` / `mix` → `201`, `user.sex` matches
- [ ] `GET /api/user` after login shows `sex` + `needs_sex: false`
- [ ] Profile save without sex → blocked / `422`
- [ ] Profile change sex → persists after `refreshMe`
- [ ] Google new user → `needs_sex: true` until profile sets sex
- [ ] EN + AR labels + RTL on register + profile
- [ ] Compared with web `/register` and `/settings/profile`

---

## Out of scope

- Admin `/admin/*`
- Showing sex on public profile `/u/{publicId}`
- Event audience gender (male-only / female-only / mix events) — separate feature if requested
- Hard participation gate on `needs_sex` (unless product asks)

---

## Web reference (source of truth for UX)

- Register: `resources/views/auth/register.blade.php`
- Profile: `resources/views/profile/edit.blade.php`
- Enum: `app/Enums/UserSex.php` (`male`, `female`, `mix`)

---

*Created 2026-09-24 — handoff for Flutter parity with required user sex.*
