# Page Header / Hero editor — separate update (on top of the corrected cumulative release)

Install (staging first; back up the database OUTSIDE the document root as in the corrected release README):
1. Unzip `wadi-page-header-update.zip` over the site root.
2. `php artisan migrate --force` — two migrations: `2026_11_20_000100_create_page_headers` (new table; no row = the page keeps today's banner) and `2026_11_20_000200_remove_duplicate_starter_titles` (removes ONLY unpublished starter drafts' duplicate title heading created by the earlier "Edit visually", so a page never shows two titles).
3. `php artisan optimize:clear`.
Rollback: restore the backup, or `php artisan migrate:status` then `php artisan migrate:rollback --pretend` before rolling back the latest batch (drops `page_headers`; pages return to their original banner automatically). Prerequisite: the corrected cumulative release (this update was built and tested on top of it).

## What it does
- The banner on EVERY inner page is rendered by one shared component (`x-page-hero`). It now looks up the page's settings; with none, it renders the original markup byte-for-byte (Export/About HTML hashes were identical before/after customising Contact Us).
- **Page Builder:** the outline has a named item **Page Header / Hero** (Outline tab). Clicking the banner in Preview selects the same item and opens its settings. There is no second hero block and no second title. **Edit page header** shortcuts: Pages list row, page edit screen, category list row.
- **Modes:** Use default / Customize this page / Hide banner (hidden keeps one screen-reader `<h1>`).
- **Controls:** media-library image (select/upload/replace/clear, thumbnail), cover/contain, focal point with phone + computer crop preview, optional phone image, image opacity (slider + number), overlay colour and overlay opacity (separate), banner height + padding (computer/phone), alignment (start/centre/end, follows RTL), text colour, breadcrumb toggle, eyebrow/title/subtitle per language (AR…UR; empty = the page's own text). Opacity 0 is stored and rendered as 0.
- **Draft/publish:** every change autosaves a DRAFT and refreshes the preview; visitors see the published copy until **Publish page header**. Publishing the header touches only that page and does not publish the page's builder content. Discard returns to the last published state.
- Stand-alone editor for banners that are not CMS pages: **Website Content → Page headers** (storefront listing pages and product categories). Pages with a custom homepage hero are not touched (the homepage has no managed banner). Product detail pages have no title banner, so none is offered.

## Verified (tests/Feature/PageHeaderTest.php, 9 tests, 78 assertions — PASS; related suites PASS)
Existing pages keep their banner; Contact Us gets a photo + 35% overlay without changing Export/About; drafts invisible to visitors until published; the 0% overlay persists after save/reload and never fades the photo (image opacity separate) or the title; removing the image gives the chosen plain colour and the default photo does not return; hidden mode keeps one title; per-language text and RTL; builder outline item + selection + independent publish; permission check; category banners independent.
Browser proof (screenshots): editor with the Contact Us banner selected and its image/opacity controls; desktop and phone previews at 35% and at 0% (photo still visible, text fully opaque); one banner and one `<h1>`.

## Be aware / not done
- The screenshots use a clearly-labelled STAND-IN image (I do not have your factory photograph); upload your real photo via the media library.
- Eyebrow/title/subtitle and the editor labels exist in English and Arabic; the other 8 admin languages are not translated. The PUBLIC text per language is whatever you type (empty = the page's own translation).
- Click-to-select was driven in a real browser (Chromium): with another item selected, clicking the banner inside the Preview opened the Page Header / Hero panel, and the Outline shows the named item (`tests/Browser/header-click-select.e2e.mjs`, run against the local site; it hard-codes the local admin login, edit before reuse).
- Not run on MySQL 5.7 (same limitation as the corrected release); the new table uses only plain columns and JSON.
