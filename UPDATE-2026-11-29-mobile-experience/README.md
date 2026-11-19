# Wadi El Rayan — premium MOBILE-ONLY experience (staging-tested, nothing deployed)

Phone range: **viewport 599 px and narrower.** Tablet and desktop (600 px and up) are untouched. Same website, routes, content, CMS and backend; admin panel not changed. **No migration, no npm, no Composer, no service worker.**

## How desktop and tablet are protected (by construction, then by measurement)
* The phone stylesheet `public/css/wr-mobile.css` is linked with `media="(max-width: 599px)"`: above 599 px the browser does not apply a single rule from it.
* All new markup carries the class `wrm` and is `display:none` by default (one tiny inline rule), so on desktop/tablet it is never drawn, never focusable and never announced.
* `public/js/wr-mobile.js` runs only while the phone media query matches. It is switched on/off live when the viewport crosses 599/600 px: listeners removed, injected buttons and table wrappers removed, open sheets closed, scrolling restored.
* No shared design token, Tailwind rule or compiled asset was changed; new variables are `--wrm-*` only.
* **Measured:** 96 of 96 full-page screenshots at 768 / 1024 / 1440 / 1920 px (12 page types × English and Arabic) are **pixel-identical** before and after (see `evidence/`).

## What phones get
1. **Language strip** above the header (outside any menu): every enabled language from the language manager, its configured flag over its native name, current language highlighted, horizontal scroll with fade + chevron hints, current language scrolled into view, 72 × 52 px tap targets. Links reuse the site's own equivalent-page logic (`switch_locale_url`), so a product page opens the same product in the new language and the existing fallback applies when no translation exists. The desktop language selector is untouched.
2. **Compact header** (logo + menu button, 52 px, sticky; the strip scrolls away).
3. **Bottom navigation**: Home · Products · Packaging · Request a Quote (gold) · More. Real routes, active state from the current route, safe-area aware, hides while a form field has the keyboard, never covers content (page bottom padding), WhatsApp and the consent notice sit above it.
4. **More sheet**: remaining menu items from the site's own menu (with expandable children), quick call / e-mail / WhatsApp, and "Privacy choices" (the floating pill is replaced by this on phones so it no longer covers content). Focus is trapped, Escape/backdrop/× close it, focus returns to the opener, background is `inert`.
5. **Product filters** as a bottom sheet: active-filter count on the button, live count in the sheet, Apply / Clear, Escape closes, filters stay in the URL so Back keeps them.
6. **Product gallery**: visible previous/next buttons + swipe (RTL-aware); existing thumbnails and lightbox kept.
7. **Forms**: phone keyboards and autofill hints (email, tel, name, organisation), 16 px fields (no iOS zoom), full-width submit, **duplicate submissions blocked**, entered data kept on validation errors (server `old()` as before).
8. **Cards/tables**: two-column product cards (compact), tables scroll inside their own frame (no page-wide horizontal scroll), larger footer/breadcrumb/pagination tap targets.
9. **Speed/stability**: no framework, no animation library (≈ 12 KiB JS + ≈ 10 KiB CSS, only loaded/applied at phone width); animations are subtle and removed under *reduce motion*.
10. **Ten languages** (AR, EN, ES, FR, IT, PT, EL, RU, ZH, UR) for every new label (`lang/*/mobile.php`); Arabic and Urdu mirror with logical spacing.

## Changed files
New: `public/css/wr-mobile.css`, `public/js/wr-mobile.js`, `resources/views/partials/mobile-shell.blade.php`, `lang/{ar,en,es,fr,it,pt,el,ru,zh,ur}/mobile.php`.
Modified (each change is an invisible hook): `resources/views/layouts/public.blade.php` (viewport-fit=cover, phone stylesheet/script links, include), `resources/views/partials/header.blade.php` (`data-wr-site-header` attribute), `resources/views/pages/products/index.blade.php` (`data-wr-*` hooks), `resources/views/components/field.blade.php` (autocomplete / inputmode hints).
Tests (not required on the server): `tests/Feature/MobileShellTest.php`.

## Install / rollback
See `EXACT-ADMIN-STEPS-AR.md` (Arabic) — in short: back up → extract the ZIP over the site → `php artisan optimize:clear && php artisan view:clear` → open on a phone. **Instant kill-switch:** rename `public/css/wr-mobile.css` to `wr-mobile.css.off` and run `php artisan view:clear`; the whole phone experience disappears and the original header returns. Full rollback: restore the 4 modified files and delete the new ones.

## Not built (and why)
* **Product search**: the site has no text search of products, so no search icon was invented (filters are provided).
* **Tablet / large-phone-landscape (600–767 px)** deliberately keep the existing responsive layout.
