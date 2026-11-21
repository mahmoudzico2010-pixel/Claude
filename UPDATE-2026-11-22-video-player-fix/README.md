# Video player not working on the live site (iPhone) — fix (staging-tested, nothing deployed)

## What your screenshot shows
Players that stay at `00:00 / 00:00`, a dead progress bar, one showing "pause" and the others a play button over a dark box: the browser (iPhone WebKit) could not start the video.

## Most likely cause found in the code (and fixed)
iPhone Safari will only play a video if the server supports **byte-range requests**: it first asks for `Range: bytes=0-1` and expects **HTTP 206 + `Content-Range`**. When the answer is "200 + the whole file" the player stays dead at 00:00.
On hosts where the `storage` symlink is not used (**`MEDIA_PUBLIC_FALLBACK=true`**, common on shared hosting), uploaded files are served by the PHP route `/media/…`. That route used `Storage::response()`, which is a plain stream **with no Range support** (the code comment assumed it had). So every video, and anything that needs seeking, failed on iPhone while still working in desktop Chrome.
**Fix:** `MediaFileController` now serves local files with Symfony's `BinaryFileResponse`, which answers `206 Partial Content` / `Content-Range` / `Accept-Ranges: bytes`. Verified by tests: `bytes=0-1` → 206 `bytes 0-1/…`; middle and open-ended ranges → correct slices; full request → 200 with `Accept-Ranges: bytes`; traversal and executable extensions still refused.

## Also added (so a failing video is never a silent dark box)
If a video errors, or you press play and nothing is decoded after 15 s, the page now shows a message with a link **"Open or download the video"** (all 10 languages). QuickTime/`.m4v` files are announced to the browser as MP4.

## If the live site is NOT using the PHP route
Then the cause is something else, and I need two facts to find it (I cannot reach the live site from here):
1. In the site's `.env`, what is `MEDIA_PUBLIC_FALLBACK`? (`true` → this fix is the cure.)
2. Run this (replace the URL with a video file's address — right-click the video → copy video address):
   `curl -I -H "Range: bytes=0-1" "https://wadielrayan.net/storage/media/news/….mp4"`
   A healthy server answers **`HTTP/… 206`** with `Content-Range: bytes 0-1/…` and `Accept-Ranges: bytes`. If it says 200, a server/CDN setting is dropping ranges (ask the host to enable range requests / disable compression for video).
Also: an iPhone video that is HEVC/ProRes or very large (50+ MB, "moov" atom at the end) can start slowly or not at all on a cellular connection — convert to **MP4 (H.264, "fast start")** for the public site.

## Install
Prerequisite: the video-upload update. Back up, unzip `wadi-video-player-fix-update.zip` (7 files, no migration), then `php artisan optimize:clear && php artisan view:clear`.
Rollback: restore the backup of those files.

## Not tested
Real iPhone playback (no device here), the live server, HEVC/ProRes decoding. The browser-side fallback message was written and syntax-checked, and the gallery markup is covered by tests, but its on-screen behaviour in a failing browser was not exercised here.
