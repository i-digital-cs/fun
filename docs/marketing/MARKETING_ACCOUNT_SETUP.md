# Marketing Account Setup Checklist (Phase 0)

**Purpose:** Owner/operator checklist to create advertising and developer assets.  
**Phase 0 does not create accounts or spend money.**

Mark each item: ☐ TODO · ☑ DONE · ✎ N/A

---

## A. Meta (Facebook + Instagram)

### A1. Presence assets

- [ ] Create / claim **Facebook Page** for Funnity  
- [ ] Convert Instagram to **Professional** account  
- [ ] Link Instagram Professional account to the Facebook Page  
- [ ] Confirm Page roles for the operating admin user  

### A2. Business Portfolio

- [ ] Create **Meta Business Portfolio** (Business Manager)  
- [ ] Add Page + Instagram asset  
- [ ] Add people with least privilege  
- [ ] Start **Business Verification** if planning Full Marketing API access / agency-style use  

### A3. Ad account

- [ ] Create **Ad Account** in Business settings  
- [ ] Set currency, timezone (prefer business home TZ; document it)  
- [ ] Add payment method  
- [ ] Set **account spending limit** (hard financial guardrail)  
- [ ] Assign admin users  

### A4. Developer app

- [ ] Create app at Meta for Developers  
- [ ] App type suitable for Business / Marketing API  
- [ ] Add product: **Marketing API**  
- [ ] Note App ID (store in password manager — not chat logs)  
- [ ] Configure Valid OAuth Redirect URIs for Funnity admin callback (when Phase 2 ready)  
- [ ] Create **System User** in Business; assign Ad Account tasks (`ANALYZE` first; add `ADVERTISE`/`MANAGE` before Phase 7)  
- [ ] Install app on system user; generate token with `ads_read` initially  

### A5. Permissions & review

- [ ] Confirm Limited vs Full Marketing API Access Tier in App Dashboard  
- [ ] If needing Full Access / advanced: prepare App Review + Business Verification  
- [ ] Request only needed permissions (`ads_read` → later `ads_management`)  
- [ ] Complete Data Use Checkup when prompted  

### A6. Measurement

- [ ] Create **Meta Pixel** for funnity.com  
- [ ] Plan events: `PageView`, `CompleteRegistration`, custom `JoinEvent`, `CreateEvent`  
- [ ] Plan **Conversions API** with event_id dedupe  
- [ ] Do **not** install scripts until Phase 4 + privacy/consent review  

### A7. Sanity tests (after Phase 2 code exists)

- [ ] Read ad account via API with system user token  
- [ ] List campaigns (even if empty)  
- [ ] Insights call returns 200 with empty data  

---

## B. TikTok

### B1. Business & ads

- [ ] Create **TikTok For Business** account  
- [ ] Create / access **TikTok Ads Manager** advertiser account  
- [ ] Set currency, timezone, payment method  
- [ ] Set spending limits / budget controls available in Ads Manager  
- [ ] Link TikTok account assets as required for ads  

### B2. Developer app

- [ ] Register as developer on TikTok API for Business portal  
- [ ] Create developer app  
- [ ] Select scopes: Ad Account info, Ads Management (Campaign/Ad Group/Ad), Reporting, Pixel/Measurement as needed  
- [ ] Configure advertiser redirect URL(s) including Funnity admin callback  
- [ ] Store `app_id` + `secret` securely  

### B3. Advertiser authorization

- [ ] Open Advertiser Authorization URL  
- [ ] Advertiser reviews scopes, confirms, completes **email verification code**  
- [ ] Capture `auth_code` (valid **1 hour**, single use)  
- [ ] Exchange for long-term access token via `/oauth2/access_token/`  
- [ ] Store token encrypted in Funnity (Phase 2)  

### B4. Rate limits

- [ ] Note default **Basic** tier (10 QPS / 600 QPM / 864k QPD)  
- [ ] Apply for higher tier only if reporting volume requires it  

### B5. Measurement

- [ ] Create TikTok Pixel  
- [ ] Plan Events API + browser pixel dedupe  
- [ ] Defer install until Phase 4 + consent  

### B6. Sanity tests (after Phase 2)

- [ ] `campaign/get` succeeds  
- [ ] Integrated report call succeeds with Reporting scope  

---

## C. Funnity platform prep (ops)

- [ ] Confirm SuperAdmin user access to `/admin`  
- [ ] Add crontab for `funnity.com` `schedule:run`  
- [ ] Add queue worker strategy for Funnity  
- [ ] Decide account timezone SSOT for reporting  
- [ ] Update Privacy Policy / cookie consent before pixels  
- [ ] Decide whether Telegram alerts are desired (Phase 8)  

---

## D. Order of operations (recommended)

1. Meta Page + IG Professional + Business Portfolio  
2. Meta Ad Account + spending limit  
3. TikTok For Business + Ads Manager + spending limit  
4. Meta Developer App + system user (`ads_read`)  
5. TikTok Developer App + advertiser auth  
6. Implement Phase 1–2 in Funnity  
7. Connect read-only  
8. Privacy/consent → Pixel/CAPI (Phase 4)  
9. Request `ads_management` / write scopes only before Phase 7  

---

## E. What Phase 0 explicitly does **not** do

- Create any of the above accounts  
- Generate tokens  
- Install pixels  
- Launch campaigns  
- Charge advertising budgets  
