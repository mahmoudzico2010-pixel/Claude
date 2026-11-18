# Wadi El Rayan — technical SEO release (implementation, not advice)

**Nothing was deployed, no DNS was touched, `.env` and the database were not modified.** This is a changed-files ZIP to upload onto the existing live site. Prerequisite: the *Analytics + navigation* update (2026-11-24) and its map hotfix are installed (same code base). PHP 8.3, Laravel 13.34 / Filament 5.8. **No schema change, no migration, no npm, no Composer** — everything is PHP, Blade and language files.

## What ran against what (read this first)
| | Result |
|---|---|
| **Live website** `wadielrayan.net` | **NOT checked.** The build sandbox has no route to the live site (connection refused), so I could not reproduce the live HTTP findings, run Lighthouse on the live URLs, or see live content. Every finding below was reproduced in the **code** and on a **test copy of the site** (local PHP server, SQLite, *synthetic/demo data*, not your live content). |
| Lighthouse | Real Lighthouse **13.6.0**, 2026-11-18, mobile **and** desktop, anonymous requests to a local test server: Home EN+AR, category, product EN+AR, Private Label EN+AR (14 pages × 2 form factors, before and after). **No packaging page was tested** (the packaging catalogue cannot be published in the sandbox). These are **not** live scores; performance numbers are noisy on a shared CPU. |
| MySQL 5.7 | **NOT RUN.** All tests and the browser checks used SQLite. This release adds no SQL. |
| Automated tests | See `TEST-REPORT.md`. |

## Before / after findings
Locally-measured evidence: `seo-evidence.json`, `lighthouse/` (reports), `screenshots/`.
| # | Finding (from your live audit) | Reproduced? | What changed | Effect on a Lighthouse audit? |
|---|---|---|---|---|
| 1 | Two hosts each declare their own canonical | **Yes** — canonical/hreflang/og/sitemap were built from the *incoming* host (any Host header was echoed) | New single preferred origin from config (`SEO_PUBLIC_URL`, else https `APP_URL`). All canonical, hreflang, Open Graph, sitemap, robots and schema URLs use it. Optional 301 www/http → preferred (one hop, GET/HEAD only, path + query kept, never for POST, admin, Livewire, `_wr`, installer, plugin webhooks, other hosts). | No (canonical audit passes either way) |
| 2 | Canonical/hreflang carried tracking & filter parameters | **Yes** (hreflang kept the full query string) | Canonical/hreflang keep only a real `?page=N`; `utm_*`, `fbclid`, sort, filters are dropped. Filtered catalogue views were already `noindex`. Every genuine language is self-canonical. A language that is only the English fallback (no real translation) points to the default language and is not advertised. | No |
| 3 | 16 `<h1>` on the homepage | **Code path confirmed** (one `<h1>` per slide); **not visible with the demo data** (1 slide). Test with 16 slides added. | One main `<h1>` (visually hidden; *"Egyptian Table Olives & Pickled Vegetables for Global Markets"* in 10 languages, overridable per language with setting `home.h1_<locale>`); slide headings are `<h2>`; builder hero on the homepage is `<h2>`. Design unchanged. | No (structure improvement) |
| 4 | Brand duplicated in titles | **Yes** — `Picual Green Olives – Whole \| Wadi El Rayan — Wadi El Rayan` | Brand appears once: a title that already contains the brand is not suffixed; otherwise ` \| Brand`. The brand is the short name without the descriptor ("WADI EL RAYAN FOR FOOD INDUSTRIES" → "Wadi El Rayan"), or setting `seo.brand_<locale>`. **Human-written titles are not edited.** | No |
| 5 | "Read more" links | **Yes — Lighthouse `link-text` failed** on EN home, category, product (SEO **92**); Arabic passed | The visible words are unchanged; each link now carries its target in the accessible text ("Read more: *Product name*"; Quality link likewise). | **Yes: `link-text`** — SEO 92 → **100** |
| 6 | Two Arabic slide headings in English | **Not reproducible here** (they are live database content, not code). | Cannot be fixed from code and I changed **no content**. Added: (a) English fallback text inside a page of another language is now marked `lang="en"` (+ direction); (b) `php artisan seo:locale-gaps` — read-only report/CSV of every text still falling back to English per language, with **proposed** Arabic for the two known strings (*ماذا يمكن أن تخبرنا زيتونة واحدة* / *ابدأ بالكمية التي تحتاجها*, **unreviewed by a native speaker**). You apply them in the Homepage Manager. | No |
| 7 | Sitemap: 103 English entries, no categories | **Yes** — 91 `<loc>` (demo data), 0 categories, one URL per page with all alternates | One `<url>` per **genuinely translated** language version; categories/subcategories with published products; each page lists the same alternates (itself included) + `x-default`; drafts/noindex excluded; accurate `lastmod`; preferred host. Test copy: 854 URLs, 0 non-reciprocal references. | No (discovery) |
| 8 | robots.txt: empty `Disallow:`, no Sitemap | **Yes** — a static `public/robots.txt` file shadows the application's own robots route | Replaced with `Disallow: /admin`, `Disallow: /storage/rfq-attachments`, `Sitemap: https://wadielrayan.net/sitemap.xml`. CSS/JS/images are not blocked. (Delete the static file to use the dynamic version, which also disallows everything in maintenance mode.) | No |
| 9 | Same broad description on listing/Contact; weak Private Label text | **Yes** | Page-specific default descriptions in 10 languages for Home, Products, categories, varieties, products, Packaging, packs, About, Team, Quality, Export, Private Label, News, Quote, Contact — used **only** when a page has no description of its own. A short subtitle (<70 characters, e.g. *"Your brand, packed on our lines."*) is no longer used as the description. Existing human-written descriptions are untouched. | No |
| 10 | Structured data | Reviewed | One Organization + WebSite on the homepage, one Product + BreadcrumbList on products, Article/Event on news; all URLs on the preferred host; `url` added to Product/Article; Product brand uses the short brand. **No offers, prices, ratings, stock or GTIN** — a quote-only catalogue is therefore not eligible for Google Product/merchant rich results; that is correct, not a bug. | No |
| 11 | Product image attributes in initial HTML | **Yes** — main gallery `<img>` had only `:src`/`:alt` (Alpine) | Server-rendered `src`, `srcset`, `alt` on the primary image; Alpine only swaps them when a thumbnail is chosen. The zoom lightbox image is created only when opened (not in the DOM until then). | No |

