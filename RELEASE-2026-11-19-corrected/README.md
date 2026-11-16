# Wadi El Rayan — corrected cumulative release (catalogue Phase 1 + 2 + review fixes)

Files: `wadi-cumulative-corrected.zip` (ONE ZIP: all changed files since the 2026-11-16 update, no tests, same paths as the site root — including the 59-product payload `database/data/catalog/products-59.json`, the 124 supplied WebP photographs in `database/data/catalog/photos/` with `SHA256SUMS`, and `database/data/catalog/photo-mapping.csv`), `photo-mapping.csv`, `TEST-REPORT.md`, `wadi-source.bundle` (git history with all tests and `scripts/`), `screenshots/`.
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
1. Unzip over the site root.  2. `php artisan migrate --force` (runs only 2026_11_18 and 2026_11_19).  3. `php artisan catalog:import-59 --apply` (uses the bundled photographs by itself, no `--photos` path; repeatable; it refuses with a clear message if you forget step 2).  4. `php artisan optimize:clear`.
### Path B — neither phase installed
1. Unzip.  2. `php artisan migrate --force` (runs 2026_11_17, 2026_11_18, 2026_11_19).  3. `php artisan catalogue:hierarchy`.  4. dry run: `php artisan catalog:import-59` (writes nothing; uses the bundled photographs).  5. `php artisan catalog:import-59 --apply`.  6. `php artisan optimize:clear`.

## 2. Rehearse on a disposable database first
`scripts/rehearse-install-paths.sh sqlite` (any machine) or `scripts/rehearse-install-paths.sh mysql EMPTY_DISPOSABLE_DB USER` on staging. It builds the 2026-11-16 baseline in the throw-away DB, then runs both paths, dry run, first apply, repeat import and an engine rollback, and it ends with `REHEARSAL PASSED` only if every check passed (exactly 59 products, dry run writes nothing, repeat import leaves every imported table identical, rollback restores the original values) — otherwise it exits non-zero with `REHEARSAL FAILED: …`.
Target safety: before anything is reset it asks Laravel itself which connection/database it would use and requires that to equal what you asked for; for MySQL the database must not be the `.env` site database, its name must contain `rehearsal` or `disposable`, it must contain ZERO tables, and you must confirm with `REHEARSAL_CONFIRM_DB=<name>`. A marker table is created once the emptiness check passes and is re-verified, together with the effective database name, before every reset (`db:wipe`, never `migrate:fresh`). See TEST-REPORT.md: the SQLite rehearsal PASSED; the MySQL 5.7 run is NOT RUN (no MySQL 5.7 available to me).

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

## Photographs are bundled (latest repackaging)
The importer reads `database/data/catalog/photos/` (124 WebP files, byte-identical to the ZIP you supplied; `SHA256SUMS` lets you verify) unless you pass `--photos=/other/folder` or `--no-photos`. Only name-confirmed matches are linked (41 photos → 12 primary images, 29 gallery links); the other 83 stay in **Catalogue → Photo review** (74 numbered BEKO files, 6 group/combined shots, 3 Guindillas files whose pictures look yellow). Nothing is assigned by sequence or appearance. Re-running never duplicates media and never removes a link a person made. The CSV in the ZIP is what a fresh install produces; **Photo review → Download mapping CSV** always reflects your later decisions. The ZIP is about 11 MB: if the upload to cPanel is limited, upload it in File Manager or unzip locally and upload the folder.
