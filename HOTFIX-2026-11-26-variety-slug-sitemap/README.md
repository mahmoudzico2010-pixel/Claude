# Hotfix — variety page `/products/variety/Azizi` returned 404 (and was listed in the sitemap)

**Found from your live sitemap.** The variety stored as `Azizi` (capital A) is listed as `/{lang}/products/variety/Azizi` in all 10 languages, but the route only accepted lowercase slugs, so the page answered **404** — for visitors, for the site's own links to it, and for Google (a "Submitted URL not found" error). Reproduced locally.

## Fix (verified: automated test opens the page and requests up to 400 sitemap URLs, all answer 200/301/302)
* `routes/web.php`: variety and category routes accept letters of either case.
* `SitemapController.php`: never lists a URL whose slug the router would refuse (spaces, accents…).

## Install (no migration, no npm)
1. Back up the site folder. 2. Unzip `wadi-variety-slug-hotfix.zip` over the site (it replaces `routes/web.php` — if you edited that file by hand, change only the two `->where(...)` lines to `[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*`). 3. `php artisan optimize:clear` (clears the route cache). 4. Open `https://wadielrayan.net/en/products/variety/Azizi` — it should show the variety page.

## Recommended data clean-up (your decision, in the admin)
* Rename the variety slug to lowercase `azizi` (*Catalogue → Varieties*). Mixed-case URLs work after this hotfix but lowercase is the convention; old links keep working because both cases open.
* `sliced-green-jalape-os` and `sliced-red-jalape-os` lost the "ñ" in the slug (jalapeños) and `extra-virgin-olive-oil-2` is a numbered duplicate: harmless, but worth tidying later in *Products*.
* Team profiles are in the sitemap (`/team/...`): fine if those people agreed to public profiles, otherwise untick "show on Team page / public".

Not tested: the live site; MySQL 5.7.
