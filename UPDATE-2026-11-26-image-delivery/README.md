# Update 2026-11-26 — Image delivery (the biggest remaining Lighthouse item) + resolver safety

## Why scores were still low (from your live reports)
"Improve image delivery — est. savings 170–204 KiB", "Image elements without explicit size", render-blocking CSS, unused JS.
I reproduced the image item on a category page (mobile): **257 KiB** of wasted image bytes. Two causes:

1. **Product photos with no smaller copies.** Photos added through the catalog import / ZIP have only the full 1200 px file, so every card
   (shown ~178 px wide on a phone) downloads the 1200 px original. The command that fixes this already exists in the site:
   `php artisan media:variants --limit=40 --min-kb=1` (cPanel → Terminal, or a Cron job). It only ADDS smaller WebP copies; originals never change;
   repeat until it prints "0 remaining". Locally this alone cut the waste from 257 KiB → 74 KiB.
2. **The gold-frame overlay (1200 px, 78 KB) was loaded for every card.** Now a 480 px copy (15 KB) is used on cards (included in this ZIP:
   `public/frames/wadi-emerald-gold-frame-480.webp`; the original stays for large views). Locally waste → 13 KiB, page weight 624 → 566 KiB.

## Safety fix in the legacy-URL resolver (previous update)
Addresses in the CURRENT structure (`/en/products/…`, `/ar/news/…`) that really don't exist (e.g. an unpublished page) stay a true 404 and are no longer redirected
(two older tests expected exactly that). Old-style addresses are still resolved as before.

## Full-suite result (sandbox)
Before this fix the full suite showed 7 NEW failures caused by the resolver (media/packaging 404 rules, explicit old-category mappings, traversal/host-in-path guards, hidden products). All fixed: the resolver now never touches media/storage/packaging, ignores traversal or scheme-in-path addresses, and the explicit mappings in `config/legacy.php` stay authoritative; the general resolver only improves on a bare products-index answer. The related suites (SeoRelease, FreshDeployment, MediaFileRange, PackagingRoutes, CatalogStructure, PackagingCatalogImport, LegacyUrlResolver, IndexingHygiene) pass (89 tests). A final full run is reported in chat. The other known failures are the sandbox-only ones (QuoteRequest, ContactMessage, CatalogQuote, NetworkDiagnostic, TrustedProxy, InstallPackage, LanguageActivation, MediaUploadPolicy megapixel limits).

## Install
Upload `pkg/` over the site (6 files, incl. the controller; one is the 480 px image). Clear `storage/framework/views/*`. Then run the `media:variants` command above (the step that matters most).
Rollback: restore the files. No migration.

## Not changed / honest limits
Render-blocking CSS and unused JS (~100 KiB) live in the compiled Vite bundle; reducing them needs a rebuild (no npm on the server). Not tested: live site, MySQL 5.7.
