# Photo Review — "Upload ZIP of photos" (staging-tested, nothing deployed)

## What it does
In **Catalogue → Photo review** there is a new button **Upload ZIP of photos** (hidden from users who may not edit products and upload media).
1. The ZIP is opened **safely** (no path traversal, no symbolic links, no extraction bombs, size/count limits; only JPG / PNG / WebP are read; README, CSV, HTML and other files are ignored). Every picture must really decode as JPEG, PNG or WebP — a script renamed `.png` is refused.
2. Each new image is **registered in the Media Library** through the same code as any other upload (safe file name, WebP renditions, size/pixel checks), folder `products`.
3. Each one is **queued as unassigned** ("To review") in the Photo Review queue with the reason *Uploaded from ZIP "…"* and a **suggestion** taken from the file name (a product whose slug equals the file name). The queue then shows just that import (the search box holds the ZIP name; clear it to see everything).
4. **Duplicates are never imported again**: a photo whose bytes (SHA-1) are already in the Media Library — whatever its file name, whether used by a product, in the queue, or unused — and a photo repeated inside the same ZIP are skipped and listed with where the existing copy is.
5. A result card lists *imported / skipped duplicates / rejected* with reasons.

## What it never does
* **Never creates a product.** · **Never creates or changes a product-photo link, a primary image, a gallery order, or an existing review decision.** (Verified by tests and by a before/after comparison of every product, link and primary in the database.)
* Assigning stays the existing deliberate step: pick a photo → pick an **existing product** (or press *Select this product* on the suggestion) → *Assign this photo* (gallery) or tick **Primary** → Save. Ticking *Primary* on a product that already has one shows a warning; **Undo** restores the previous primary.

## Install
1. Back up the site. 2. Unzip `wadi-photo-zip-update.zip` over the site (8 files, no migration, no npm). 3. `php artisan optimize:clear && php artisan view:clear`.
Rollback: restore the 4 changed files from the backup and delete the new files `PhotoZipImporter.php`, `ProductPhotoMatcher.php`, `PhotoAutoMatcher.php` (in `app/Services/Catalog/`) and `resources/views/filament/pages/photo-review-match-plan.blade.php`.

## Limits (important)
* **Server upload size:** the ZIP travels in one request, so it must fit the host's `upload_max_filesize`/`post_max_size` (the dialog shows the figure). The 40-photo ZIP you sent is **4.2 MB**. If your host allows less, upload it in smaller ZIPs — duplicates are skipped, so splitting is safe. Per ZIP: up to 100 photos, 300 MB uncompressed.
* The file-name suggestion is only a hint (exact slug match). Photos are AI-generated illustrations per the ZIP's README — review them before publishing; this feature does not publish anything.

## Smart file-name matching (added)
Each photo's file name is matched to existing products and the top 3 are shown in the queue with a score and a band (**Strong ≥ 90 · Possible ≥ 65 · Weak ≥ 40**). Case, accents, `_`/space separators, copy/final/number suffixes, plurals and spelling variants (halved = halves, jalapeno = jalape-os …) are normalised; a cut or colour that contradicts the product (e.g. *sliced* vs *whole*) is penalised; products without a photo win ties; near-ties are flagged "close call". Camera-style names (`IMG_2231`, `BEKO0402`) get no suggestion. A hint only: nothing is assigned until you choose the product and press Assign. It also works for photos already in the queue.
Checked on the 40 files you sent: all 40 rank their exact product first. Full-suite result was still pending when this was packaged.

## One-click "Match all by file name" (added)
Photo review → **Match all by file name** → a preview lists exactly what would be assigned and what stays in the queue → **Assign the matches**. Works on photos you have already uploaded (every pending photo, not just the visible ones).
* **Level:** *Strong matches only* (default, recommended; score ≥ 90) or *Strong and possible* (≥ 65). **Close calls** (two products almost equally good), weak matches and camera-style names are **never** assigned — they stay in the queue.
* **Primary rule:** a product that already has a primary photo **keeps it** — matched photos only join its gallery. A product without a primary gets its best-matching photo as primary, the rest go to the gallery.
* **No products are created.** The preview changes nothing.
* **Undo:** an **Undo last match** button reverses the whole run in one click (every previous primary restored exactly); any single photo can also be undone from its card. Runs and undos are written to the audit log.
* Tested on your 40-photo ZIP in a copy of the catalogue: the preview wrote nothing; one click assigned them (plus one older queued photo that also matched); no product created; no existing primary replaced; Undo restored the queue, links and primaries exactly as before.
