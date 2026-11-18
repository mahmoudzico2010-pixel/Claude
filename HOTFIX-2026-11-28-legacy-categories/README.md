# Old shop addresses → new pages: general rule (no per-product editing)

Found from your screenshot: `/public/product-category/green-olives-products` returned 404 (old category name ≠ new `green-olives`).

## How it works now (general)
For addresses under the old shop paths (`/product/…`, `/shop/…`, `/product-category/…`, `/category/…`, with or without a leading `/public`):
1. Exact match with a published product / active category / variety → 301 to it (as before).
2. Otherwise the old name is matched to **one** new record **by its words**, ignoring order and shop noise words (*products, product, category, shop, all*): `green-olives-products`, `olives-green`, `Green_Olives` → `green-olives`. If two records fit equally (e.g. "black-olives" could be either black-olive category) nothing is guessed.
3. Still no single match → 301 to the **products section** `/en/products` (relevant, never the homepage). The destination is always built by the site; nothing is taken from the request (no open redirect). Addresses outside the old shop paths are untouched (404).
4. `config/legacy.php` is only an optional override list for special cases; you do **not** have to add anything per product.

## Install
Unzip over the site (2 files: `LegacyUrlRedirectController.php`, `config/legacy.php`), `php artisan optimize:clear`. Test: `https://wadielrayan.net/public/product-category/green-olives-products` → 301 `/en/products/category/green-olives`. Rollback: restore the 2 files.

## Honest limits
* Word-matching can only find a category/product whose words still appear in the new name; pages that no longer exist go to the products section (Google may report some as "soft 404" — that is expected for removed pages).
* Tested with automated tests (19 pass incl. word-order, noise words, ambiguity, fallback, no open redirect); not tested on the live site.
