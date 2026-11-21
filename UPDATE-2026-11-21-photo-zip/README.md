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
1. Back up the site. 2. Unzip `wadi-photo-zip-update.zip` over the site (5 files, no migration, no npm). 3. `php artisan optimize:clear && php artisan view:clear`.
Rollback: restore the 4 changed files from the backup and delete `app/Services/Catalog/PhotoZipImporter.php`.

## Limits (important)
* **Server upload size:** the ZIP travels in one request, so it must fit the host's `upload_max_filesize`/`post_max_size` (the dialog shows the figure). The 40-photo ZIP you sent is **4.2 MB**. If your host allows less, upload it in smaller ZIPs — duplicates are skipped, so splitting is safe. Per ZIP: up to 100 photos, 300 MB uncompressed.
* The file-name suggestion is only a hint (exact slug match). Photos are AI-generated illustrations per the ZIP's README — review them before publishing; this feature does not publish anything.
