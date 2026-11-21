# Video upload fix — every video format is accepted (staging-tested, nothing deployed)

## What was wrong
Your iPhone video (`copy_ED50D79A-….mov`, 53.5 MB) was refused with *"File of invalid type — Expects video/mp4 or video/webm"*: the library only accepted MP4 and WebM, and an iPhone records **QuickTime (.mov)**.

## What changed
* **Any video format is now accepted for upload**: iPhone `.mov`/`.m4v`/`.qt`, Android `.3gp`/`.3g2`, `.mkv`, `.avi`, `.wmv`/`.asf`, `.flv`/`.f4v`, `.mpg`/`.mpeg`/`.mpe`/`.m2v`, camera `.mts`/`.m2ts`/`.ts`, `.ogv`/`.ogm`, `.mxf`, `.vob`, `.divx`, `.dv`, plus `.mp4` and `.webm`.
* **Still safe:** the file's real content type (read from its bytes, not its name) must be a video (`video/*`) **and** the extension must be on the video list — a script or text file renamed `.mov` is still refused (the large-file uploader checks it; verified by a test). Executable/web extensions (`php`, `exe`, `js`, `html`, `svg` …) can never be video extensions.
* **Everywhere the same rule:** the *Upload new* dialog of any video field, **Upload several videos**, **Upload large files** (the resumable uploader; use it for videos above your server's single-upload limit), and the media-file route that serves them.
* **Playback:** a QuickTime/m4v file is announced to the browser as MP4 (it plays when it holds H.264, the iPhone default "Most Compatible"). Other containers are stored as uploaded.
* The upload dialog now says so: *"Any video format is accepted … Browsers reliably play MP4 (H.264) and WebM only — convert other formats for the best result."* (English and Arabic).

## Honest limits
* **Accepting a file is not the same as every browser playing it.** `.mkv`, `.avi`, `.wmv`, `.flv`, `.mpg` and HEVC-encoded `.mov` (iPhone "High Efficiency") usually do **not** play in Chrome/Firefox; they are stored but visitors may see a blank player. For the public site, use **MP4 (H.264)** or WebM — on iPhone: *Settings → Camera → Formats → Most Compatible*, or convert with any free converter. This update does not transcode videos on the server (the host has no video converter).
* Size: the existing video limit (default 200 MiB, adjustable in *System → Upload limits*) is unchanged; a file larger than the web server's single-request limit (shown in the dialog) must go through *Upload large files*.
* Tested in Chromium on a copy: a phone-size session uploaded `.mov`, `.mkv`, `.avi`, `.3gp` and `.mp4` together through *Upload several videos* — none flagged invalid, all five stored and shown in the news gallery with playable types. Real iPhone files and real playback on iOS/Android were **not** tested.

## Install
Prerequisite: the news-gallery update (it contains `MediaPicker` changes this builds on). Back up, unzip `wadi-video-upload-fix-update.zip` over the site (11 files, no migration), then `php artisan optimize:clear && php artisan config:clear && php artisan view:clear`.
Rollback: restore the backup of those files.
