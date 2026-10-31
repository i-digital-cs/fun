# Funnity Admin V3 — Design Prototype

**Status:** Design review only · **AWAITING OWNER APPROVAL**

Isolated high-fidelity prototype for the proposed Admin Command Center visual direction.

This folder does **not** replace production Admin Dashboard V2.1.

## Preview

From the repository root:

```bash
cd design-prototypes/admin-v3
php -S 127.0.0.1:8766
```

Open: [http://127.0.0.1:8766/](http://127.0.0.1:8766/)

Or:

```bash
npx --yes serve -l 8766 .
```

## Interactions

- Reporting period: Today / 7d / 30d / 90d (fixture data)
- Language: EN ↔ AR with RTL/LTR
- Sidebar collapse (desktop) / drawer (≤1024px)
- Chart tooltips (Chart.js)
- Refresh updates the timestamp (still fixture data)

## Safety

- No Laravel routes, controllers, services, or Vite entries modified
- No production database or authenticated APIs
- Fixture data only (`data/fixtures.json`)
- Do not deploy or publish without approval
