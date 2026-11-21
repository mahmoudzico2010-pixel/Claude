# News & exhibitions — photo / video gallery + exhibition period (staging-tested, nothing deployed)

## What you get
1. **Photo gallery and video gallery for every news item AND exhibition** (one editor, `News & Exhibitions → edit → "Photo & video gallery"`):
   * **Photos**: pick from the media library or *Upload new*, add as many as you like, **drag to reorder** (on a phone use the up/down arrows). Remove an item and the file stays in the Media Library.
   * **Videos**: the same, with video files.
   * **Upload several photos (or videos) together**: the section header has *Upload several photos* / *Upload several videos* — pick or drop up to 40 photos (10 videos) at once; each is checked and stored like a normal upload (size, type, pixel limits), an identical file already in the library is reused, a file already in the list is not added twice, a refused file is named in a message while the others are added. Save to keep them.
   * Public page (`/{lang}/news/{slug}`), under the article: a responsive photo grid (2/3/4 columns) with **lightbox** (click → full-size photo, Esc to close, arrow keys and swipe to browse, focus returns to the thumbnail, RTL-aware), and video players (`preload="metadata"`, controls, `playsinline`). Photos are lazy-loaded with responsive sizes. No gallery → nothing is rendered and no extra script is loaded. Drafts and their galleries stay private. Labels are translated in all 10 languages.
   * The Media Library now lists "News / exhibition gallery" under *Used in* for files used in a gallery.
2. **Exhibition period (date range) — not only one date.**
   * The panel already had *Start date / End date*, but the **Exhibition section was hidden when editing an existing exhibition** (a bug: the form compared the type as text while an existing record holds it as an enum). Fixed — it shows when the form is *Exhibition*, labelled **From / To**, with: end must not be before start, an end date needs a start date, one-day events need only *From*.
   * Public display as a range: **17–21 Oct 2026**, **28 Sep – 2 Oct 2026**, **29 Dec 2026 – 3 Jan 2027**, single day **17 Oct 2026**; the label reads **Dates** (period) or **Date** (single day) in all languages; the same range is on the news list, the home news block and in the structured data (`startDate` / `endDate`).

## Install
1. Back up the database and the site. 2. Unzip `wadi-news-gallery-update.zip` over the site (25 files). 3. `php artisan migrate --force` — **one additive migration** (new table `news_post_media`; nothing existing is altered). 4. `php artisan optimize:clear && php artisan view:clear`.
Rollback: restore the backup of the changed files; the new table can stay (harmless) or `php artisan migrate:rollback --step=1` drops it (deletes gallery links only, never media files).

## Limits
* Captions: photos use the **alt text of the media file** (editable per language in the Media Library); there is no separate per-photo caption field.
* Videos need to be web formats the browser plays (MP4/WebM) — large files should be uploaded with *Upload large files* first and then picked from the library. No video poster image is generated (the browser shows the first frame).
* The structured data lists the event dates; gallery images are not added to the JSON-LD.
* Tested in a Chromium copy with demo data (SQLite); MySQL 5.7 — NOT RUN (the migration uses plain columns).

## Thumbnails: fitted and animated (added)
* **Fitted:** each thumbnail shows the **whole photo** (`object-fit: contain`) on a blurred, enlarged copy of the same picture, so portrait and landscape shots fit one frame **without cropping heads or text**. The blurred copy re-uses the thumbnail already downloaded (no extra request).
* **Animated:** a shimmer placeholder while a photo loads, the picture fades/zooms in, tiles **reveal one after another** as the gallery scrolls into view, a gentle zoom + expand icon on hover/focus, and the lightbox fades in with a **slide/fade between photos** (direction follows RTL).
* **Accessible / safe:** with *reduce motion* set, or without JavaScript, every tile is visible immediately and nothing animates; the animation only runs when the script is active.
