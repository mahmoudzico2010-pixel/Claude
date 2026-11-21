# Update 2026-11-25 — Footer social icons (alignment + X icon)

* **Alignment bug (my regression from the 2026-11-23 accessibility update):** a tap-target rule `footer a{display:inline-block}` overrode the
  icon boxes' flex centring, so icons sat in the top-left corner. Now `footer a:not(.flex){...}` — the icon boxes are centred again (see footer-social-after.png).
* **X icon:** the footer now shows X (Twitter) when `Settings → Social → X (Twitter)` has a URL (the field already existed but the footer never used it).
  Icons only appear for the networks that have a URL.

Files (2): `resources/views/layouts/public.blade.php`, `resources/views/partials/footer.blade.php`. NOTE: the layout file is the cumulative version
(includes the contrast tokens + tap-target CSS from the previous update), so uploading it supersedes the earlier layout.
Install: upload over the site, clear `storage/framework/views/*`. Rollback: restore those 2 files. No migration.
Tested: `LighthouseHeadersTest` (incl. X icon + no inline-block regression) and a Chromium screenshot at 430px with all 5 networks. Not tested: live site, MySQL 5.7.
