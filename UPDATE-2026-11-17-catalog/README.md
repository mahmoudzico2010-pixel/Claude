# Catalogue import — phase 1 (59 entries, ten languages, photo matching)

Run on staging first:
```
php artisan migrate --force                       # adds products.import_key and the photo review table
php artisan catalogue:hierarchy                   # families / subcategories (from the previous update)
php artisan catalog:import-59 --photos=/path/to/unzipped-WebP-folder --mapping=photo-mapping.csv     # DRY RUN, writes nothing
php artisan catalog:import-59 --apply --photos=/path/to/unzipped-WebP-folder                          # real run, repeatable
```
Rollback: the import only adds drafts (import_key set) and fills empty fields. To undo: soft-delete products where `import_key` is not null and status is draft, or restore the database backup taken before `--apply`.

## Result on my test database (real ZIP)
Created 55 + reconciled 4 existing = 59 products (25+20+3+4+4+1+1+1). 2,926 translation fields filled, 24 existing human fields kept. 3 cultivars (AKS, Dolce, Sers Cola) and the Cored style created. 124 photos imported, 44 matched by file name (12 products got a primary image), 80 in the review queue (74 BEKO#### camera files + 6 group/combined shots). A second run created nothing (59 reconciled, 0 filled).
All new products are DRAFT; nothing is published by the import.

## Not done yet (be aware)
- The bulk editor in Products (select page / all matching filters, activate, delete/restore, Keep/Set/Clear attribute edit, translation fill/overwrite, media actions, preview, history, rollback) — NOT built.
- Admin screen for the photo review queue (assign / reject / reorder) — table and data exist, screen not built.
- Localized image alt text in 10 languages — the media table only has English and Arabic alt columns; the import fills those two.
- Per-product copy is style-specific but NOT cultivar-specific: there is no verified cultivar information to base more on, and I did not invent any. Unknown facts (calibre, ingredients, shelf life, nutrition, packaging, MOQ, certifications) are left empty.
- "Azizi" matched the existing catalogue record spelled "Aggizi" — please confirm which spelling you want.
- "Red Peppers" reuses the existing category "Red Pepper"; colour/processing fields are single-language columns (English).
- 74 BEKO camera photos were not assigned by sequence or by looks, per your brief; they wait in the review queue.
- Storefront rendering of all 590 combinations, RTL pages and the permission tests for bulk actions: untested because the bulk editor does not exist yet. Only the import (3 tests) is tested.
