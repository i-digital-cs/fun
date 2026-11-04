# Email V2 visual QA

Synthetic previews (OTP `4821`, fictional recipients). Local Chromium renders — **not** Gmail/Outlook captures.

| Context | EN | AR |
|---------|----|----|
| Registration verify | `en-verify-{desktop,mobile}.*` | `ar-verify-{desktop,mobile}.*` |
| Login (sign-in) | `en-login-{desktop,mobile}.*` | `ar-login-{desktop,mobile}.*` |
| Password reset | `en-reset-{desktop,mobile}.*` | `ar-reset-{desktop,mobile}.*` |
| Email change | `en-email-change-{desktop,mobile}.html` | `ar-email-change-{desktop,mobile}.html` |

`funnity-mark-email.png` is copied locally so HTML previews resolve the logo without `APP_URL`.
