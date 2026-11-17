# Test report — analytics + admin navigation update (what really ran)

Environment: sandbox Linux, PHP 8.3, Laravel 13.34, Filament 5.8, Livewire 4, **SQLite**; Chromium/Playwright 1.x. **MySQL 5.7: NOT RUN.** No production or live host was touched.

## Automated tests
| Run | Result |
|---|---|
| Full suite on the change branch | **1333 passed, 10 skipped, 20 failed** (25 212 assertions) |
| The same 20 failing tests on the *previous release* (About+Team, no changes of mine) | **the same 20 fail** → this update adds **0** failures. They are sandbox-environment failures (no DNS for the RFQ e-mail check, network diagnostics, trusted-proxy defaults, language-activation fixtures): `QuoteRequestTest` ×7, `NetworkDiagnosticTest` ×6, `LanguageActivationTest` ×3, `CatalogQuoteTest`, `ContactMessageTest`, `InstallPackageTest`, `TrustedProxyTest` ×1 each. (An earlier run showed 19; the 20th is the same family and fails identically on the old code.) |
| New/changed tests only (Analytics*, AdminSidebar, ArabicShaper, FilamentStrictAuthorization, Navigation) | 76 passed |
| **Install rehearsal**: the shipped ZIPs unzipped over a clean copy of the previous release, `migrate` on an empty SQLite DB, routes registered, new tests run there | migrations DONE, 7 analytics routes, 49 passed |

Covered by tests: no recording before consent / after refusal; consented visit stores only safe aggregates; country only from a trusted proxy, Unknown otherwise; missing referrer = Direct; own-host referrer ignored; bots / staff / staff preview / prefetch / maintenance excluded; tampered page context rejected; duplicate events, reloads, retries; stable-ID product and pack views; browsing vs click vs submission; engaged time capped, overlapping tabs not added, unknown stays unknown; session timeout; Cairo day fixed at ingestion incl. DST; totals/chart/table/funnel reconcile; filters consistent; ten storefront languages aggregate to one product; roll-ups idempotent, uniques never summed; retention prunes only after aggregation; age unavailable by default and small cohorts withheld; compare-with-previous; CSV equals report tables (UTF-8 BOM, Arabic); PDF renders in English and Arabic; Arabic shaper; analyst can view but not export/configure (forged calls refused, no audit row); exports and settings audited; presets private; sidebar: dashboard standalone, six groups in the briefed order, Arabic labels with no raw keys, one canonical location per screen, no empty groups, unauthorised direct URLs refused, old URLs still open.

## Browser acceptance (Chromium on a disposable staging DB, synthetic data)
* Consented visit from a controlled Spain test source (trusted-proxy header): olive product opened → RFQ clicked → form started → submitted → success page **reloaded** → **one** saved request, one `rfq_submit` event. A second **direct** RFQ and a **multi-product** RFQ (two lines) were also saved: 3 requests ⇒ 3 events, correct product lines. A visitor who **refused consent** recorded nothing.
* Not run in a browser: the *packaging catalogue public page* (cannot be published here; covered only by the stable-ID unit test); a real 2-minute hidden-tab and real two-tab run (covered by the heartbeat/overlap unit tests with simulated intervals, not by a stopwatch session); real CDN header; real search/social referrers; GA4 age provider (no connector shipped).
* The sandbox has no DNS, so for the demo only, the RFQ e-mail DNS check was temporarily relaxed and **reverted** before packaging (shipped `StoreQuoteRequest.php` is unchanged).

## Visual checks (screenshots in `screenshots/`)
* Reports: English desktop, Arabic RTL desktop, 390 px phone in English and Arabic, each with a "TEST DATA" banner. Defects found and fixed during review: country filter showed "Unknown" instead of "All"; narrow Overview cards; scrambled ratio cells such as "6.4% (15 / 236)" in RTL.
* PDF: real exports in Arabic and English rendered to PNG (`screenshots/pdf/`). The PDF contains tables and charts-as-tables; map is not drawn in the PDF.
* Sidebar before/after: English and Arabic, desktop 1440 px and phone 390 px drawer, plus a 1100 px icon-rail view. "Before" was captured from the real previous release.
* Not separately tested: keyboard operation of the mobile drawer (Filament default behaviour is used), screen-reader announcements of the icon rail (Filament tooltips/aria are used).

## Notes
* Tests were written for SQLite; MySQL 5.7 strict-mode behaviour is **NOT RUN** — run `php artisan migrate --pretend` and the new tests on staging MySQL.
* Previously delivered ZIPs were not modified.
