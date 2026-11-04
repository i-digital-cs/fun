# Living Map V3.5.7 — Final Visual Acceptance

**Date:** 2026-11-02  
**Mode:** Review only (no V3.5.8, no homepage deploy, no DB writes)  
**Preview:** `https://funnity.com/preview/living-map-v35?expires=1794212659&signature=4e43cb1fc3474ba50241031929a93b4ce8f91657e84250701ab55504e5f63d64`  
**Raw probe:** `acceptance_report.json`

## Final recommendation: **CONDITIONAL**

V3.5.7 meets the intended premium spotlight / carousel / aggregate flows on a **fresh** session. The owner screenshot with **no spotlight** is explained and reproduced: **session dismiss persistence**, not a missing build or CSS clip. Remaining caveats (below) should be accepted explicitly or scheduled as a tiny follow-up before homepage promotion.

---

## Explanation — missing Event Spotlight on owner screenshot

### Verified cause

Spotlight dismiss writes:

`sessionStorage['lm-v35-spotlight-dismissed'] = '1'`

On every subsequent load in the **same browser tab/session**, `createLivingMap` reads that flag and **never offers** the spotlight (`renderSpotlight` early-returns → `hidden`).

| Probe | `sessionDismissed` | Spotlight DOM | Marker highlight |
|---|---|---|---|
| Fresh load (storage cleared) | `null` | Visible (`display:block`) — “Console night with friends” | Port Said aggregate `city_id=11` · count 3 |
| Reload with dismiss pre-set | `'1'` | **Hidden** (`display:none`) | None |
| Evidence shot | — | `acceptance-00-dismissed-storage-reload.jpg` | Matches owner “no spotlight” look |

### Ruled out (for this reproduction)

| Hypothesis | Result |
|---|---|
| CSS positioning / clipping | Fresh spotlight `spotRect` fully inside stage; `clippedByStage: false` |
| Responsive hide-on-desktop | Desktop fresh load shows spotlight |
| No events / empty pick | Fresh load picks a real API event with image/title/date |
| Screenshot timing alone | Dismissed-storage reload stays hidden after full settle (~5s) |
| Homepage / wrong surface | Preview banner V3.5.7 present; `/` has no `.lm-shell--v35` |

### Intended vs surprising

- **Intended:** dismiss does not nag every refresh in the same tab.  
- **Surprising for review:** a single ✕ makes later reloads look like the feature was never shipped.  
- **Workaround for reviewers:** DevTools → Application → Session Storage → delete `lm-v35-spotlight-dismissed`, or open a new private window.

---

## Screenshot index

| # | File | State |
|---|---|---|
| 0 | `acceptance-00-dismissed-storage-reload.jpg` | Reproduce missing spotlight (dismissed storage) |
| 1 | `acceptance-01-fresh-desktop-load.jpg` | Fresh desktop full page |
| 2 | `acceptance-02-desktop-spotlight-visible.jpg` | Spotlight card close-up |
| 3 | `acceptance-03-spotlight-marker-highlight.jpg` | Stage with spotlight + lit aggregate |
| 4 | `acceptance-04-aggregate-event-list.jpg` | City aggregate list |
| 5 | `acceptance-05-floating-event-card.jpg` | Floating event card from list |
| 6 | `acceptance-06-desktop-carousel.jpg` | Full carousel strip |
| 7 | `acceptance-07-mobile-spotlight.jpg` | Mobile bottom-sheet spotlight |
| 8 | `acceptance-08-mobile-carousel.jpg` | Mobile carousel |
| 9 | `acceptance-09-arabic-rtl.jpg` | Arabic RTL desktop |
| 10 | `acceptance-10-spotlight-dismissed.jpg` | After dismiss click |

All event titles/images/dates/places observed are from live `/api/discovery/map` (and city-events for lists). No production fixtures created.

---

## Interaction verification

| Interaction | Result | Notes |
|---|---|---|
| Spotlight opens on fresh load | **Pass** | Real title/date/approx disclaimer |
| Associated aggregate highlighted | **Pass** (EN fresh) | `city-aggregate` Port Said `11` |
| Dismiss button | **Pass** | Hides panel + clears `.is-spotlight`; sets session flag |
| Does not unexpectedly reappear (same session with flag) | **Pass** | acceptance-00 |
| Aggregate → list | **Pass** | Panel visible; **3** rows (live Port Said count) |
| List → floating card | **Pass** | `floatingFromList: 1` |
| Carousel → card | **Pass** | Card opens with real title |
| Carousel → aggregate highlight | **Conditional** | One sample (`Courtside rally night`) opened card but `selectedAgg: false` — highlight path not always applied for that event’s city pin |
| No fabricated exact coords | **Pass** | Spotlight/list copy keeps approximate disclaimer |
| Critical overlap | **Pass** | Loc filter + HUD remain usable; spotlight bottom-end |
| Keyboard rail ArrowRight | **Pass** | `scrollLeft` 2 → 298 |
| Mobile dismiss (touch) | **Pass** | Hidden after tap |
| Homepage isolation | **Pass** | No `.lm-shell--v35` on `/` |
| RTL spotlight | **Pass** (visible) | Arabic copy for approx note |
| RTL marker link | **Conditional** | Spotlight visible but `markerLit: false` when associated pin not treated as on-canvas |

---

## Visual review

| Aspect | Assessment |
|---|---|
| Thumbnail size | Improved vs early V3.5.6; readable on desktop & mobile |
| Title hierarchy | Strong; CTA full-width orange is clear |
| Spacing / glass | Acceptable premium glass; shadow restrained |
| Marker highlight | Orange glow distinguishable from plain aggregates on EN fresh load |
| Desktop balance | Good; spotlight does not cover loc filter |
| Mobile sheet | Bottom-sheet present with grab affordance + large CTA |
| RTL | Layout mirrors; Tajawal path intact |
| Carousel | Consistent cards; images/categories/dates real |
| Empty/loading | Not empty in live Delta data; empty copy still wired in rail |

### Visual issues (non-blocking / conditional)

1. **Reviewer confusion** when spotlight is session-dismissed — looks “missing.”  
2. **RTL / off-canvas** spotlight may show without marker glow (by design: no fabricated pin).  
3. **Dismiss glyph** can read as a small block in some JPEG compressions (still an ✕ control).  
4. Occasional **carousel → aggregate** highlight miss for non–Port Said cards (card still opens).

---

## Remaining blockers

| Blocker | Severity | Needed for homepage? |
|---|---|---|
| Session dismiss hides spotlight with no visible “show again” affordance | Medium (UX/review) | Prefer a one-line “Featured event” restore or per-day dismiss before promotion |
| Carousel aggregate highlight not 100% reliable for every card | Low–Medium | Should fix before calling marker sync complete |
| Live aggregate counts fluctuate | Info | Expected; not a blocker |

No geographic integrity regressions found relative to V3.5.5/5.6 audits. Homepage remains V3.4.

---

## Recommendation detail

**CONDITIONAL** — approve preview quality for continued owner review **if** the owner accepts that:

1. Missing spotlight on their shot was **dismiss persistence**, verified.  
2. Fresh private/incognito (or cleared session key) shows the polished spotlight + Port Said aggregate link.  
3. Homepage promotion waits until either dismiss UX is clarified or the conditional interaction gaps above are accepted as known.

**Not FAIL** — feature is present and instrumented.  
**Not unconditional PASS** — reviewer-facing dismiss surprise + incomplete carousel→agg highlight consistency.

---

## Stop for owner approval

No code changes in this acceptance pass. No homepage deployment. No Flutter work.
