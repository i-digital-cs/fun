# Funnity Admin V3 — Design Proposal

**Status: AWAITING OWNER APPROVAL**  
**Phase: Design-only prototype (no production implementation)**

---

## 1. Executive summary

Admin Dashboard V2.1 is operationally useful but visually generic. The owner rejected its direction: repetitive white cards, weak hierarchy, and little Funnity personality.

**Admin V3** proposes a new visual language — **Editorial Command Center** — that keeps the same information architecture and analytics semantics while redesigning composition, typography, surfaces, and chart treatment.

A high-fidelity interactive prototype lives at:

`design-prototypes/admin-v3/`

It uses **fixture data only**, requires no auth, and does not replace V2.1.

---

## 2. V2.1 visual audit

| Weakness | Observation |
|----------|-------------|
| Generic identity | Reads as a conventional Laravel/SaaS admin kit |
| Card monotony | Same white / border / radius / weight everywhere |
| Weak hierarchy | KPIs and Action Center compete equally |
| Mechanical layout | Stacked widgets without editorial flow |
| Default charts | Chart.js defaults with little brand expression |
| Typography | Secondary labels and Arabic density feel small |
| Rhythm | Long scroll of similar blocks; uneven density |

V2.1 fixed spacing and some hierarchy relative to V2, but did not establish a distinctive product surface.

---

## 3. Three design directions

### Direction A — Editorial Command Center

- Strong typography and asymmetric grids  
- Featured insight surface + varied supporting metrics  
- Compact secondary strips; queue-style Action Center  
- **Pros:** Distinctive, scannable, premium  
- **Cons:** Needs discipline so asymmetry does not feel chaotic  
- **Fit:** Excellent for a brand-forward ops product

### Direction B — Modern Product Intelligence

- Dense analytics, chart-first, minimal decoration  
- **Pros:** Efficient for power users  
- **Cons:** Can feel cold; less Funnity personality  
- **Fit:** Strong for pure BI; weaker for social/events brand

### Direction C — Social Operations Hub

- Activity-led, warmer component styling, social emphasis  
- **Pros:** Brand-friendly, human  
- **Cons:** Risk of looking like a social feed; less “command”  
- **Fit:** Good accents; weaker as the sole system

---

## 4. Selected direction

**Recommended: Direction A — Editorial Command Center**  
with restrained personality accents from C (warm orange CTAs, softer purple rails, friendly but professional copy).

Rationale:

1. Breaks the identical-card pattern V2.1 was rejected for.  
2. Makes Action Center and lifetime membership visually undeniable.  
3. Still operational and truthful to existing metrics.  
4. Supports EN/AR + RTL without dark-mode gimmicks.

---

## 5. Design system (prototype tokens)

### Color

| Token | Value | Use |
|-------|-------|-----|
| Primary | `#7138F5` | Brand, active nav, charts |
| Accent | `#FF762E` | Refresh / emphasis |
| Background | `#F8F9FE` | Page canvas |
| Ink | `#19213D` | Primary text |
| Surfaces | white + tinted purple/orange softs | Variation without chaos |
| Semantic | danger / warning / info / success softs | Action tiers |

### Typography

- Arabic: **Tajawal** (local in prototype `assets/fonts/`)  
- English: **IBM Plex Sans** (Bunny for prototype preview)  
- Large featured numerals; section titles ~1.2rem; body ~15px

### Spacing / radius / motion

- Scale: 4 / 8 / 12 / 16 / 24 / 32 / 48  
- Radii: 8 / 14 / 22 / pill  
- Transitions ~180ms; respects `prefers-reduced-motion`

### Components

Featured KPI, accent/ink/standard KPI, metric strip, action queue board, chart panel, activity row, data table, status pills, geo rank bars, health cards, segmented period control, sidebar nav + collapse.

---

## 6. Information architecture

Same capabilities as V2.1 — new composition:

