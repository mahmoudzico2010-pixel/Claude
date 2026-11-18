# Performance + old-website redirects (staging-tested, not deployed)

Prerequisite: the technical-SEO release is installed. No migration, no npm, no Composer. This ZIP **includes** the variety-slug / sitemap hotfix (same `routes/web.php` and `SitemapController.php`), so you can install this one instead of it.

## 1. What your real PageSpeed reports showed (Nov 18, live)
Mobile 84 / desktop 95; **total download 8,127 KiB (mobile) and 8,904 KiB (desktop)**; mobile LCP 4.5 s; "improve image delivery" 342–651 KiB; render-blocking 200–300 ms.

## 2. Root cause of the 8–9 MB (found in code, reproduced, fixed)
The homepage slider stacks all slides in one grid cell. The browser treats a `loading="lazy"` image as visible when it overlaps the viewport, so **every slide image was downloaded at page load** (with 16 slides, the first image — the LCP — competed with 15 others on a slow phone connection). Measured on the same 16-slide test data: **before = 15 images fetched at load; after = 3 (21 KiB)**; the next slide now loads only after the page has finished loading and just before its turn. Slides still rotate with real images (checked 8 rotations on phone and desktop widths, no errors). Without JavaScript the first slide shows as before. Video slides no longer fetch their poster until their turn.

Lighthouse on that test data (demo images are much smaller than yours, so the gain on the live site should be far larger): mobile payload 1,158 → 505 KiB, desktop 1,272 → 626 KiB; mobile performance steady at 91 (before: 83–92 depending on run), desktop 95 → 99, desktop LCP 1.5 → 0.9 s, CLS 0.024 → 0.001. The 16 `<h1>` on the homepage were reproduced with this data and are 1 after the SEO release.

## 3. Resized image copies — `php artisan media:variants`
Images imported without smaller copies (the catalogue photos on the test copy had none) make product cards and thumbnails download the full 1200 px original (≈90 KiB each). The new command **adds** 400/640/960/1280-px WebP copies next to the original (never changes or deletes the original; skips images that already have copies; works in batches of 20). Run it on the site until it says `0 remaining`:
```
php artisan media:variants --dry-run      # how many images need it
php artisan media:variants                # repeat until "0 remaining"  (use --limit=40 on a fast host)
```
Needs PHP GD with WebP (the site already uses it for uploads). Whether your live images lack copies is **not known** — `--dry-run` tells you.

## 4. Old-website addresses → the same page on the new site (301)
Your screenshots show Google still lists the old product page (*Shop › Green Olives*) and the old addresses answer 404 (`/public/product/manzanilla-green-olives-whole`, `/contact-us`, `/cookie-policy`). New rules, tested: `/public/product/<slug>`, `/product/<slug>`, `/shop/<…>/<slug>`, `/product-category/<slug>`, `/shop`, `/contact-us`, `/about-us`, `/cookie-policy` and `/privacy-policy` (→ the privacy policy, where cookies are described; only if it is public), `/public/en/…`. A redirect happens **only when the target exists** (published product, active category/variety, public page); anything else is a real 404 — never the homepage, and the destination is never taken from the request (no open redirect).
**Not known:** the exact address Google shows for that result. Right-click the result → *Copy link address* and send it to me; if it is a pattern not listed above I will add it.

## 5. Not changed / still open
* Render-blocking CSS (90 KiB, ≈200–400 ms) and ~100 KiB of unused JavaScript: fixing them means rebuilding the compiled assets (npm), which was deliberately not done.
* Best Practices: "image aspect ratio" (the decorative frame artwork is intentionally stretched over cards) and the security-header suggestions (CSP, HSTS, COOP) — HSTS is a one-line `.htaccess` change you may add once everything is HTTPS; I did not touch `.htaccess`.
* Accessibility: colour-contrast warning (unchanged).
* The live page's 670 ms "document request latency" includes the redirect from `/` to `/en`; not changed (language redirect is by design).

## Install
1. Back up the site folder. 2. Unzip `wadi-performance-redirects.zip` over the site (replaces `routes/web.php`; if you edited it by hand, merge the `foreach (['public/{path}', …` block and the two `[A-Za-z0-9]` route patterns). 3. `php artisan optimize:clear` and `php artisan cache:clear`. 4. `php artisan media:variants --dry-run`, then run it until 0 remaining. 5. Test `https://wadielrayan.net/public/product/manzanilla-green-olives-whole` → 301 to `/en/products/manzanilla-green-olives-whole`. 6. Re-run PageSpeed on `/en` (mobile) and compare total size. **Rollback:** restore the backup, `php artisan optimize:clear`; generated image copies are harmless extra files.

Verified here: local server, SQLite, demo data. **Not tested:** the live site, MySQL 5.7, your real slide images.
