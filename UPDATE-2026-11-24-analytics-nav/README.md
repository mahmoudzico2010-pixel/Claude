# Wadi El Rayan — Visitor analytics, reports and the reorganised admin sidebar (staging only, nothing deployed)

**Prerequisite:** the *About + Team* update (2026-11-23, commit `563d132`) must already be installed. This ZIP is incremental on top of it and replaces **no** earlier ZIP. The full cumulative release (Delivery B) is a separate, later milestone and will include this update.

## What is in it
| Area | What you get |
|---|---|
| **Visitor analytics (first-party, consent-aware)** | A small script (`public/js/wr-analytics.js`) sends page views, product views, RFQ clicks/starts and heartbeats to `POST /_wr/a`. Nothing is stored before the visitor accepts measurement; a refusal or withdrawal stops it. No cookies from third parties, no fingerprinting, no raw IP, no full user-agent, no full referrer, no form values. |
| **Reports** (*Analytics & Reports* in the sidebar) | **Overview** (trend, live panel, cards), **Audience** (tabs: Countries · Languages · Browsers & devices · Age groups), **Pages & Products** (pages, products, categories, packs), **Acquisition & Conversions** (sources, referrers, campaigns, funnel), **Saved reports**. Every chart has a "Table of the same data" with the same filters and reconciled totals. |
| **Filters** | Period (today, yesterday, 7/30/90 days, custom), compare with previous period, country, site language, device, source, product, category, pack format, page. |
| **Exports** | UTF-8 CSV with BOM (opens correctly in Excel, Arabic included) and PDF in Arabic or English (own Arabic shaping, local DejaVu font, no web fonts), plus Print. Each export is written to the audit log. |
| **Tracking settings** | *System Settings → Visitor analytics settings*: on/off, consent required, session timeout, raw-event retention, session retention, minimum cohort, reporting timezone, trusted country headers, campaign fields. |
| **Admin sidebar** | Dashboard + 6 workflow groups (see `SIDEBAR-MAP.md` for the complete before → after list). |
| **Permissions** | `analytics.view`, `analytics.export`, `analytics.configure` (separate, enforced on the server). Admin has all three; Sales has `analytics.view`; nobody else by default. |

## Compatibility
PHP 8.3, Laravel 13.34 / Filament 5.8 / Livewire 4, MySQL 5.7 utf8mb4 strict (no JSON columns, no window functions, indexed columns kept short). **MySQL 5.7 — NOT RUN** (tests and the browser demo ran on SQLite). Run `php artisan migrate --pretend` on staging MySQL first. No Redis, queue worker, WebSockets or Node are needed at runtime; the compiled assets are the shipped files (`public/js/wr-analytics.js`, `public/vendor/world-map/*`); no `npm run build` step.

