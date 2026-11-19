# Wadi El Rayan — mobile CONTROL PANEL experience (staging-tested, nothing deployed)

Phone range: **viewport 599 px and narrower.** Desktop and tablet admin (600 px and up) and the whole public website (all sizes) are unchanged. Same panel, routes, permissions, validation, publishing workflows and backend. **No migration, no npm, no Composer, no change to compiled assets.**

## Baseline / version requirements
| | |
|---|---|
| Release base | `23adef3` — "Legacy redirects: general word-based matching + section fallback" |
| **Required baseline (must already be installed)** | the accepted **mobile storefront update** (`wadi-mobile-experience-update.zip`, source commit `09e9a8e`) |
| This update | source commit `0d88245` (10 files, listed in `CHANGED-FILES.txt`) |
| Stack | PHP 8.3 · Laravel 13.34 · Filament 5.8 · Livewire 4 |
| Server needs | nothing new (no queue, no cron change, no Node, no Composer) |

This ZIP contains **no storefront file and no compiled asset** (`public/build` is not touched, Filament's own assets are not touched), so it cannot overwrite or undo the accepted storefront work. The admin phone chrome is two small static files, `public/css/wr-admin-mobile.css` and `public/js/wr-admin-mobile.js`, shipped as-is (no build step). If `public/flags/sm/*.webp` (from the storefront update) exist they are used for the flags; otherwise the original flag SVGs are used.

## What phones get
1. **Language strip at the very top** (outside the menu, also on the sign-in page): each *admin-interface* language with its configured flag over its native name, the current one highlighted, horizontally scrollable with an end fade. Only languages that have an admin translation file (`lang/<code>/admin.php`) are offered — today **English and Arabic**. Arabic switches the whole panel to RTL. This is the *interface* language only; the ten content-language tabs inside forms are untouched. Switching reloads the page, so if a form has unsaved edits the panel asks first (translated) and Cancel keeps everything.
   *New behaviour, honest note:* the panel had no interface-language switch before. The choice is stored in a cookie (`wr_admin_lang`); without a choice the panel keeps the site's configured language, exactly as before. Nothing is written to the database. Adding another admin language later = add `lang/<code>/admin.php` and it appears in the strip automatically.
2. **Compact header**: the panel's own top bar restyled — menu button, identity (company name) over the **current screen title**, a search icon that opens the panel's own global search full-width, the existing notifications and account controls.
3. **Bottom navigation**: Dashboard · Products · Requests · Media · More, real routes, only what the signed-in user may open (same `canAccess()` as the server), translated labels, route-based active state, safe-area aware, 44 px targets. It steps aside while a field is focused / the on-screen keyboard is open. **More** opens the panel's own organised drawer (the same seven groups as the desktop sidebar) with dialog semantics, inert background, Escape to close and focus returned to *More*.
4. **Editing**: single-column forms; tabs (resource tabs and the ten content-language tabs) scroll sideways with an end fade and scroll the active tab into view; **Save / Cancel are pinned above the bottom bar while the end of the form is off-screen and sit in place once it is on screen** (nothing is covered); fields scroll clear of the bars; validation errors (browser-required and server-side) switch to the tab holding them and keep every entered value; Livewire's loading state prevents double submits; success/failure toasts sit above the bar.
5. **Lists**: resource list tables become **labelled cards** (every column stays, labelled; row actions visible); search and filters in the panel's toolbar; **filters and the column manager open as bottom sheets** with Apply/Reset; the complete bulk system is untouched (current page, all filtered, excluded rows, exact counts, preview, apply, history, roll back — see test report). Comparison/preview/report tables keep a readable width and scroll **inside their own container**.
6. **Media**: upload sheet, previews and the primary-image picker work on touch; the product **gallery has Move up / Move down buttons** (a touch alternative to dragging). The buttons are hidden above 599 px by one rule in the page head, so desktop/tablet keep the drag handle only.
7. **Analytics**: filter bar as a two-column grid of full-width fields with 44 px controls; data tables, CSV/PDF export and print stay reachable; tables scroll in their own container; empty states unchanged. All numbers are the real report data.
8. Accessibility/quality: 44 px targets, 16 px inputs (no iOS zoom), focus trap by `inert`, Escape, focus return, `prefers-reduced-motion`, no framework, no service worker, no browser storage of panel data.

## Install
1. **Back up** the site folder (at least the two modified files and `public/css`, `public/js`).
2. Unzip `wadi-admin-mobile-update.zip` over the site (same paths as the repo).
3. `php artisan optimize:clear && php artisan view:clear`
4. Open the panel on a phone (or a browser at ≤ 599 px). Sign-in already shows the language strip.

## Rollback
* **Instant switch-off:** delete `public/css/wr-admin-mobile.css` (the strip, bottom bar and script are then not rendered at all). Tested.
* **Full revert:** restore the backup of the two modified files (`app/Providers/Filament/AdminPanelProvider.php`, `app/Filament/Resources/Products/ProductResource.php`) and delete the other 8 new files; then `php artisan optimize:clear`. No database change to undo.

## Known limits (see TEST-REPORT.md for the full list)
* Chromium emulation only — no real iOS/Android device or real soft keyboard.
* The Dashboard's two widgets use MySQL-only SQL and error on the SQLite test database (unchanged, not part of this update).
* Move up/down buttons were added to the **product gallery** only; other drag-only orderings (e.g. the product list's reorder mode) are unchanged.
