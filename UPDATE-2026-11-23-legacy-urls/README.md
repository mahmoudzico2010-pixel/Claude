# Update 2026-11-23 — Indexed / old URLs (general fix) + Lighthouse accessibility & SEO

## What it fixes
Google still holds old addresses (WordPress shop paths, `/public/…`, `/index.php/…`, old language sub-domain paths, feeds, wishlist links).
They used to answer **404**. Now, when a page would be a 404, a **general resolver** (no per-product list) decides:

* **301** to the best current page: product → cultivar → category → products index, or the matching section
  (about / contact / quote / export / quality / private label / news / team / privacy / terms), words recognised in 12 languages
  (e.g. `/productos`, `/contatto`, `/member_cat/export/`, `/en/about-us.html`, `/public/products/natural-black-olives/sers-cola`).
* **410 Gone** (no replacement) for junk: `/feed/`, `/shop/feed/`, `wp-*`, `xmlrpc`, cart/checkout/my-account, `?add_to_wishlist=`, `?replytocom=` …
* real 404 stays only for addresses that mean nothing.
Safety: only published products / categories / cultivars with published products are targets; targets are built with `route()` (never from the
request host/query → no open redirect); GET/HEAD only; admin/assets/JSON never touched; any resolver error falls back to the normal 404.

## Lighthouse (local, same pages before → after)
| | before (your report) | after (sandbox, home/products/about, mobile+desktop) |
|--|--|--|
| Accessibility | 86 | **100** (tap targets 24px, pagination aria, filter button name, text contrast ≥ 4.5:1, heading order) |
| SEO | 92 | **100** (sandbox robots.txt/static served correctly) |
| Performance | 96 | 99–100 (home/about); product pages on mobile 85–88 in the sandbox only because demo images are missing |
| Best Practices | 73 | 96–100 here; the only remaining item locally is 404s for demo images (sandbox data) |
**Not reproducible from the sandbox:** the live Best Practices 73 and CLS 0.11 (live-only causes: HTTPS/mixed content, real console errors,
live hero images). Please open the live report's "Best Practices" and "CLS" detail lists (or send the JSON) and they will be fixed next.

## Install (cPanel)
Upload the contents of `pkg/` over the site (same folders). Then clear caches: delete `bootstrap/cache/*.php` and `storage/framework/views/*`.
No migration, no new .env keys. CSS source tokens changed too (`resources/css`), but the fix is also applied at runtime in the layout, so no rebuild is needed.
## Rollback
Restore the previous copies of the files in `pkg/` (list below). Removing `bootstrap/app.php` change alone disables the 404 resolver.

## Tested / not tested
Tested: `LegacyUrlResolverTest` (14 tests), related suites (indexing hygiene, photo matcher), Lighthouse 13.6 locally.
NOT run: MySQL 5.7; the live site (not reachable from the sandbox). Full-suite result is reported separately in the chat.
config/design.php
app/Services/Legacy/LegacyUrlResolver.php
app/Services/Catalog/ProductPhotoMatcher.php
app/Http/Controllers/LegacyUrlRedirectController.php
resources/views/layouts/public.blade.php
resources/views/errors/410.blade.php
resources/views/builder/elements/timeline.blade.php
resources/views/vendor/pagination/tailwind.blade.php
resources/views/pages/products/index.blade.php
bootstrap/app.php