## Install (staging first)
1. **Back up** the database and the site folder.
2. Unzip `wadi-analytics-nav-update.zip` over the site (same paths as the repo).
3. `php artisan migrate --force` — two additive migrations: the analytics tables (`analytics_*`) and the permission sync (adds the three `analytics.*` permissions to existing roles; removes nothing).
4. `php artisan optimize:clear`
5. **Cron** (cPanel → Cron Jobs, once per minute — the same single line Laravel always needs):
   ```
   * * * * * cd /home/CPANEL_USER/public_html && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
   Replace `CPANEL_USER` and the folder with your real path; use the PHP 8.3 binary your host shows in *MultiPHP* (often `/usr/local/bin/ea-php83`). The scheduler then runs `analytics:aggregate --prune` at minute 7 of every hour (builds the day's roll-ups, then deletes raw rows older than the retention).
6. **Catch-up** (safe to repeat, idempotent): `php artisan analytics:aggregate --from=2026-11-01 --to=2026-11-24` (and `--prune`). `php artisan analytics:rebuild-days` rebuilds roll-ups from still-existing raw rows.
7. Open *Analytics & Reports → Overview*. It stays empty until visitors accept measurement. No history is invented: *"Measurement started"* shows the real start date.

### Country (optional but recommended)
Country is taken from a hosting/CDN header **only if the request comes from a trusted proxy** (list the proxy addresses in `.env` as `TRUSTED_PROXIES=…`; headers accepted are set in Tracking settings, e.g. `CF-IPCountry`), or from an optional local MaxMind file `storage/app/geoip/GeoLite2-Country.mmdb` if you add one and `composer require maxmind-db/reader`. Otherwise the country is **Unknown** and shown as "Unknown / unavailable". The IP is read for that lookup only and never stored.

## Rollback
* Files: restore the file backup (or re-unzip the previous release). The public site keeps working if the analytics files are removed — the storefront script is tiny, fails silently and never blocks a page.
* Database: restore the backup. `php artisan migrate:rollback --step=2` drops the analytics tables (**deletes analytics data**); the permissions rows are kept.
* To stop measuring without removing anything: *Tracking settings → Measurement enabled = off*.

## Definitions and rules (also shown beside each report)
* **Session** = one visit, ended by 30 minutes of inactivity (configurable). **Estimated unique browsers** = distinct pseudonymous browser ids among consenting visitors over the *whole selected period*; it is **never** computed by adding daily numbers, and it is not "people". A returning visitor is only an estimate.
* **Product view** = a product detail page opened. A card impression is not a view. **RFQ click / start / submitted** are three different steps; "submitted" is recorded **by the server after the request was durably saved**, once per request reference (a reload, retry or second click adds nothing).
* **Funnel** = distinct sessions that reached each step *and all previous steps*: product viewed → RFQ clicked → form started → request saved. **All saved requests** (including those that began on Contact / Request-a-Quote) are shown separately; a request is linked to products only by the lines it actually contains, and is counted once at site level. Brochure opens and mail/WhatsApp clicks are secondary actions, never sales.
* **Engaged time** estimates visible, active time (heartbeats, paused when hidden/idle, capped per interval, overlapping tabs not added). It is *not attention*. If no timing signal arrived the value is **unknown**, never zero.
* **Attribution rule**: `utm_medium` (paid / email / social) first, then the referring domain (search engine → organic, social network → social, anything else → referral). No referrer = **Direct / unknown**; a missing referrer is never turned into a source. Own-site referrers are ignored.
* **Country** = approximate location of the *connection*, not nationality, company domicile or shipping destination.
* **Age groups**: never inferred (not from IP, browser, language, browsing or products). Default "Age data unavailable". The table `analytics_age_bands` is ready to receive *aggregate* bands from a consent-aware provider (e.g. GA4); **no connector is shipped in this update**, so the report stays "unavailable" until one is added. Small cohorts (below the minimum, default 5) are withheld; ages are never joined to RFQs or individual records.
* **Reporting timezone**: Africa/Cairo (changeable). Timestamps are stored in UTC; the reporting day is fixed at ingestion with the correct daylight-saving offset.
* **Retention**: raw events 90 days (default), sessions 400 days, anonymised daily roll-ups kept (they contain counts only). Raw rows are deleted only after they have been aggregated. There is no raw visitor feed anywhere in the admin.
* **Excluded**: Filament/admin pages, signed-in staff and staff previews, maintenance pages, health checks, known bots, prefetch/prerender requests, automated tests, the separate support application.

## Known limits (honest list)
* The packaging-catalogue public page could not be published in the sandbox (its source assets are not part of this repository), so "browse a packaging page" was verified by tests (`product_and_packaging_views_use_stable_ids…`) and **not** by clicking through that page in the browser demo.
* The sandbox has no DNS, so the RFQ form's e-mail DNS check was relaxed *only in the disposable demo* to submit requests; the shipped code is unchanged.
* World map: derived from `@svg-maps/world` 2.0.0, **CC BY 4.0** (credit in `public/vendor/world-map/NOTICE.md`, licence text in `LICENSE.md`); small countries may be hard to see on the map — the ranked table is the full, sortable alternative.
* Real traffic cannot be demonstrated in a sandbox. All screenshots use **synthetic test data** (a banner says so on every report image); nothing from it is shipped. The installed site starts empty.
* Browser language is stored as a short code such as `es` or `es-ES` only (never the full header).
* MySQL 5.7 not run; the 19 known baseline test failures of the repository are unrelated (see `TEST-REPORT.md`).
