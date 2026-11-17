# Wadi El Rayan — Delivery A: About Us story, Our Team, staff profiles (staging only, nothing deployed)

**Prerequisite:** the *Page Builder Pro* update (2026-11-22, last commit `e9fa7fc`) must already be installed on the target site — the About story uses its graphic elements, template importer and icon library. This ZIP is incremental on top of it. (Delivery B, later, bundles everything in one self-contained release.)

## What is in it
| Area | What you get |
|---|---|
| **About Us story** | The owner's Arabic text built into the About page (hero banner + 4 chapters + 6-stage journey + "Our operations today" + team preview + CTAs) in **all 10 languages**. Every heading, paragraph, image slot, caption and button is editable in *Pages → Edit visually*. Image slots stay empty (and render nothing) until real photographs are chosen. |
| **Our Team page** | `/{locale}/team` — hierarchy chart on desktop (leadership on top, reporting lines below), department list view, vertical expandable branches on phones, RTL mirrored for Arabic and Urdu, keyboard + reduced-motion friendly. |
| **Staff profiles** | `/{locale}/team/{slug}` — portrait (or neutral initials), role, department link, bios, visible contacts only, reporting context only when both people are public. |
| **Admin: Website Content → Team** | **Team members**, **Team departments**, **Team structure** (reorder inside a branch, move to a manager). Search + filters (department, manager, visibility, missing portrait, missing translation, archived). Separate permissions: `team.view`, `team.edit`, `team.publish`, `team.structure`. "Translate to all 10 languages" included. Profiles never create logins. |
| **Sidebar order** | Rearranged by workflow: Catalogue → Inquiries → Website Content → Media → Company & Markets → Languages → Settings → Users & Security → Maintenance → Plugins; Website Content items now run Homepage → Pages → Page headers → Content blocks → Menus → News → Documents → Team. |
| **Builder elements** | *Team preview* (members marked "show on About") and *Capacity evidence* (hidden until a dated, approved figure with unit, scope and source is entered and switched on); timeline items can be marked "ongoing". |

## Compatibility
PHP 8.3, Laravel 13.34 / Filament 5, MySQL 5.7 utf8mb4 (no JSON columns, no window functions, indexes ≤ 480 bytes, FKs `RESTRICT`). **MySQL 5.7 — NOT RUN** (tests and the browser demo ran on SQLite). Please run `php artisan migrate --pretend` on your staging MySQL first.

## Install (staging first)
1. **Back up** the database and the site folder (cPanel → Backup, or `mysqldump` + zip of the site).
2. Unzip `wadi-about-team-update.zip` over the site (same paths as the repo). No `npm run build` is needed (styles are inline).
3. `php artisan migrate --force` — three additive migrations: team tables, team permissions (added to the existing roles, nothing removed), About story seed.
4. `php artisan optimize:clear`
5. Optional, repeatable: `php artisan about:story` (never replaces an existing layout or customised banner).

**What the migration does to the About page:** if the About page still has the installer placeholder text and was never published from the builder, the new story is created **and published**. If the owner already wrote their own About text, the story is created as a **draft** only — the live page is unchanged until you review and press *Publish* in the page editor. Running it twice changes nothing.

## Rollback
* Files: restore the backup of the site folder (or re-unzip the previous release).
* Database: restore the backup. (`php artisan migrate:rollback --step=3` removes the team tables and **deletes team data**; permissions and the About layout are deliberately kept.)
* To drop only the story: *Pages → About → Edit visually → Versions* → revert, or *Use basic version*.

## Owner decision pending — naming origin
The owner's note mentions "Wadi El Natrun"; the company is named Wadi El Rayan (Wadi El Rayan Protected Area is in Fayoum; Wadi El Natrun is in Beheira). The public story says **Fayoum** only. A separate, **disabled** paragraph at the end of the About layout holds the optional line («وحين جاء وقت اختيار اسم المصنع…») in Arabic and English. Please ask the owner which place inspired the name; if it is the Fayoum reserve, enable that section; otherwise replace the text with his own explanation. Nothing was auto-substituted or published.

## Not invented, not seeded
No founder name/title/quote, founding year/village, acreage, tonnage, customers, certificates, rankings or market counts. No fictitious employees are shipped (the demo staff in the screenshots exist only in the staging database). No owner photographs (grove, harvest, sorting line, QC, factory entrance, loading area, branded packs) were supplied to me, so image slots are empty — add them in the page editor. The capacity evidence block is hidden and empty.

## Translations
Arabic is the owner's wording, verbatim. The other nine languages were prepared by me, meaning-faithful but **not reviewed by native speakers** — please have them read before relying on them. Existing manual translations are never overwritten.

## Verified (see TEST-REPORT.md)
New automated tests: 20 (team rules, privacy, 10 locales, admin permissions, About seeding/idempotency). Real-browser demo on a disposable staging database: created an employee with a manager through the admin form → draft not public (404) → preview → publish → public profile opens → email private by default → switched on (mailto present) → switched off (absent from profile, team page, sitemap, Arabic profile and JSON-LD). Screenshots in `screenshots/`.

## Not run / limits
MySQL 5.7; real phone hardware (emulated 390 px only); drag-and-drop reordering in Team structure is arrow-based (no drag); portrait upload with focal point UI is two numeric fields (no visual cropper); public pages for unpublished languages follow the existing language-publication rules.
