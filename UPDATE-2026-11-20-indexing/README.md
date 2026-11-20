# Google indexing — diagnosis and fixes (coverage export of 2026-11-20)

## What the two exports say
* 180 known URLs: **27 indexed, 153 not indexed** (the site is only days old; first data point 2026-11-16).
* **134 of the 153 are "Google systems" decisions, not errors**: *Crawled – currently not indexed* **70** and *Discovered – currently not indexed* **64**. Google fetched (or queued) these pages and chose not to index them yet. For a new domain this is normal and is mostly about *perceived value*, not a technical fault.
* Website-side reasons (19 pages): noindex 5 · redirect 5 · 404 ×3 · 5xx ×2 · 403 ×2 · duplicate-without-canonical 1 · robots.txt 1.
* Of the 70 "crawled, not indexed" URLs: ~40 are **translated copies** (/packaging 19, /products 12, /news 6, about/team/export…), mostly Spanish, Greek, Russian, Italian, French; 5 are **`/language/xx?to=…` redirect links**; the rest are **old WordPress addresses** (`/shop/`, `/product/…?add_to_wishlist=…`, `/member_cat/…`) and old **sub-domains** (`en.`, `it.`, `sp.wadielrayan.net`).

## Fixed in this update (small, safe, tested)
1. `robots.txt` now has `Disallow: /language/` — the language switch is a visitor action, not a page; Google was crawling it and reporting "Page with redirect".
2. Every language-switch link (desktop selector and the phone strip) is `rel="nofollow"`, and the redirect answers `X-Robots-Tag: noindex, nofollow`. Switching languages for people is unchanged.
3. Tests: `IndexingHygieneTest` (3 tests). Install: unzip over the site, `php artisan optimize:clear`. Rollback: restore the 4 files.

This removes crawl waste and ~5–10 reported URLs. **It will not by itself raise the indexed count** — be honest about that.

## What actually moves the number (actions, in order of effect)
1. **Concentrate on fewer, stronger pages first.** Ten languages × every page = ~180 thin-looking URLs on a new domain. In *Settings → Languages*, keep **English and Arabic** (and any language whose pages are fully, professionally translated) published; leave the others unpublished until their translations are complete. Unfinished languages show English fallback text → Google treats them as duplicates → "Crawled – not indexed". Publish more languages gradually, a few per week. (The sitemap already lists only published, genuinely available language versions.)
2. **Search Console → URL Inspection → "Request indexing"** for the 10–15 key pages (home, products, 3–4 category pages, 3–4 best products, export, private label, contact) in EN and AR. Daily quota is small; do a few per day.
3. **Old WordPress URLs**: confirm each returns a single 301 to its new page or a clean 404 (the legacy redirect rules are already in the site). For the 404 ×3, 5xx ×2 and 403 ×2 rows, open *Coverage → that reason → the table* and send me the exported lists — I need the exact URLs (they were not in the files you sent) to fix them.
4. **Old sub-domains** (`en.`, `it.`, `sp.wadielrayan.net`): either remove the DNS records or make each 301 to the main host/language. Google is crawling them as separate sites.
5. **Internal links + external signals**: link important product/category pages from the home page and from each other; add the site to the company's Google Business Profile, LinkedIn and trade directories. New domains rarely index well without a few real external links.
6. In Search Console press **Validate fix** on each website-side reason after the deploy; re-check in 2–4 weeks. Indexing of a new site normally takes weeks; judge the trend, not the first day.

## What I could not do
* The live site is not reachable from this environment, so I could not re-check live responses; fixes are tested on the local copy only.
* Nothing was deployed.
