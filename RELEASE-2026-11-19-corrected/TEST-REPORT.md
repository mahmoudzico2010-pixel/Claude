# Test report — corrected cumulative release

## Newly verified in this release (all PASS)
| Finding | Test | Result |
|---|---|---|
| 1 Re-import keeps manual photo links | CatalogImport59Test::manual_guindilla_photo_decisions_survive_re_imports… (manual PRIMARY + manual GALLERY + unknown-provenance link, run twice; untouched importer link removed, edited importer link kept; 3 kept links flagged) | PASS |
| 2 Rollback authorisation | BulkProductEditorTest::rollback_authorises_each_inverse_operation… (editor with view+update only: restore and un-publish denied and untouched, text/attribute edit reversed, run stays open, admin finishes it) | PASS |
| 2 Photo-review authorisation | PhotoReviewQueueTest::an_editor_without_permission… (assign refused, nothing written) | PASS |
| 3 Apply honours preview | BulkProductEditorUiTest::apply_honours_the_preview… (two editors, same field: nothing written, conflict listed, fresh preview required, then applies) | PASS |
| 3 Real "Select all matching" | tests/Browser/bulk-select-all.e2e.mjs in Chromium against the local site: "Select all 25" across pages, untick one row on page 2 → 24 selected, preview 24 changes, apply → exactly 24 changed, excluded row untouched, no other product changed | 8/8 PASS |
| 4 Install paths | scripts/rehearse-install-paths.sh sqlite (log: rehearsal-sqlite.log): Path B (dry run writes nothing, first apply 55 created + 4 reconciled, repeat 0 created, engine rollback) and Path A (Phase-1-shaped DB, importer refuses until migrated, migrate runs only 2 new migrations, repeat import no-op) | PASS on SQLite |
| 5 One primary per product | PhotoReviewQueueTest (model hook; multi-select with chosen primary, rest gallery; primary must be among the selected) | PASS |
| 5 Arabic RTL, phone | 390×844 browser: dir=rtl lang=ar, Bulk edit and Photo review both fully Arabic, no horizontal overflow (screenshots) | PASS (manual browser check, not an automated assertion) |
| 5 Alt text | queue assignment keeps human alt, gives plain product name (no scene) to unverified photos | PASS |
Also re-run and passing: BulkProductEditor (9), Bulk editor UI (4), Photo review (6), Import (4), Storefront 590 pages (2), hierarchy, frames, builder, site chrome, catalogue admin, strict-authorization matrix.

## NOT RUN
- **MySQL 5.7 on staging with PHP 8.3: NOT RUN.** This environment has PHP 8.3 but no MySQL server, no Docker daemon and no route to download one. The SQLite rehearsal does not prove MySQL-specific behaviour (JSON columns, strict mode, utf8mb4 index lengths).
  Exact staging steps: create an EMPTY disposable schema (`CREATE DATABASE wadi_rehearsal CHARACTER SET utf8mb4;`) and a user limited to it on a MySQL 5.7 server; copy the release to a scratch directory (not the live site); then
  `PHOTOS=/path/to/WebP scripts/rehearse-install-paths.sh mysql wadi_rehearsal USER` (it asks for the password, never reads the site's `.env` database) and keep the output. Expect both paths to end with "REHEARSAL DONE (mysql)" and `products with import_key: 59`.
- Arabic/Urdu admin on a real device; the new admin screens in the other 8 languages (admin strings exist in en/ar only).

## Pre-existing failures (fail identically on the original code in this sandbox)
QuoteRequestTest 7, LanguageActivationTest 3, InstallPackageTest 1, ContactMessageTest 1, CatalogQuoteTest 1 (e.g. e-mail DNS validation, audit-log rows in a fresh install check). NetworkDiagnosticTest fails only inside the full-suite run (passes alone, same as on the original code).
Nothing new fails in the full suite after this release.
