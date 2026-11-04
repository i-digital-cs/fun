# Transactional Email V2 (canonical)

Premium light-mode OTP emails integrated into `/home/d14098/funnity.com`.

**Production activation: NOT PERFORMED.** Owner approval required before deploy, cache clears, or live sends.

Template improvements are **not** a guaranteed fix for Gmail Spam / Outlook Junk.

---

## Architecture

| Flow | Security purpose | Presentation `mailContext` | Subject (EN) |
|------|------------------|----------------------------|--------------|
| Registration | `email_verify` | `register` → verify copy | Verify your :app email |
| Login (unverified) | `email_verify` | `login` | Your :app sign-in code |
| Resend verification | `email_verify` | `verify` (default) | Verify your :app email |
| Profile email change | `email_verify` | `email_change` | Confirm your new :app email |
| Password reset | `password_reset` | (forced reset) | Reset your :app password |

Presentation is separated from security purpose: OTP hashing, validation, cooldown keys, and budgets still key only on `EmailAuthPurpose`.

### Shared templates

```
resources/views/emails/
  layouts/transactional.blade.php
  partials/header.blade.php      # 88px PNG mark
  partials/footer.blade.php
  partials/otp-code.blade.php
  auth-code.blade.php
  auth-code-text.blade.php
```

### Logo

- Email PNG: `public/branding/funnity-mark-email.png`
- Helper: `SiteSettingsService::emailLogoUrl()`
- Website SVG logo unchanged

### Security preserved

- Outbound OTP budgets / lanes
- Cooldown, locks, verify attempt limits
- OTP length / expiry config
- API response contracts
- No plaintext OTP in delivery-failure logs beyond existing message field on exceptions (codes never logged)

---

## Visual adjustments (finalization)

- Logo width/height attributes: **88px** (was 72); `max-width:22vw` for mobile balance
- Expiration text: `#475569`
- Security notice: `#64748B`
- Footer body: `#64748B`, strong `#475569`

---

## Greeting display

- `AuthCodeMail` builds a localized greeting from the stored `users.name` for display only.
- Missing / whitespace-only names → neutral `Hi,` / `مرحباً،` (account name is not rewritten).
- Short legitimate names (including two-character names such as `as`) are shown as stored.
- Manual Outlook checklist: `docs/email/OUTLOOK_MANUAL_QA.md`.

## Known limitations

1. Resend from the verification screen uses default **verify** copy (not login), because the resend endpoint does not know whether the user arrived via register or login. Security purpose remains `email_verify`.
2. Inbox placement is independent of templates.
3. `APP_URL` must be the public HTTPS origin for PNG logo URLs.
4. A greeting of `Hi as,` means the account’s stored name is literally `as` (registration allows short names); it is not a truncation bug.

---

## Tests

```text
php artisan test --filter='TransactionalEmailV2Test|EmailOtpCooldownTest|ApiAuthFlowsTest|OtpSecurityHardeningTest'
Tests: 33 passed (191 assertions)
```

---

## Visual QA

See `docs/email/qa/` (EN/AR desktop + mobile for verify, login, reset).

Local Chromium only — not claimed as Gmail/Outlook rendering.

---

## Backup / rollback

Backup of pre-integration canonical files:

`~/backups/funnity-email-v2-finalize-20261101094321`

Rollback: restore those files over the listed paths, remove `public/branding/funnity-mark-email.png` and new email layout/partials if desired, and delete `tests/Feature/TransactionalEmailV2Test.php` / `docs/email/` if reverting docs.

Source worktree (not deleted): `/home/d14098/funnity-email-v2` branch `feature/transactional-email-v2`.

---

## Post-activation checklist (after owner approval)

- [ ] Confirm `https://<host>/branding/funnity-mark-email.png` returns 200
- [ ] Controlled registration OTP to Gmail / Outlook
- [ ] Controlled login (unverified) OTP — confirm sign-in subject
- [ ] Password reset OTP
- [ ] Arabic user locale RTL body
- [ ] Images blocked: code still readable in plain text / HTML text
