# Test report — mobile control panel (local/staging only; nothing deployed)

Environment: Chromium (Playwright) phone emulation at **360 / 390 / 430 CSS px**, English LTR and Arabic RTL, demo data on **SQLite** (MySQL 5.7 — **NOT RUN**). Baseline = accepted storefront source `09e9a8e`; update = `0d88245`.

## Tested — what was actually run
| Area | What | Result |
|---|---|---|
| Navigation + language | 360/390/430 × EN/AR: strip visible at top, both languages with flag image + native name, only the active one highlighted (aria-current + bold), 44 px targets, direction flips, bottom bar (5 translated destinations, route-based active state, fixed to the bottom edge), compact header (identity, screen title, menu, notifications, account, search), no page overflow, **More** drawer (dialog semantics, background inert, focus inside, Escape closes, focus returns to More) | pass |
| Breakpoint | live resize 1440 → 1024 → 768 → 600 (chrome absent) → 599 → 390 (chrome present); keyboard Tab 80 stops at 1024 never reaches the mobile chrome | pass |
| Edit + save a product | change an Arabic field on the phone, Save → "Saved" toast, value persisted in the DB; Save button wired to Livewire loading state | pass |
| Validation + unsaved data | browser-required field (panel jumps to the tab holding it, message shown, other pending edit kept, nothing written); **server-side** error (duplicate slug: inline error, entered values kept, no navigation, DB unchanged) | pass |
| Language switch with unsaved edits | translated confirm; Cancel keeps page + data; OK reloads in Arabic RTL | pass |
| Upload + primary image | upload sheet on the phone → image selected as main image → saved → `primary_image_id` is the new asset | pass |
| Gallery reordering | Move up / Move down visible (44 px) on the phone → order persisted in `product_images` | pass |
| Bulk | page selection (exact "25 records selected" + "Select all 66"), all-filtered minus one excluded record (exact 65), bulk-edit page states scope, preview first, **Apply N changes** wrote only previewed rows, excluded product untouched, run recorded, Roll back control present | pass |
| Analytics | filter bar on the phone (country filter applied, no overflow), data tables + CSV/PDF controls reachable | pass |
| Permissions | Viewer and Sales: bottom bar offers exactly what the server allows; a hidden destination typed by URL is refused (403/404) | pass |
| Keyboard / viewport | focused field hides the bar; with a 430 px-high viewport the field stays on screen; bar returns; Save/Cancel pinned above the bar at the top of a long form and in place at its end | pass |
| **Acceptance script** | `evidence/atest.mjs` → `evidence/acceptance-results.json` | **112 / 112** |
| Panel sweep | all **49** sidebar screens × 360/390/430 × EN/AR = **294** page loads: no horizontal overflow, no 4xx/5xx, chrome present; only the Dashboard logs script errors (see limits) | pass |
| **Desktop/tablet admin regression** | 11 screens × EN + AR × 768/1024/1440/1920, same data, animations frozen, baseline = clean `09e9a8e` server (Arabic baseline = server with `APP_LOCALE=ar`, since no switcher existed) | **81 / 88 byte-identical**; the other 7 differ by 4–134 pixels (≤ 0.003 %) in analytics chart/anti-aliasing regions and one 2×2-pixel dot. The baseline compared with *itself* shows the same analytics noise. `evidence/adiff.json`, side-by-sides in `screenshots/desktop-tablet-comparison/` |
| Public website unchanged | 12 pages × EN/AR × 390/768/1440, before vs after | 71 / 72 identical; 1 differs by 24 px in the phone header corner (logo/hamburger anti-aliasing; not re-shot) |
| PHP tests | 14 new tests (`MobileAdminShellTest`: only languages with admin translations offered, shell hidden by default + hooks, bar permissions per role, route active state, cookie switch + return-URL safety, locale applied to the panel but not the public site, CSRF in the strip form, login page, gallery buttons hidden above the breakpoint, static assets with no service worker/storage) | pass |
| Full Laravel suite (serial, SQLite) | 1380 passed, 11 skipped, **20 failed = the same 20 known sandbox network failures** (QuoteRequest, ContactMessage, CatalogQuote, NetworkDiagnostic, TrustedProxy, InstallPackage, LanguageActivation); none new | as baseline |
| Install rehearsal | the ZIP extracted over a clean copy of the `09e9a8e` tree: panel serves, strip 2 / bar 5 / title present, Arabic switch → RTL, public `/en` still has the storefront phone shell; deleting `wr-admin-mobile.css` removes the chrome and the login page still loads (rollback) | pass |

## NOT tested / limits (honest list)
* **No real iOS/Android device or real soft keyboard.** The keyboard was emulated (focus + viewport resize). Safari-specific behaviour (visual viewport, safe-area insets, `inert`) is unverified.
* **MySQL 5.7 — NOT RUN.** There is no migration in this update.
* **Dashboard**: its two widgets use MySQL-only SQL (`DATE_FORMAT`, `HAVING` without aggregate) and fail on SQLite in the sandbox; unchanged by this update and therefore excluded from the desktop pixel comparison. They were not checked on MySQL.
* **Arabic flows**: the Arabic language, RTL, navigation, drawer and all 49 screens were exercised at three widths; the editing / upload / bulk / analytics *flows* were walked in English only. Arabic screenshots of those screens are included.
* **Not exercised interactively**: photo-review assignment/undo, page headers, homepage sections, team, packaging, settings (loaded and swept for overflow/errors, and screenshotted where noted; not edited). Move up/down buttons exist only on the product gallery; other drag-only orderings (e.g. the product list's reorder mode, homepage sections) were not changed.
* Rehearsal ran HTTP/browser checks only; PHPUnit could not be run inside the rehearsal copy (shared-vendor autoload clash), but the same tests pass in the working tree and in the full suite.
* The Arabic desktop baseline was produced with the site language set to Arabic; the "after" Arabic with the new cookie. Both render the same panel language, but a different mechanism.
* No performance claim is made for the admin; no extra network request was added except two small static files (≈16 KB CSS, ≈16 KB JS) that only load on phones/admin and are cacheable.
