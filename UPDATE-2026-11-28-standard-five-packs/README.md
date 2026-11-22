# Update 2026-11-28 — Only the five standard packs for ALL products

Goal: every product shows exactly these packs (as in your table): 225 L Plastic Drum, 5 kg White Plastic Bucket, 10 kg White Plastic Bucket,
5 kg Clear Plastic Bucket, 10 kg Clear Plastic Bucket. Their capacity / drained / net / gross weights come from the packaging catalog entries themselves (unchanged).

## How (one new command, nothing else changes)
1. Upload `pkg/` (1 file: `app/Console/Commands/PackagingStandardFiveCommand.php`). No migration.
2. cPanel → Terminal (or a one-time Cron job), from the site folder:
   * `php artisan packaging:standard-five`            → REPORT ONLY: shows which catalog entry it found for each of the five and how many products will change.
   * `php artisan packaging:standard-five --apply`    → makes the change. It first writes a backup of the old product↔pack links to `storage/app/backups/product-packaging-<time>.json` (the exact path is printed).
   * Undo: `php artisan packaging:standard-five --restore=product-packaging-<time>.json`
3. Clear `storage/framework/views/*` if the page still shows the old list.

## Safety
* The five packs are found by their English NAMES in your packaging list. If any one matches zero or more than one entry, the command stops and changes NOTHING (it prints what it found) — I cannot see your live names, so please run the report-only step first and send me the output if it stops.
* It only rewrites the product↔pack links (all products, including unpublished). The packaging entries themselves are never created, edited or deleted, and the Packaging page "See all packaging" is untouched.
* Products added LATER have no links; they would show the site's general list until you run the command again (say if you want new products to default to these five automatically).
Tested: `PackagingStandardFiveTest` (report-only changes nothing; apply gives every product exactly the five; the other packs stay; backup + restore; refuses when a pack is not found uniquely). Not tested: the live data, MySQL 5.7.
