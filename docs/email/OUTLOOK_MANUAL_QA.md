# Outlook manual QA checklist (owner)

Email V2 HTML is table-based with inline CSS and a PNG logo. Local Chromium QA is **not** Outlook proof.

## Before sending

- [ ] Use an owner-approved Outlook / Microsoft 365 test mailbox only
- [ ] Prefer one password-reset **or** one login OTP send (respect cooldown / budgets)
- [ ] Do not paste OTP codes into tickets or chat

## In Outlook Desktop (Windows)

- [ ] Message opens without layout collapse
- [ ] Funnity PNG logo loads (or text fallback if images blocked)
- [ ] Heading, greeting, and body are readable (not cut off)
- [ ] OTP digits are large, spaced, and fully visible
- [ ] Expiration + security notice have readable contrast
- [ ] No broken overlapping tables / huge empty gaps
- [ ] With images blocked: code + copy still usable

## In Outlook on the web

- [ ] Same checks as desktop
- [ ] Narrow browser width (~390px): card remains readable, OTP not clipped

## Inbox placement (record only)

- [ ] Inbox vs Junk
- [ ] Authentication results if shown (SPF / DKIM / DMARC)
- [ ] Timestamp, email type, locale (EN/AR)

## Greeting spot-check

- [ ] Named account shows `Hi <full name>,` / Arabic equivalent
- [ ] Short real names (e.g. two letters) are shown as stored — not rewritten
- [ ] Empty/whitespace name would show neutral `Hi,` / `مرحباً،` (no `Hi  ,`)
