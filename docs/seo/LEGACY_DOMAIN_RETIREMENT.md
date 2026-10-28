# Legacy domain retirement

Date: 17 October 2026.

**Verdict: PASS**

`fun.idigital-cs.com` and `www.fun.idigital-cs.com` no longer serve the old application. `https://funnity.com` was not moved, and its database was not changed.

## 1. Document roots

| Host | Document root | What it is |
| --- | --- | --- |
| `fun.idigital-cs.com`, `www.fun.idigital-cs.com` | `/home/d14098/fun.idigital-cs.com` | Separate cPanel vhost. The files were a Node/Express catalog named Bloom Compare (`cosmetics-compare`) with SQLite `products.db`. They were not a checkout of the Funnity Laravel app. |
| `funnity.com`, `www.funnity.com` | `/home/d14098/funnity.com` | Production Laravel application. The cPanel file for this vhost is named `funnity.com.idigital-cs.com`. It was not edited. |

The two trees had different inodes. The legacy tree had no symlinks. Production’s only app symlink is `public/storage` → `storage/app/public` inside `/home/d14098/funnity.com`.

## 2. Dependency findings

| Resource | Legacy | Production | Shared |
| --- | --- | --- | --- |
| Database | SQLite files under the legacy tree only (`data/products.db` and copies under `public/data` and `App/data`) | MySQL database `d14098_fun` on `127.0.0.1` | No |
| Environment file | None. Connection is a filesystem path in `db.js` | `/home/d14098/funnity.com/.env` | No |
| Cache and queue | None | Laravel `database` cache and `database` queue | No Redis namespace |
| Storage | `uploads/` inside the legacy tree | `storage/` inside the production tree | No |
| Cron | No crontab line for this host | No crontab line for Funnity | No |
| Queue worker | None | None for Funnity | No |
| Other workers | — | A Souqnest `queue:work` process was already running. It was not stopped. | Not this site |
| Git | Own `.git` in the legacy tree | Own repository in `/home/d14098/funnity.com` | No |
| SSL | Let’s Encrypt certificate for `fun.idigital-cs.com` and `www.fun.idigital-cs.com`, valid through 20 December 2026 | Production certificate, untouched | No |
| DNS | Left in place so the redirect can answer | Untouched | — |

No shared or uncertain dependency with production was found, so retirement continued.

## 3. Backup

`/home/d14098/backups/fun.idigital-cs.com-20261017/`

| File | Purpose |
| --- | --- |
| `fun.idigital-cs.com.tar.gz` | 58,950,738 bytes, sha256 `3b7c9b0d99304036c2a40f4193b5bf6c2eb101df9d6199b8c11fc8009180e96f`. `gzip -t` passed. The listing includes `.htaccess`, `db.js`, `package.json`, and `products.db`. |
| `archived-docroot/` | The live tree after the backup, moved out of the web root. `data/products.db` passes `PRAGMA integrity_check` (`ok`). |
| `htaccess-original.txt` | The catalog rewrite rules from before the redirect. |
| `vhost-fun.idigital-cs.com.txt` and `vhost-fun.idigital-cs.com_SSL.txt` | Original document root, server name, and alias. |
| `crontab-before.txt` | Crontab before the change. It matches the crontab after the change. |
| `production-counters-before.txt` | 555 events, event 503 `completed`, 500 demo rows hidden from search. Those counts were the same after retirement. |

No database was dropped. The production database was not written.

## 4. Redirect implementation

Only `/home/d14098/fun.idigital-cs.com/.htaccess` was changed. Production Laravel and the production `.htaccess` were not used for this host.

| Request | Response |
| --- | --- |
| `/` and `/index.html` | 301 `https://funnity.com/` |
| `/events`, `/safety`, `/privacy-policy`, `/terms`, `/support`, `/account-deletion`, `/sitemap.xml`, `/robots.txt`, including a trailing path | 301 to the same path on `https://funnity.com`. Query string kept (`/events?q=1` → `https://funnity.com/events?q=1`). |
| Catalog and development URLs (`/product.html`, `/api/products`, `/css/styles.css`, `/data/products.db`, `/server.js`) | 410. Those pages do not exist on Funnity, so they are not sent to the homepage. |
| `http://` and `https://www` | One hop to `https://funnity.com/...`. Followed redirects: 1 hop, then 200. |

HTTPS on the legacy name uses the existing certificate. The certificate was not removed. DNS was not removed.

## 5. Processes disabled

There was no legacy cron entry, Supervisor unit, systemd unit, or `node server.js` process to stop. None were added. The Souqnest queue worker was left running. Funnity still has no cron line and no queue worker in this account.

## 6. Files archived

The document root now contains only the redirect `.htaccess`.

Everything else, including `.git`, `node_modules`, SQLite databases, uploads, and the original application, is in `archived-docroot/`. The tar archive is a second copy. Neither was deleted.

## 7. Production verification

| Check | Result |
| --- | --- |
| `https://funnity.com/` | 200 |
| `https://funnity.com/login` | 200. A POST without a token returns 419 from the production app, not a redirect. |
| `https://funnity.com/events/530/hgz-kor-khmasy` | 200 |
| `https://funnity.com/api/events/530` | 200 |
| `https://funnity.com/storage/media/branding/funity-mark.svg` | 200, `image/svg+xml` |
| Event rows | Still 555. Event 503 still `completed`. Demo rows 1–500 still `search_indexable = 0`. |
| Crontab | Unchanged |
| Production document root | Still `/home/d14098/funnity.com` |

## 8. Rollback

1. Move `/home/d14098/backups/fun.idigital-cs.com-20261017/archived-docroot/*` back into `/home/d14098/fun.idigital-cs.com/`.
2. Restore `.htaccess` from `htaccess-original.txt` in that same backup directory.
3. Leave DNS and the certificate as they are.

Alternatively, extract `fun.idigital-cs.com.tar.gz` over the document root. That archive is the pre-change tree, including the original `.htaccess`.

Do not delete the backup directory as part of rollback.
