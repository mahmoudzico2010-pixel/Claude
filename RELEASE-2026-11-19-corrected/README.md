# Wadi El Rayan — corrected cumulative release (catalogue Phase 1 + 2 + review fixes)

Files: `wadi-cumulative-corrected.zip` (all changed files since the 2026-11-16 update, no tests, same paths as the site root), `photo-mapping.csv`, `TEST-REPORT.md`, `wadi-source.bundle` (git history with all tests and `scripts/`), `screenshots/`.
Prerequisite: the 2026-11-16 update is installed (frames, hierarchy). Nothing here touches production by itself.

## 0. Backups — OUTSIDE the domain's document root (both paths)
Never write a dump or a media copy into `public_html`/`public/`: anything there can be downloaded by URL.
```
# pick a directory above the web root, e.g. /home/CPANELUSER/backups (NOT /home/CPANELUSER/public_html/...)
mkdir -p /home/CPANELUSER/backups && chmod 700 /home/CPANELUSER/backups
mysqldump --single-transaction -u DBUSER -p DBNAME > /home/CPANELUSER/backups/wadi-before-$(date +%F-%H%M).sql
tar czf /home/CPANELUSER/backups/wadi-media-before-$(date +%F-%H%M).tgz -C /home/CPANELUSER/APPDIR storage/app/public
chmod 600 /home/CPANELUSER/backups/*
```
cPanel "Backup" → "Download a MySQL Database Backup" also works; download it to your computer and delete it from the server afterwards. Verify the dump (`tail -n 3 file.sql` ends with "Dump completed").

## 1. Know which path you are on
`php artisan migrate:status` — if `2026_11_17_000100_catalog_import_keys_and_media_review` is **Ran** you have Phase 1 (Path A); if it is **Pending** you have neither (Path B).

### Path A — Phase 1 already installed
1. Unzip over the site root.  2. `php artisan migrate --force` (runs only 2026_11_18 and 2026_11_19).  3. `php artisan catalog:import-59 --apply --photos=/path/to/WebP-folder` (optional, repeatable; it refuses with a clear message if you forget step 2).  4. `php artisan optimize:clear`.
### Path B — neither phase installed
1. Unzip.  2. `php artisan migrate --force` (runs 2026_11_17, 2026_11_18, 2026_11_19).  3. `php artisan catalogue:hierarchy`.  4. dry run: `php artisan catalog:import-59 --photos=...` (writes nothing).  5. `php artisan catalog:import-59 --apply --photos=...`.  6. `php artisan optimize:clear`.

## 2. Rehearse on a disposable database first
`scripts/rehearse-install-paths.sh sqlite` (any machine) or `scripts/rehearse-install-paths.sh mysql EMPTY_DISPOSABLE_DB USER` on staging. It builds the 2026-11-16 baseline in the throw-away DB, then runs both paths, dry run, first apply, repeat import and an engine rollback. It never reads or writes the `.env` database. See TEST-REPORT.md: the SQLite rehearsal PASSED; the MySQL 5.7 run is NOT RUN (no MySQL 5.7 available to me).

## 3. Rolling back — no fixed command is safe for both paths
- **Primary rollback = restore the backup** (database dump + previous files). It is correct on both paths.
- Content only: Products → Bulk edit… → Recent bulk edits → Roll back (per-product permission is checked for each reversal; denied/edited rows are reported and left alone). Photos: Photo review → Undo.
- Schema only if you must: run `php artisan migrate:status`, note which of 2026_11_17 / 18 / 19 show Ran in the LATEST batch, then `php artisan migrate:rollback --pretend` to see what it would do before running it without `--pretend`. Path A's batch contains only 18+19; Path B's contains 17+18+19. Never use a hard-coded `--step`.

## 4. What changed in this corrected release (your five findings)
1. **Re-import keeps manual photo decisions.** The importer only removes a photo link if it created it (`product_images.source='import'`) and nobody changed it afterwards; any other link (manual, bulk, unknown/Phase-1) is kept and flagged in Photo review with a note. A kept Guindillas link, primary or gallery, survives repeated imports (tested twice).
2. **Rollback and photo review authorise per product.** Each reversal checks the inverse operation (publish for status, delete/restore for trash, update for edits) on that product; denied rows are listed and untouched; the run stays open. Same checks for assign/undo/reorder/make-primary.
3. **Apply honours the preview.** If a product's planned change differs at Apply time, nothing is written, the conflicting products are listed with what changed, and a fresh preview is required. A real-browser test drives Filament's "Select all N", unticks a row on page 2, applies, and checks the excluded row is untouched.
4. **Install paths rehearsed** (SQLite disposable DB: PASS both paths). **MySQL 5.7: NOT RUN** — exact staging steps are in TEST-REPORT.md.
5. **Admin workflow.** Bulk editor and Photo review are in Arabic and English (RTL tested on a 390 px phone, no horizontal overflow). One primary per product is enforced in the model; with several photos ticked you choose which one is primary and the rest go to the gallery. The three Guindillas photos remain in the queue for your decision. Human-written alt text is kept; photos outside the 41 inspected ones get the plain product name, never an assumed scene.
