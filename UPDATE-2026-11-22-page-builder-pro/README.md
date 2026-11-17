# Wadi El Rayan — Page Builder Pro (staging only, nothing deployed)

Based on the product-page update (commit 41f80f9). No database migration. No `npm run build` needed (new styles are inline).

## Install (staging first)
```
unzip wadi-page-builder-pro-update.zip over the site      # or: git fetch wadi-page-builder-pro.bundle
php artisan optimize:clear
```
Optional: `wadi-page-builder-pro-tests.zip` (tests only). Open any page → **Edit visually**.

## What is new
| Area | What you get |
|---|---|
| **Graphic elements** (new group) | Icon (33 icons, picker), Icon box, Icon list, Shape divider (wave/curve/tilt/triangle/zigzag/arc), CTA banner (gradient), Testimonial, Accordion/FAQ, Timeline, Progress bar, Badge, Image box, Star rating, Social icons. All 10 languages per text; links are checked (only http/https/mailto/tel/relative/#). |
| **Drag & drop (SortableJS)** | Drag an element or template from the left panel **onto the live page**: the target block is outlined in green and a green line shows exactly where it will land (between blocks, or side by side in columns). Only legal targets light up. The **Structure** tab is a nested tree: drag the ⠿ handle to reorder or move into another container, with animation. Click still adds to the selected block. |
| **Left panel tabs** | Elements (search + tiles) · Structure (tree) · Templates (8 ready sections + your own) |
| **Templates** | Hero with call to action, 3 features, statistics band, 2 testimonials, FAQ accordion, quote banner, timeline, wave divider (English + Arabic text; other languages start with English, edit in the right panel). Right-click → **Save as template** keeps your own block for reuse. |
| **Return to basic version** | Bar above the editor: **Use basic version** (website shows the page's original content; your layout is kept, nothing deleted) · **Use builder layout** · **Reset layout to original content** (undoable) · **Open basic editor**. |
| **Pro tools** | Right-click menu (duplicate, copy, paste, move up/down, hide/show, save as template, delete) in the preview and in the tree · Ctrl+Z / Ctrl+Shift+Z / Ctrl+Y · Ctrl+S save · Ctrl+D duplicate · Ctrl+C / Ctrl+V · Delete · Esc. Shortcuts also work while the cursor is inside the preview. |

## Verified
- New `BuilderProToolsTest` (6) and `BuilderGraphicElementsTest` (2) pass; together with VisualPageBuilder, PageHeader, StarterTitleMigration, FilamentStrictAuthorization, InterfaceTexts: **50 tests / 3999 assertions pass**.
- Real browser (Playwright/Chromium, `screenshots/pro-*.png`, scripts `dnd.mjs`, `dnd2.mjs`): drag an Icon box onto the live page → outline + line shown → element created in that column and selected; right-click menu in the preview; tree drag reorders sections (server `move` call); Ctrl+Z undoes it.

## Honest limits (please read)
- "Better than Elementor" is not reached in one pass. Still missing: free-form absolute positioning, global styles / design tokens panel, a pop-up/animation timeline, saved global widgets that update everywhere, per-element hover/motion effects, undo of individual field edits inside a single element (undo is per change step), drag between the Elements tab and the Structure tree in one gesture (they are separate tabs; drag from Elements to the live page instead).
- Not tested on a touch screen; the Move up/down items in the right-click menu are the keyboard/touch route.
- Saved templates are stored in site settings (shared by all editors).
- MySQL 5.7: NOT RUN (tests use SQLite). The dashboard widgets error on SQLite only (MySQL-specific SQL, pre-existing, unrelated).
- Sortable.js 1.15.7 (MIT) is bundled in `public/vendor/sortablejs/` — no CDN, no new Composer/npm dependency.
