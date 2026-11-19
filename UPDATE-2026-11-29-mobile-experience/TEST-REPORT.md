# Test report — mobile experience (staging/local only; nothing deployed)

## Tested (what was actually run)
| Check | Result |
|---|---|
| Desktop/tablet pixel comparison: 12 page types × EN + AR × 600, 767, 768, 1024, 1440, 1920 px, animations frozen, same demo data, baseline from a clean checkout of the previous release | **144 / 144 identical** (`evidence/pixel-diff-results.json`, side-by-side images in `screenshots/desktop-tablet-comparison/`) |
| Phone behaviour script (Chromium emulation, 360/390/430 px, EN + AR/RTL; strip, nav, More sheet, focus trap/Escape/focus return, filter sheet apply/clear/Back, gallery, forms, keyboard state, tables, floating elements, boundary 599/600 and live resize on/off, mobile controls absent on tablet/desktop incl. keyboard focus) | **96 / 96 pass** (`evidence/phone-behaviour-results.json`, script `evidence/mtest.mjs`) |
| `tests/Feature/MobileShellTest.php` (strip lists all published languages with flags, 5 bottom destinations with real routes, accessible dialog, 10-language labels, RTL, one H1, no duplicate analytics script, canonical/hreflang untouched, static assets) | 9 passed, 306 assertions |
| Install rehearsal: ZIP extracted over a clean copy of the previous release; site served, `/en`, `/ar`, products, product pages 200; shell present; deleting `public/css/wr-mobile.css` removes the shell completely (kill-switch) | passed (HTTP level) |
| Full Laravel suite | see "Full suite" below |

## Mobile performance (Lighthouse 13.6, mobile emulation, simulated slow 4G, median of 3, local demo server)
| Page | Perf before → after | FCP | LCP | CLS | Payload |
|---|---|---|---|---|---|
| /en | 100 → 100 | 1.23 → 1.38 s | 1.23 → 1.38 s | 0.048 → 0.000 | 440 → 502 KiB |
| /en/products | 87 → 82 | 1.37 → 1.54 s | 3.98 → 4.65 s | 0.048 → 0.000 | 659 → 812 KiB |
| product page | 80 → 76 | 1.23 → 1.38 s | 4.45 → 4.96 s | 0.048 → 0.000 | 713 → 768 KiB |

**Honest reading:** the phone shell costs ~0.15 s FCP and 50–150 KiB (extra DOM, inlined 15 KB CSS, 17 KB JS, two-column cards load more images above the fold). Layout shift improved to 0. Flag payload was cut from ~99 KiB to ~5 KiB by the WebP thumbnails (first build, with SVG flags, scored lower: products 80). The products-list LCP is still slower than before on this demo set because demo images have no resized variants, so `sizes` cannot help; real WebP variants should narrow this. No score is promised. Numbers are from a local server, not production.

## NOT tested / limits
* No real iOS/Android device, no real on-screen keyboard: keyboard handling was verified by emulating focus/viewport resize in Chromium only. Safari-specific behaviour is untested.
* MySQL 5.7 — **NOT RUN** (SQLite only). The update has no migration.
* The 600–767 px range intentionally keeps the existing layout (not a phone range here).
* The site has no product text search, so none was added.
* Rehearsal ran PHP-level tests against the shared vendor directory of the base checkout, so only the HTTP-level checks are reported for the rehearsal.
* RFQ/contact submission succeeds in staging only with DNS e-mail validation relaxed (sandbox has no DNS); 8 baseline Quote/Contact test failures are that sandbox limitation, unrelated to this update.
* Real analytics/consent traffic and WhatsApp on production are untested; consent banner position and the WhatsApp offset were checked in the demo.
