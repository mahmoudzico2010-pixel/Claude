# Update 2026-11-27 — News & exhibition photos always fitted (never cropped)

The news/exhibition page showed the featured photo cropped (`object-cover`): the medal's bottom ("GOLD AWARD") was cut off.
Now the WHOLE photo is always shown, fitted inside its area on a soft background, in:
* the news / exhibition page (hero photo),
* the news list cards, the home-page news cards, and the page-builder "news" element,
* the gallery thumbnails (the full-size slideshow and the lightbox already fitted the photo).

Tall or square photos therefore get soft side bars instead of being cut. The area keeps its 16:9 / 16:10 shape so layouts do not jump.
Files (5 views): see `pkg/`. Install: upload over the site, clear `storage/framework/views/*`. Rollback: restore the 5 files. No migration.
Tested: `NewsImageFitTest` + the news gallery suites (11 tests), Chromium screenshot with a square photo (after-news-page-square-photo.png). Not tested: live site, MySQL 5.7.
Note: the slideshow's slow "drift" animation still zooms slightly while it plays; say if you want it removed so the photo is never enlarged.
