# Update 2026-11-24 — Performance & Best Practices (from the live Lighthouse report)

## What the live report showed (Nov 22)
Mobile: Performance 85 (LCP 4.4 s, TBT 90 ms), Best Practices 96, Accessibility 97, SEO 100. Desktop: 98 / 96 / 97 / 100.

## Root cause found and fixed — phone LCP
I reproduced the slow phone LCP locally (category page: LCP 4.7 s, score 82). The Largest Contentful Paint element was **the cookie/analytics consent banner**:
it was hidden in the HTML and only shown after the analytics JavaScript ran, so it appeared late and counted as the page's LCP.
It is now shown in the first paint (a 1-line inline script, only when no choice is stored). Result locally: LCP 4.7 s → 1.7 s, Performance 82 → 98.

## Best Practices
* Added `Cross-Origin-Opener-Policy: same-origin` (origin isolation audit).
* HSTS is now `max-age=63072000; includeSubDomains; preload` (strong policy audit).
* NOT done on purpose: strict CSP / Trusted Types. The admin can paste Google Analytics / Tag Manager snippets in Settings; a strict CSP would silently break them, and Lighthouse only counts a strict policy. Say if you want this and which scripts you use.
* "Images with incorrect aspect ratio" and "Identical links" and the last contrast item depend on the live data (your real photos / texts); locally they pass (100). Send a screenshot of the failing rows' element list from PageSpeed Insights if they persist.

## Still open (honest)
Performance on the product page on a throttled phone in my sandbox was 80–88 with synthetic demo photos; real numbers need the live report after this update.
"Reduce unused JavaScript (~100 KiB)" and "Render-blocking CSS (~150 ms)" are in the compiled Vite bundle; reducing them needs a rebuild of `public/build` (no npm on the server) — next step if you want it.

## Install / rollback
Upload `pkg/` over the site (2 files). Clear `storage/framework/views/*`. Rollback: restore the 2 files. No migration.
Tested: `LighthouseHeadersTest`, `LegacyUrlResolverTest`; local Lighthouse 13.6 mobile. Not tested: live site, MySQL 5.7.