### Lighthouse, test copy (same pages, same machine)
SEO: **100 on all 14 pages × both form factors after** (was 92 on EN home, category and product — all `link-text`; 100 on the rest). Accessibility and Best Practices: unchanged (A11y 94–100; Best Practices 100). Performance: within run-to-run noise on this machine (e.g. mobile home 99–100, product mobile 46–89 across runs, before and after). **Still open (not SEO, not changed):** `color-contrast` and, on Private Label, `heading-order`. Full table: `lighthouse/baseline-summary.txt` and `after-summary.txt`; all 28 reports (HTML + JSON) in `lighthouse-reports.zip`. A Lighthouse score is not a ranking promise.

## Install on cPanel (HostGator)
1. **Back up** the site folder (cPanel → Backup or Zip `public_html`). The database is not touched.
2. Upload `wadi-seo-release.zip` into the site root and **Extract** (same paths as the project; `bootstrap/app.php`, `config/wadi.php`, `public/robots.txt` and the views are overwritten — if you edited any of them by hand, merge instead).
3. **Add one line to `.env`** (do not replace the file): `SEO_PUBLIC_URL=https://wadielrayan.net` — this turns on the preferred host everywhere and the www/http redirect. Without it, canonical/hreflang use `APP_URL` when that is an https address (and the redirect stays **off**).
4. Terminal (or cPanel Terminal): `php artisan optimize:clear` then `php artisan cache:clear`. **Do not run migrate.**
5. Optional, one-hop redirect at the web-server level: `HTACCESS-ONE-HOP-REDIRECT.txt`.
6. **Verify** (replace with your URLs):
   * `curl -sI http://www.wadielrayan.net/en` → one `301` to `https://wadielrayan.net/en` (the HTTP→HTTPS rule in `.htaccess` may add one more hop unless you use step 5).
   * `curl -s https://wadielrayan.net/robots.txt` → contains the `Sitemap:` line.
   * View-source of `/en`: one `<h1>`, one `rel="canonical"` on `https://wadielrayan.net/en`, hreflang block.
   * `https://wadielrayan.net/sitemap.xml` → categories present, every `<loc>` on the preferred host.
   * Run Lighthouse (Chrome DevTools → Lighthouse, Mobile, SEO) on `/en`, a product and Private Label while logged out.
7. Optional: `php artisan seo:locale-gaps --locale=ar` → lists the English fallbacks (CSV in `storage/app/seo/`). It changes nothing.

**Rollback:** restore the file backup (or re-extract the previous release) and run `php artisan optimize:clear`. To switch the host behaviour off without touching files, remove the `SEO_PUBLIC_URL` line and run `php artisan config:clear`.

## Not changed / needs your decision
* **Draft copy from your brief is NOT applied** (it is a proposal): Home title *Egyptian Olive & Pickle Exporter | Wadi El Rayan*, Private Label title/description, product-card wording. The homepage title falls back to this wording **only if** you have no homepage/SEO title saved. Edit titles/descriptions in the admin (Pages → SEO fields, Homepage Manager) — your text always wins.
* The default descriptions and the 10-language H1 are my wording, **not reviewed by native speakers**.
* Old-URL redirects: no evidence of changed old URLs was supplied, so **none were created** (nothing redirects to the homepage).
* If your live `APP_URL` is the www address, set `SEO_PUBLIC_URL` explicitly to the non-www address as above.
* Search Console steps: `SEARCH-CONSOLE-GUIDE.md`.
