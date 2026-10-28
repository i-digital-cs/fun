# SEO release readiness

**Date:** 17 October 2026  
**Deployed:** No  
**Production data changed:** No  
**Search Console:** Not verified. Indexing and ranking are unchanged from the Phase 0 observation.  
**Verdict:** **CONDITIONAL**

The technical change is safe to take to a deploy approval. The content gate is not passed while four placeholder or test events stay indexable. Live Apache redirects are not observed yet, because this environment was not deployed.

## Test suite

`php artisan test` after the fixes below: **232 passed, 0 failed** (976 assertions).

The previous 5 failures were reproduced on a clean `main` worktree (`git stash` of the SEO diff, then the same tests, then `git stash pop`). They are not caused by the canonical-redirect or homepage changes.

| Test | Failure on baseline | Root cause | Fix |
|---|---|---|---|
| `MediaConfigTest::test_media_disks_are_configured_for_local_scaffold` | Expected `media`, got `r2` | `phpunit.xml` did not set `MEDIA_DISK`. Laravel then read production `.env` (`MEDIA_DISK=r2`). | Test env only: `MEDIA_DISK=media` and `MEDIA_PRIVATE_DISK=media_private` with `force="true"`. Production `.env` was not edited. |
| `MediaConfigTest::test_media_disk_helper_resolves_configured_disks` | Same `r2` result | Same config leak into `MediaDisk::public()`. | Same phpunit pin. |
| `UserAuthMethodTest::it_detects_linked_google_and_password_accounts` | Expected `both`, got `google+password` | Application code since Sign in with Apple returns `google+password`. `authMethodLabel()` still accepts the old word `both`, but `authMethod()` never returns it. | Assertion updated to the real contract. Production behavior was not changed. |
| `BroadcastingAuthApiTest::test_user_can_authorize_own_private_channel` | `assertOk` then “Invalid JSON” | `phpunit.xml` sets `BROADCAST_CONNECTION=null`. `NullBroadcaster::auth()` returns nothing, so the route is an empty 200 and never runs `routes/channels.php`. | This test class boots a Pusher driver with fake `test-key` / `test-secret` before the app starts, then restores `null` in `tearDown`. |
| `BroadcastingAuthApiTest::test_user_cannot_authorize_another_users_channel` | Expected 403, got 200 | Same null driver. It does not deny other users’ channels. | Same local signer. Own channel now returns 200 with `auth`. The other user’s channel returns 403. |

### Implications

- **Media.** Test processes were resolving the production R2 disk name. Pinning the suite to the local `media` disks stops that. It does not change the live disk.
- **Auth method string.** Admin screens already label `google+password` as “both”. No sign-in path changed.
- **Broadcast auth.** Production `.env` uses `BROADCAST_CONNECTION=pusher`, so live channel checks are the Pusher broadcaster, not the null driver. The repaired test is the first run that actually executes `App.Models.User.{id}`. Guests still get 401. A user cannot sign another user’s private channel. The null driver remains the default for the rest of the suite so tests do not call Pusher.

## Content gate

Per-event records: `docs/seo/event-content-quality-audit.json` (555 events). Narrative: `EVENT_CONTENT_QUALITY_AUDIT.md`.

| Class | Count | Still indexable |
|---|---|---|
| Duplicate or near-duplicate | 500 (418 ended, 82 current or upcoming) | Yes. Not removed. |
| Historical, meaningful | 50 | Yes. A past date is not a reason to drop them. |
| Historical, little continuing value | 0 | — |
| High-quality unique | 1 (event 530) | Yes |
| Valid, insufficient content | 0 | — |
| Placeholder or test | 4 (501, 502, 503, 602) | Yes. **Blocker until the owner approves a change.** |

Profiles stay `index,follow`. Recommendation is `noindex,follow` in `PROFILE_INDEXING_POLICY.md`. Not applied.

## What is safe to approve

- Canonical GET/HEAD redirects in `.htaccess` and `EnforceCanonicalUrl` (see `SEO_PRODUCTION_RISK_REGISTER.md`).
- Homepage title, description, and Organization JSON-LD already in the working tree.
- Sitemap `lastmod` no longer stamped with `now()` for static URLs.
- The test-env pins above.

## What blocks a clean release

1. Events **501, 502, 503, and 602** are still published or completed, `index,follow`, and eligible for the sitemap. Do not deploy a claim that indexable content is clean until the owner unpublishes them or explicitly accepts them.
2. After any deploy, run the curl list in `SEO_DEPLOYMENT_CHECKLIST.md`. This review did not execute the live Apache rules.
3. Search Console domain verification is still open. Do not report indexing from this document.

## Decision

**CONDITIONAL.** Not blocked: authentication POSTs are not redirected, the suite is green, and no event row was edited. Not a pass: four test events would still be offered to Google, and the vhost redirects are unverified in production.
