# Small fix — intro poster image sized to the screen

* The intro's still image was always the 1920-px file (also on phones) and the `<video>` carried a second `poster` address, so phones could download two large images. Now: one image with `srcset` (phone ≈ 400 px WebP, desktop 1920), no second poster.
* Tested locally (intro with a test video + poster): phone fetched the 400 px WebP only, desktop the 1920 px one; video plays; no errors; 70 related tests pass.
* Install: unzip over the site, `php artisan optimize:clear`, `php artisan cache:clear`. Replaces one file: `resources/views/home/intro.blade.php`. Rollback: restore the file.
* Honest limit: on the test copy the mobile LCP element was the first hero slide, not the intro. Whether the intro poster is the LCP on your live page is **not known**; this fix removes wasted bytes either way.
