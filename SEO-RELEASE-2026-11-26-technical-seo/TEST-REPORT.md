# Test report — technical SEO release (what really ran)

Environment: build sandbox, PHP 8.3, Laravel 13.34, Filament 5.8, **SQLite**; Chromium + Lighthouse 13.6.0 against a **local test server with demo data**. **The live website was not reachable and was not tested. MySQL 5.7: NOT RUN.** Nothing was deployed.

| Run | Result |
|---|---|
| Full suite on the release code | **1349 passed, 11 skipped, 20 failed** (26 259 assertions) |
| The same 20 tests on the previous code (before this release) | **the same 20 fail** → 0 new failures. They are sandbox-environment failures (no DNS for the RFQ e-mail check, network diagnostics, trusted-proxy defaults, language-activation fixtures): QuoteRequestTest ×7, NetworkDiagnosticTest ×6, LanguageActivationTest ×3, CatalogQuoteTest, ContactMessageTest, InstallPackageTest, TrustedProxyTest. |
| One regression found and fixed during this run | my first draft advertised an *unpublished* staging language in hreflang (`LocalesTest`, `LocaleEnforcementTest` caught it, 9 tests); fixed, full suite re-run as above. |
| New `SeoReleaseTest` | 13 tests: canonical/hreflang/og on the configured host even when the request host is hostile; tracking parameters dropped, page number kept; reciprocal alternates; unavailable translation not advertised and canonicalised to the default language; one-hop 301 for www/http with path + query, no loop, POST/admin/other hosts untouched; brand exactly once; one main `<h1>` (also with **16 slides**, slide headings `<h2>`, English fallback marked `lang="en"` inside the Arabic page); descriptive link text; page-specific descriptions; sitemap (per-language entries, categories, preferred host, drafts excluded, no duplicate/non-reciprocal references); robots; structured data (no offers/ratings/reviews, one Product, one BreadcrumbList, no local host). |
| Install rehearsal | The shipped ZIP unzipped over a clean copy of the previous release (analytics update + map hotfix), caches cleared, `SeoReleaseTest` run there: 13 passed; sitemap route present. |
| Lighthouse (local, anonymous) | 14 pages × mobile+desktop before and after — **SEO 92 → 100** on EN home, category and product (`link-text`), 100 elsewhere; Accessibility/Best Practices unchanged; see README and `lighthouse-reports.zip`. A first "after" run showed Best Practices 96 because the test copy lacked its `public/storage` link (demo images answered 403); that run was discarded and redone — it is not a code regression. |
| Raw-response checks | `seo-evidence.json` (title, `<h1>` count, canonical host, hreflang count, description: before vs after, with a hostile `Host:` header on the "after" request) and the two sitemap files. |

**Not tested:** the live site; MySQL 5.7; the packaging pages (cannot be published in the sandbox); real Googlebot rendering/Search Console; Lighthouse accessibility/performance regression on live hardware; native-speaker review of the new 10-language wording.