1. Prototype banner (fixture disclaimer)  
2. Command header + period panel  
3. Executive intelligence (featured + varied KPIs + strip)  
4. Action Center (aside + priority rows)  
5. Events intelligence (trend + status mix)  
6. Activity stream (8 fixture rows)  
7. Recent events table  
8. Social intelligence (bento)  
9. Geographic intelligence (ranked bars)  
10. System health (failed jobs + honest monitoring gap)

**Metrics used are only those supported by current analytics shape** (users, events, participation, friendships, message *counts*, failed jobs, city event counts). No invented revenue/retention/uptime.

---

## 7. Prototype structure

```text
design-prototypes/admin-v3/
├── index.html
├── README.md
├── assets/fonts/Tajawal-*.ttf
├── css/{tokens,shell,dashboard}.css
├── js/{i18n,charts,app}.js
└── data/fixtures.json
```

### Local preview

```bash
cd design-prototypes/admin-v3
php -S 127.0.0.1:8766
```

Open http://127.0.0.1:8766/

---

## 8. Interaction behavior

| Interaction | Behavior |
|-------------|----------|
| Period Today / 7d / 30d / 90d | Swaps fixture KPI + chart series |
| Language | EN ↔ AR; `dir=rtl` / `ltr` |
| Sidebar collapse | Desktop icon rail |
| Mobile drawer | ≤1024px hamburger + backdrop |
| Refresh | Updates timestamp; still fixtures |
| Charts | Hover tooltips; reduced-motion aware |
| Nav items | Demo placeholders (non-routing) |

---

## 9. Responsive behavior

| Viewport | Composition |
|----------|-------------|
| 1440 | Full editorial grid + sidebar |
| 1280–1024 | Same; tighter gutters |
| 768 | Single column; drawer nav |
| 390 / 360 | Stacked intel; action rows reflow; bottom mobile links |

Verified via Playwright screenshots (see §11).

---

## 10. Accessibility

- Semantic landmarks / headings  
- Focus-visible rings  
- Skip link  
- Segmented controls with `aria-pressed`  
- Chart `aria-label`s  
- Touch targets ≥40px on controls  
- Contrast: purple/orange on light, white text on featured purple  
- RTL reading order via `dir`  
- `prefers-reduced-motion` disables Chart animation  

**Limitations:** prototype charts are canvas (not fully SR-tabled); demo nav buttons are inert by design.

---

## 11. Screenshots

Directory: `docs/admin/admin-v3-design-review/`

| File | Content |
|------|---------|
| `01-english-desktop-1440.png` | EN desktop full |
| `02-arabic-desktop-1440.png` | AR desktop full (RTL) |
| `03-english-mobile-390.png` | EN mobile |
| `04-arabic-mobile-390.png` | AR mobile |
| `05-tablet-768.png` | Tablet |
| `06-executive-kpi.png` | Featured KPI composition |
| `07-action-center.png` | Action Center |
| `08-events-analytics.png` | Events section |
| `09a-sidebar-expanded.png` | Sidebar expanded |
| `09b-sidebar-collapsed.png` | Sidebar collapsed |
| `10-side-by-side-v21-vs-v3.png` | V2.1 vs V3 top-of-page compare |

---

## 12. Implementation roadmap (not started)

After **explicit owner approval** only:

1. Map prototype tokens into `DESIGN.md` / admin CSS layer (without tearing member surfaces).  
2. Port shell refinements to `layouts/admin` + sidebar components.  
3. Rebuild dashboard Blade sections to match V3 composition while binding **live** `DashboardAnalyticsService` payload.  
4. Keep Chart.js but apply V3 chart options; keep privacy / period / auth semantics.  
5. EN/AR lang keys parity; RTL QA.  
6. Automated tests + authenticated browser QA.  
7. No Flutter changes unless member APIs change (they should not).

**Do not implement until approved.**

---

## Approval

**AWAITING OWNER APPROVAL**

Prototype and design review delivered. Production Admin V2.1, analytics backend, database, Vite production config, and Flutter remain the live system of record until a separate implementation task is authorized.
