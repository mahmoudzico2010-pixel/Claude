# Test report — Delivery A (About + Team)

Environment: PHP 8.3.6, Laravel 13.34, SQLite in-memory for PHPUnit; browser demo on a disposable SQLite staging copy with Chromium (desktop 1400 px, phone 390 px). **MySQL 5.7: NOT RUN.**

## New automated tests (20, all pass)
* TeamTest (9): self/indirect reporting loops refused (members + departments); several roots; manager with reports cannot be archived until moved; drafts/archived/hidden-manager never public; contacts private until switched on and absent from HTML, JSON-LD, team page and sitemap; `javascript:` links never rendered; all 10 locales render with right direction and one H1; department filter; preview limited to staff; migrations run twice change nothing.
* TeamAdminTest (5): list/filters/structure page; edit form saves translations and cannot change status; publish/move/archive are permission-checked on the server (content editor cannot publish or place people); viewers cannot open edit screens; publishing creates no login.
* AboutStoryTest (6): story live in all 10 languages with one H1 and real localized links; naming-origin paragraph stays disabled, no unverified claims; re-running never duplicates or overwrites edits; sites with their own About text get a draft only; team preview shows only published "show on About" members, evidence block hidden until complete; banner draft does not reach visitors until published and other pages are unaffected.
Updated: FilamentStrictAuthorizationTest (new resources/page in the matrix), PageHeaderTest (About now carries the story banner).

## Full suite
1,299 passed, 10 skipped, **19 failed** — the same 19 that fail on the untouched baseline in this sandbox (QuoteRequest 7, NetworkDiagnostic 6 [full-suite only], LanguageActivation 3, CatalogQuote 1, ContactMessage 1, InstallPackage 1).

## Browser demonstration (disposable staging DB) — `e2e-demo-create-publish-hide-email.mjs`
1 draft → public profile 404 · 2 Publish from the admin list · 3 public profile 200, manager shown, e-mail and phone **absent** · 4 e-mail switched on → `mailto:` present · 5 e-mail switched off → address absent from profile, team page, sitemap, Arabic profile and JSON-LD.
Overflow check: no horizontal scroll at 390 px on About, Team and profile in EN, AR and UR; exactly one H1 on each.

## Not run
MySQL 5.7; physical phones/tablets; screen-reader pass; portrait upload through the media modal (no staff photographs were supplied); real translations review by native speakers.
