# Videos: one line like the slideshow, shaped by the video, first scene as thumbnail (staging-tested, nothing deployed)

## What you get on the public news / exhibition page
* **One line, same as the photo slideshow**: the videos sit in a single horizontal row you **swipe** (phone) or move with **previous / next arrows** (shown when they do not all fit). No more tall stack of dark boxes.
* **Each tile follows its video's shape**: all tiles have the same height; a **landscape** video is wide, a **portrait** one (iPhone vertical) is tall and narrow, a square one square. The shape is read from the video file itself (MP4 / MOV / M4V / 3GP header, including the iPhone's rotation flag, no ffmpeg needed) when it is uploaded — and, for videos already uploaded, the first time the page is shown (then remembered). Other formats (MKV, AVI …) get their shape from the browser as soon as it knows it.
* **First scene as the thumbnail**: each player requests its first frame (`#t=0.001`), so iPhone Safari shows the opening scene instead of a dark box (this needs the byte-range support from the *video player fix* update).
* Only **one video plays at a time** (starting one pauses the others). The "cannot be played → Open or download the video" message from the previous update is kept inside each tile.

## Install
Prerequisite: the video-player-fix update. Back up, unzip `wadi-video-thumbnails-update.zip` over the site (6 files, no migration), then `php artisan optimize:clear && php artisan view:clear`. Rollback: restore the backup of those files (the sizes remembered in the database are harmless).

## Limits (honest)
* The first-scene thumbnail relies on the browser: iPhone Safari and desktop browsers show it for formats they can decode; a video the browser cannot decode (HEVC in Chrome, MKV/AVI) still shows a dark tile with the "open or download" link. There is no server-made poster image (the host has no video converter).
* Portrait detection from the file works for MP4 / MOV / M4V / 3GP. Tested: synthetic and ffmpeg-made files (landscape, portrait, rotated-90° iPhone style, moov-at-end) and a Chromium phone/desktop session with 7 videos (one row, same height, landscape wide, portrait narrow). Not tested on a real iPhone.
