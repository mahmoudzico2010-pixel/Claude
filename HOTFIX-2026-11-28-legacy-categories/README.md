# Old category addresses → new categories (301)

Found from your screenshot: `/public/product-category/green-olives-products` returned 404 because the old shop called the category `green-olives-products` and the new site calls it `green-olives`.

* New rule: when an old category/product address has no exact match, the controller also tries (1) your explicit mapping in **`config/legacy.php`** and (2) the slug without the old-shop ending `-products`. A redirect happens only if the target exists; otherwise a normal 404 (never the homepage).
* `config/legacy.php` already maps `green-olives-products` → `green-olives`. To fix another old address, add a line: `'old-category-slug' => 'new-category-slug',` (and `products` for a product whose slug changed), then run `php artisan config:clear`.
* Install: unzip over the site (2 files), `php artisan optimize:clear`. Test: `https://wadielrayan.net/public/product-category/green-olives-products` → 301 to `/en/products/category/green-olives`. Rollback: restore the 2 files.
* Needs from you: the list of OTHER old category/product addresses from Search Console (Pages → "Not found (404)") or `site:wadielrayan.net`; I cannot guess them (e.g. old "black-olives-products" could mean either of two new black-olive categories).
* Tested: automated tests for suffix rule, explicit mapping, shop path, and unknown slug = 404. Not tested on the live site.
