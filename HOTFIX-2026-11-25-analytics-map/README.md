# Hotfix — Analytics "Countries" world map (broken-image icon) + country source check

Staging/test material only; nothing was deployed. **Prerequisite:** the analytics + navigation update (`UPDATE-2026-11-24-analytics-nav`) is installed. No migration, no npm, no composer.

## 1. Root cause (found and reproduced)
The map was drawn as an SVG `<image>` that loaded a separate 808 KB file from `/vendor/world-map/world.svg` (a file inside the public web folder, with an absolute `asset()` URL). If that URL does not return the SVG — file not uploaded into the real web root (`public_html`), uploaded into a different folder than the web root, a host/.htaccess rule that blocks or rewrites `/vendor/…`, or an `APP_URL` that differs from the address you browse — the browser shows its **broken-image icon**, scaled up to the whole map area, exactly as in your screenshot.
**Reproduced here:** with the file missing, the old code requests `/vendor/world-map/world.svg`, receives `404`, and renders your screenshot (`screenshots/1-BEFORE-*`). I could not look at your live server, so *which* of the causes above applies on your hosting is not known — the fix removes all of them.

## 2. The fix (verified in a browser, from the packaged files only)
* The outline is now produced by the application at **`/_wr/world-map.svg`** from `database/data/world-map.json` (already shipped with the analytics update and already used to colour countries). It needs **no file in the public folder**, uses a **relative URL** (no `APP_URL` dependency), is cached (ETag, 7 days, ≈140 KB gzipped) and is not affected by `/vendor/` blocking.
* If the outline ever fails to load, a short message ("The map outline could not be loaded. The table below has the same data.") replaces the broken icon.
* **No identifiable countries** → a neutral world map plus the message *"No session in this period has an identifiable country, so no country is shaded…"* (English/Arabic), and the table still shows "Unknown / unavailable".
* Also fixed in the same area: on a phone the Countries card was wider than the screen and the map was cut off; report cards are now contained to the screen width.
* New: **Tracking settings → Country source check** (admins with `analytics.configure`) shows what the country lookup can see on that request and says in words what is missing. It never shows or stores an IP address.

Verification: screenshots `screenshots/` — before (broken), after on desktop and 390 px phone **with the public `world.svg` deliberately absent**, empty state in English and Arabic, and the source check. Automated: `AnalyticsMapHotfixTest` (4 tests) + the existing analytics/sidebar/authorization tests (51 passed). Install rehearsal: this ZIP applied over a copy of the analytics update. All screenshots use synthetic test data and carry a banner saying so.

## 3. Why the table says "Unknown / unavailable" (separate issue)
This is **expected until a country source is set up**; it is not caused by the map. Country is taken only from (a) a country header (e.g. `CF-IPCountry`) that arrives **through a proxy/CDN listed in `TRUSTED_PROXIES`**, or (b) a local MaxMind database file. A header sent directly by a visitor is ignored, and nothing is ever guessed or invented (verified by test). On ordinary HostGator shared hosting neither exists by default, so every visit is Unknown. The new check will say `no source` in that case. To get countries, choose one:

**A. Cloudflare (free plan is enough)** — put the domain behind Cloudflare (orange cloud), keep *Network → IP Geolocation* **on**. In `.env` set `TRUSTED_PROXIES=` to Cloudflare's published ranges (copy the lists from https://www.cloudflare.com/ips-v4 and /ips-v6, comma-separated; explicit IPs/CIDRs only; re-check them from time to time). Then `php artisan optimize:clear`. Open *Tracking settings → Country source check* from a normal browser session: it should say a trusted proxy was matched and the `CF-IPCountry` header is present.

**B. Local MaxMind GeoLite2-Country database** — create a free MaxMind account, download `GeoLite2-Country.mmdb`, upload it to `storage/app/geoip/` and install the reader library with `composer require maxmind-db/reader` (needs Composer on a machine you control; upload the resulting `vendor/` changes). *Not provided in this hotfix; not tested here.* The check shows "file: Yes / reader: Yes" when both are present.

Existing records are not rewritten: sessions already stored as Unknown stay Unknown (no invented history). Only new visits get a country once a source works.

## 4. Install (exact steps)
1. **Backup** the site folder (and DB — this hotfix does not touch the database).
2. Upload and unzip `wadi-analytics-map-hotfix.zip` over the site root (same paths as the project; `routes/web.php` is replaced — if you edited it yourself, merge the single `analytics.map` route instead).
3. Terminal: `php artisan optimize:clear` (clears the route cache so `/_wr/world-map.svg` is found). **Do not** run migrations.
4. Optional cleanup: delete `public/vendor/world-map/world.svg` (no longer used; the `LICENSE.md`/`NOTICE.md` beside it must stay — the map is CC BY 4.0).
5. Hard-refresh *Analytics → Audience → Countries* (Ctrl+F5). Open `https://YOUR-DOMAIN/_wr/world-map.svg` directly: you should see a grey world map.

## 5. What is verified vs. what still needs your hosting
| Verified here (SQLite, Chromium, synthetic data) | Needs testing on your hosting |
|---|---|
| Root cause reproduced; broken icon gone with the file absent | Open `/_wr/world-map.svg` on the live domain — it must return the map (if it doesn't, send me the HTTP status or what you see) |
| Map on desktop and 390 px phone; empty state English/Arabic; table parity | That your host/CDN does not cache or block the `/_wr/` path (the analytics beacon at `/_wr/a` uses the same prefix) |
| Source check wording; client-sent header ignored; header from a listed proxy accepted | Which country option (A/B) you will use; whether Cloudflare's header arrives and the check turns green |
| 51 related tests pass | MySQL 5.7 — **NOT RUN** (no schema change in this hotfix) |

Rollback: restore the backed-up files and run `php artisan optimize:clear` (restores the previous `world.svg` map only if you did not delete that file in step 4).
