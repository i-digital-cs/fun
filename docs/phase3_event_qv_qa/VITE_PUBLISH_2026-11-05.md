# Vite publish — centered Event Quick View (2026-11-05)

## Verdict: PASS

## Backup
`/home/d14098/backups/funnity-public-build-pre-qv-centered-20261105T085235Z`  
Byte-identical to pre-build `public/build`; restore simulation OK.

Rollback:
```bash
rm -rf /home/d14098/funnity.com/public/build
cp -a /home/d14098/backups/funnity-public-build-pre-qv-centered-20261105T085235Z \
      /home/d14098/funnity.com/public/build
```

## Build
```bash
nvm use 20.20.2 && npm run build   # exit 0, ~1.8s
```

Published:
- `public/build/assets/app-BsA9lgJm.js`
- `public/build/assets/app-DapBPVt2.css`
- manifest entries present; no missing files; `funEventQv` + `980px` confirmed in bundles

## Production checks (no injected CSS/JS)
All 12 scenarios passed; Home/Discover/Dashboard/Map/Login HTTP 200 with new assets.  
Map/list pin sync: **NOT VERIFIED** (out of scope claim).
