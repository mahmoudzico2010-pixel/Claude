# Wadi El Rayan — cumulative update: catalogue Phase 1 + Phase 2

Contents
- `wadi-cumulative-phase1-phase2.zip` — every changed/added file (no tests) needed for Phase 1 + Phase 2, same paths as the site root. Includes the small fixes sent since (product form, header flyout, frame fit).
- `photo-mapping.csv` — current photo decisions (matches + review status). The admin screen regenerates it at any time (button "Download mapping CSV").
- `wadi-source.bundle` — git history incl. all tests.   `screenshots/` — admin bulk editor, photo queue, Arabic/Urdu pages.

Prerequisite: the 2026-11-16 update (frames, catalogue hierarchy, page builder) is already on the site — you ran its migrations (the error log showed it).

## 0. Backup first (both paths)
```
mysqldump -u USER -p DBNAME > backup-before-phase2-$(date +%F).sql      # or cPanel > Backup > Download a MySQL Database Backup
cp -r storage/app/public storage-public-backup                             # product media (optional, additive update)
```

## Path A — Phase 1 already installed
1. Unzip `wadi-cumulative-phase1-phase2.zip` over the site root (overwrites same-path files).
2. `php artisan migrate --force`      → only the NEW migration `2026_11_18_000100_catalog_phase2` runs (alt-text table, bulk history, review columns, Azizi + Red Peppers renamed in place).
3. `php artisan catalog:import-59 --apply --photos=/path/to/unzipped-WebP-folder`   (repeatable; fills the 10-language alt text, moves the 3 "green guindillas" photos to review because the pictures show yellow peppers, adds cut names in 10 languages). Skip `--photos` if you do not have the folder; nothing else changes.
4. `php artisan optimize:clear` (and `npm run build` is NOT needed for this update).

## Path B — neither phase installed (site has the 2026-11-16 update only)
1. Unzip the zip over the site root.
2. `php artisan migrate --force`      → runs BOTH `2026_11_17_000100_catalog_import_keys_and_media_review` and `2026_11_18_000100_catalog_phase2`, in order.
3. `php artisan catalogue:hierarchy`   (families/subcategories; safe to re-run)
4. Dry run: `php artisan catalog:import-59 --photos=/path/to/WebP-folder` (writes nothing, prints counts)
5. Apply: `php artisan catalog:import-59 --apply --photos=/path/to/WebP-folder`
6. `php artisan optimize:clear`

After this you work only in the admin panel: Catalogue → Products (bulk actions), Catalogue → Photo review, Media → image → alt text in 10 languages.

## Rollback
- Content: Catalogue → Products → **Bulk edit…** → "Recent bulk edits" → **Roll back** (restores only values nobody changed since; reports the rest). Photo assignments: Photo review → select the photo → **Undo**.
- Whole update: restore the SQL backup, put back the previous files. The migrations only ADD tables/columns (media_asset_translations, bulk_edit_runs, bulk_edit_changes, extra columns); `php artisan migrate:rollback --step=2` removes them (existing alt_en/alt_ar columns were never dropped).
- Imported products are DRAFTS (`import_key` set); the import never publishes anything.

## Where things are (exact admin paths)
| Task | Path |
|---|---|
| Select ticked / this page / ALL matching filters | Catalogue → Products. Tick rows, or tick the header box and press "Select all N" (banner shows the exact count). Filters: Category/subcategory, Variety (cultivar), Form (cut), Status, Missing primary photo, Missing translation in… |
| Activate / Deactivate / Move to trash / Restore | Products → Bulk actions menu (confirmation shows exact count, how many change/skip/invalid, sample names) |
| Edit categories, cultivar, cut, processing, colour, featured, packaging, translations & SEO, photos | Bulk actions → **Bulk edit…** (all controls default to Keep; Preview shows before → after, then Apply) |
| History + rollback | bottom of the Bulk edit page |
| Photo review queue | Catalogue → **Photo review** |
| Alt text, 10 languages | Media → open an image → Alt text — AR…UR |
| Azizi / Red Peppers | already renamed in place by the migration (ID/slug/URLs unchanged; "Aggizi" kept as an import alias) |

## Test results (real runs)
Passing: BulkProductEditor (8), Bulk editor UI through the real Filament screens (3), Photo review (2), Import (3, incl. 59 products × 10 languages × 5 fields), Storefront (2: all 59 × 10 = 590 product pages 200, localized name, breadcrumb → category, RFQ link, rtl/ltr), hierarchy, frames, builder, site chrome, catalogue admin, strict-authorization matrix.
Acceptance cases proven: Azizi pitted + sliced selected → preview shows exactly 2; with one French meta missing → 1 change / 1 skip, English, Arabic, photos, categories and other Azizi styles untouched; both present → 2 skipped; 25 matching rows across 3 pages of 10 change and nothing else does; olive cut on Extra Virgin Olive Oil → invalid, never saved; assign primary + two-at-once gallery + reorder + undo, then product page, queue counts and CSV agree; deactivate/reactivate, delete/restore, viewer user refused.
Full suite: remaining failures are the same ones that fail on the original code in this sandbox (QuoteRequest 7, LanguageActivation 3, InstallPackage 1, ContactMessage 1, CatalogQuote 1 — e.g. e-mail DNS validation); NetworkDiagnostic fails only inside the full run and passes alone, as before.

## Honest gaps / untested
- "Existing tags": this project has no product tag system, so there is nothing to bulk-edit there.
- The "Select all N across pages" state was verified in a real browser (screenshot) and the engine/ UI flow in tests with 25 ids over 3 pages; the Filament "select-all" tracking flag itself was not driven in an automated test.
- Alt text is generated only for photos confirmed by file name, from what is visible (product in a dark wooden bowl on white). It states no cultivar beyond the product name. The 74 BEKO files (retail jars), 6 group shots and the 3 guindilla files are NOT assigned; resolve them in Photo review.
- Colour/processing values are localized only for the three known values; other free-text values show as typed. Cut names are machine translations in 10 languages (not human reviewed).
- Not tested in a real browser: Arabic/Urdu admin screens (admin has en/ar strings only for the new pages' labels; bulk page labels are English), mobile layout of the admin pages, and a real MySQL 5.7 server (tests use SQLite).
- Frame overlay and watermark: images are never modified by any of these tools.
