# Wadi El Rayan — coordinated update (staging only)

Nothing here was deployed. Production is yours to do. Based on release 2026-10-31 (d85897c).
Contents: `wadi-update-2026-11-16.bundle` (git commits 2edcb30…7dd2cfa), `changed-files.zip` (every added/changed non-test file, same paths as the repo), `screenshots/`.

## Commands (in this order, on staging first)
```
git fetch wadi-update-2026-11-16.bundle    # or unzip changed-files.zip over the site
php artisan migrate --force                # 3 additive migrations: GTM default, frame tables, catalogue hierarchy
npm ci && npm run build                    # new Tailwind classes in views
php artisan catalogue:hierarchy            # repeatable; prints products needing review
php artisan optimize:clear
```
`catalogue:hierarchy` and the migration are fill-only: ids, slugs, URLs, images and any text you edited are kept. Re-running never creates duplicates.

## Filament menu paths
| What | Where |
|---|---|
| Edit a page visually | Website Content → Pages → row action **Edit visually** (also a button on the page's edit screen). Double-click a heading/paragraph on the canvas to type in place. Draft → **Publish**; the public page does not change until you publish. |
| New blocks | In the visual editor library: Export map, Country flags, Request a quote, Icon, Chart (+ existing Product grid, Categories, Custom HTML/CSS for users with the sensitive-settings permission) |
| Global product frame | Catalogue → **Product image frame** (No frame / Use a frame, design, padding, live preview on a real product) |
| Frame designs (upload / replace / hide) | Catalogue → **Frame designs** (transparent PNG/WebP via the media library). The supplied emerald-and-gold WebP is the first design. |
| Per-product frame | Catalogue → Products → edit → **Images** tab → "Frame for this product": Use the site setting / A different frame / No frame |
| Families, subcategories, order, publish, header image, texts | Catalogue → **Categories** (parent = family; display order; image = header image; translations per language) |
| Export markets (map + list) | Company & Markets → Export markets |
| Developer credit, language bar mode, per-language flags | Settings → **Header & footer** |
| Hero slides (now up to 30) | Homepage Manager → Hero |
| GTM | Settings → Analytics tab, field "Google Tag Manager ID" (GTM-WLVMLZTN set by migration only if the field was empty) |

## What was verified (staging-style, local)
- Frames: same product on the global frame, a custom frame, and no frame — `screenshots/frame-*` (desktop+mobile). Product photos are never modified; the overlay is a separate image above the photo.
- Catalogue: 5 families / 13 subcategories in the agreed order, names in 10 languages, family page aggregates each product once, subcategory shows only its own, empty subcategory shows a localized availability enquiry; 18 pages × 10 locales return 200 (PHPUnit).
- Page builder: starter draft from the page's own content, inline edit stays draft until publish, new blocks render, a stale editor is refused (409).
- Tests: new — ProductFrameTest, CatalogueHierarchyTest, VisualPageBuilderTest, SiteChromeTest all pass. Full suite: remaining failures (LanguageActivation 3, QuoteRequest 7, ContactMessage 1, CatalogQuote 1, InstallPackage 1) fail identically on the untouched baseline in this sandbox (e.g. e-mail validation needing DNS), so they are not caused by this update.

## Needs your decision / not done — please read
1. **Product mapping to review** (left on the family, not moved): kalamata-style-olives, jalapeno-peppers-sliced, cherry-peppers, guindilla-peppers, pickled-cucumbers (Gherkins vs Cucumber). Only 6 products with unambiguous slugs were moved (green olives ×3, natural black olives, EVOO, mixed pickles). A "black olives" item whose type is not stated needs your call. This sandbox holds the sample data, not your live catalogue — run `catalogue:hierarchy` on staging and read its REVIEW lines.
2. **19 header images are not chosen.** No approved media library was available here, so every family/subcategory (and the Products landing) shows a brand panel with a leaf icon. Choose images in Categories → Image. The Products landing page has no image setting yet.
3. **Translations are machine-prepared** and are not marked human-reviewed. Category intros use a generic localized sentence; no facts were invented.
4. Visual builder gaps vs the brief: page-level undo/redo, versions, device previews and permissions are the existing builder's; drag-and-drop from the library onto the live canvas, an inline-edit toolbar, and reusable (saved) blocks for Hero/Category cards/Product grid are **not** built. Legacy-page conversion is the one-time starter draft only. Not exercised in a real browser: the inline double-click edit and the Filament screens (covered by Livewire/PHPUnit tests only) — please click through on staging.
5. The old three-level Products menu (variety → forms) was replaced by family → subcategory per the brief; variety pages still exist by URL.
6. I could not rebuild the full install ZIP / update package (packaging assets and baselines from earlier releases were lost in a container reset), hence the bundle + changed-files zip.
7. Demo product photos and a demo frame used in screenshots exist only in my local database, not in the package.
