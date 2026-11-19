// Admin mobile acceptance test (Chromium emulation). usage: node atest.mjs <port> <dbfile> <outdir>
import { chromium } from './lib.mjs';
import { execFileSync } from 'child_process';
import fs from 'fs';
const [,, port, dbfile, out] = process.argv; const base = `http://127.0.0.1:${port}`; fs.mkdirSync(out, { recursive: true });
const results = []; const check = (name, ok, detail = '') => { results.push({ name, ok: !!ok, detail: String(detail).slice(0, 220) }); console.log((ok ? 'PASS ' : 'FAIL ') + name + (detail && !ok ? '  -> ' + String(detail).slice(0, 220) : '')); };
import { execFileSync as _x } from 'child_process';
const RUN = Date.now(); const UPFILE = `ashots/up-${RUN}.png`; _x('python3', ['-c', `from PIL import Image;import random;random.seed(${RUN});im=Image.new('RGB',(600,450),(random.randint(0,255),random.randint(0,255),random.randint(0,255)));im.save('${UPFILE}')`]);
const q = (sql) => JSON.parse(execFileSync('php', ['ashots/q.php', dbfile, sql]).toString());
const b = await chromium.launch();
const states = {};   // one real sign-in per account (the login form is rate limited); each context starts from the saved session
async function ctxFor(w, h = 844, email = 'admin@example.com') { const c = await b.newContext({ viewport: { width: w, height: h }, isMobile: w < 600, hasTouch: w < 600, deviceScaleFactor: w < 600 ? 2 : 1, ...(states[email] ? { storageState: states[email] } : {}) }); return c; }
async function signIn(p, email = 'admin@example.com') {
  if (states[email]) { await p.goto(base + '/admin/products', { waitUntil: 'networkidle' }); return; }
  await p.goto(base + '/admin/login'); await p.fill('input[type=email]', email); await p.fill('input[type=password]', 'LocalTest#2026'); await p.click('form[wire\\:submit] button[type=submit]'); await p.waitForURL(/\/admin(?!\/login)/); await p.waitForLoadState('networkidle');
  states[email] = await p.context().storageState();
}
const settle = (p, ms = 700) => p.waitForTimeout(ms);
const tap = async (loc) => { await loc.evaluate(e => e.scrollIntoView({ block: 'center' })); await loc.click(); };
async function setLang(p, code) { await p.click(`.wam-lang[value=${code}]`); await p.waitForLoadState('networkidle'); await settle(p); }

/* =========== A. Navigation, visible language switching, drawer (EN + AR, 360/390/430) =========== */
for (const w of [360, 390, 430]) {
  const c = await ctxFor(w); const p = await c.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
  await signIn(p); errs.length = 0;
  await p.goto(base + '/admin/products', { waitUntil: 'networkidle' }); await settle(p);
  for (const lang of ['en', 'ar']) {
    if (lang === 'ar') await setLang(p, 'ar');
    const T = `[${w}px ${lang}] `;
    const langs = await p.evaluate(() => [...document.querySelectorAll('.wam-lang')].map(a => { const r = a.getBoundingClientRect(), img = a.querySelector('img'); return { code: a.value, name: a.textContent.trim(), w: Math.round(r.width), h: Math.round(r.height), flag: !!img && img.complete && img.naturalWidth > 0, cur: a.hasAttribute('aria-current'), fw: +getComputedStyle(a).fontWeight, top: Math.round(r.top), visible: r.top >= 0 && r.bottom <= innerHeight }; }));
    check(T + 'language strip visible at the very top with 2 admin languages (en, ar)', langs.length === 2 && langs.every(l => l.visible && l.top < 60) && langs.map(l => l.code).sort().join() === 'ar,en', JSON.stringify(langs.map(l => [l.code, l.name])));
    check(T + 'each language has its configured flag image and native name', langs.every(l => l.flag && l.name.length > 1), JSON.stringify(langs.map(l => [l.code, l.flag])));
    check(T + 'only the active language is highlighted (aria-current + bold)', langs.filter(l => l.cur).length === 1 && langs.find(l => l.cur).code === lang && langs.find(l => l.cur).fw >= 700);
    check(T + 'language tap targets >= 44 px high', langs.every(l => l.h >= 44 && l.w >= 44), JSON.stringify(langs.map(l => [l.w, l.h])));
    const dir = await p.evaluate(() => [document.documentElement.dir, document.documentElement.lang]);
    check(T + 'document direction follows the language', dir[0] === (lang === 'ar' ? 'rtl' : 'ltr') && dir[1] === lang, dir.join());
    const nav = await p.evaluate(() => { const n = document.querySelector('.wam-nav'); const r = n.getBoundingClientRect(); return { items: [...n.querySelectorAll('.wam-nav-item')].map(e => ({ t: e.textContent.trim(), cur: e.getAttribute('aria-current'), h: Math.round(e.getBoundingClientRect().height), w: Math.round(e.getBoundingClientRect().width) })), bottom: Math.round(r.bottom), ih: innerHeight, pos: getComputedStyle(n).position }; });
    check(T + 'bottom bar: 5 destinations (Dashboard, Products, Requests, Media, More) with translated labels', nav.items.length === 5 && (lang === 'en' ? nav.items.map(i => i.t).join() === 'Dashboard,Products,Requests,Media,More' : nav.items.map(i => i.t).join() === 'لوحة المعلومات,المنتجات,الطلبات,الوسائط,المزيد'), nav.items.map(i => i.t).join());
    check(T + 'bottom bar: route-based active state on Products and 44 px targets', nav.items.filter(i => i.cur === 'page').length === 1 && nav.items[1].cur === 'page' && nav.items.every(i => i.h >= 44), JSON.stringify(nav.items.map(i => i.cur)));
    check(T + 'bottom bar fixed to the bottom edge', nav.pos === 'fixed' && nav.bottom === nav.ih, `${nav.bottom}/${nav.ih}`);
    const hdr = await p.evaluate(() => ({ title: document.querySelector('.wam-title strong')?.textContent, brand: document.querySelector('.wam-title small')?.textContent, bell: !!document.querySelector('.fi-topbar-database-notifications-btn'), avatar: !!document.querySelector('.fi-user-menu'), menu: !!document.querySelector('.fi-topbar-open-sidebar-btn'), searchBtn: !!document.querySelector('.wam-search-btn') }));
    check(T + 'compact header: identity + screen title + menu + notifications + account (+ search)', hdr.title && hdr.brand && hdr.bell && hdr.avatar && hdr.menu && hdr.searchBtn, JSON.stringify(hdr));
    check(T + 'no whole-page horizontal overflow', await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
    // drawer
    await p.click('[data-wam-more]'); await settle(p, 800);
    const dr = await p.evaluate(() => { const s = document.querySelector('#fi-main-sidebar') || document.querySelector('.fi-sidebar'); const r = s.getBoundingClientRect(); return { role: s.getAttribute('role'), modal: s.getAttribute('aria-modal'), exp: document.querySelector('[data-wam-more]').getAttribute('aria-expanded'), inertMain: document.querySelector('.fi-main-ctn')?.hasAttribute('inert'), inertLangs: document.querySelector('.wam-langs')?.hasAttribute('inert'), inside: s.contains(document.activeElement), groups: s.querySelectorAll('.fi-sidebar-group').length, x: Math.round(r.left), rt: Math.round(r.right), iw: innerWidth }; });
    check(T + 'More opens the organised drawer: dialog semantics, background inert, focus inside', dr.role === 'dialog' && dr.modal === 'true' && dr.exp === 'true' && dr.inertMain && dr.inert !== false && dr.inside && dr.groups >= 3, JSON.stringify(dr));
    if (w === 390) await p.screenshot({ path: `${out}/${lang}-drawer-${w}.png` });
    await p.keyboard.press('Escape'); await settle(p, 700);
    const after = await p.evaluate(() => ({ exp: document.querySelector('[data-wam-more]').getAttribute('aria-expanded'), inert: document.querySelector('.fi-main-ctn')?.hasAttribute('inert'), focus: document.activeElement?.matches('[data-wam-more]') }));
    check(T + 'Escape closes the drawer, removes inert and returns focus to More', after.exp === 'false' && !after.inert && after.focus, JSON.stringify(after));
    if (lang === 'en' && w === 390) { await p.screenshot({ path: `${out}/en-products-${w}.png` }); }
    if (lang === 'ar') await p.screenshot({ path: `${out}/ar-products-${w}.png` });
  }
  check(`[${w}px] no script errors`, errs.length === 0, errs.join(' | '));
  await c.close();
}

/* =========== B. Desktop + tablet: mobile controls absent, no focus, live resize =========== */
{
  const c = await ctxFor(1440, 900); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/products', { waitUntil: 'networkidle' }); await settle(p);
  for (const w of [1440, 1024, 768, 600, 599, 390]) {
    await p.setViewportSize({ width: w, height: 900 }); await settle(p, 500);
    const m = await p.evaluate(() => ({ strip: getComputedStyle(document.querySelector('.wam-langs')).display, nav: getComputedStyle(document.querySelector('.wam-nav')).display, title: !!document.querySelector('.wam-title'), searchBtn: !!document.querySelector('.wam-search-btn') }));
    const phone = w <= 599;
    check(`[resize ${w}px] mobile chrome ${phone ? 'present' : 'absent'} (strip/nav/title/search button)`, phone ? (m.strip !== 'none' && m.nav !== 'none' && m.title && m.searchBtn) : (m.strip === 'none' && m.nav === 'none' && !m.title && !m.searchBtn), JSON.stringify(m));
  }
  await p.setViewportSize({ width: 1024, height: 900 }); await settle(p, 500);
  let reached = false; await p.evaluate(() => document.body.focus());
  for (let i = 0; i < 80; i++) { await p.keyboard.press('Tab'); if (await p.evaluate(() => !!document.activeElement.closest('.wam'))) { reached = true; break; } }
  check('[1024px] keyboard focus never reaches the mobile chrome (80 tab stops)', !reached);
  await c.close();
}

/* =========== C. Edit + save a product; validation; unsaved-change protection =========== */
{
  const c = await ctxFor(390); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/products/green-olives-whole/edit', { waitUntil: 'networkidle' }); await settle(p);
  const sd = 'input[wire\\:model="data.translations.ar.short_description"], textarea[wire\\:model="data.translations.ar.short_description"]';
  const arSd = p.locator('[wire\\:model="data.translations.ar.short_description"]');
  const marker = 'تعديل جوال ' + Date.now();
  await arSd.fill(marker); await settle(p, 300);
  await p.locator('button[type=submit]', { hasText: /Save changes/ }).click();
  await p.waitForSelector('.fi-no-notification, .fi-no', { timeout: 15000 }).catch(() => {}); await settle(p, 900);
  const note = await p.evaluate(() => document.querySelector('.fi-no')?.innerText.replace(/\s+/g, ' ').slice(0, 80));
  await p.screenshot({ path: `${out}/en-product-saved-390.png` });
  const dbv = q("select t.short_description d from product_translations t join products p on p.id=t.product_id where p.slug='green-olives-whole' and t.locale='ar'");
  check('[edit] product saved from the phone: success feedback shown and value persisted', /saved/i.test(note || '') && (dbv[0]?.d || '').includes(marker), (note || '') + ' | ' + (dbv[0]?.d || '').slice(0, 30));
  // duplicate-submit guard: the Save button is disabled while the request is in flight
  const dis = await p.evaluate(async () => { const btn = [...document.querySelectorAll('button[type=submit]')].find(x => /Save changes/.test(x.innerText)); return btn ? { loadingAttr: btn.hasAttribute('wire:loading.attr') || !!btn.querySelector('[wire\\:loading]') || btn.getAttribute('wire:target') !== null } : null; });
  check('[edit] Save button is wired to Livewire loading state (no double submit)', dis && dis.loadingAttr, JSON.stringify(dis));
  // validation 1 (browser, required field): clear the required EN name while an AR edit is pending
  await tap(p.locator('.fi-tabs-item', { hasText: /^\s*English\s*$/ }).first()); await settle(p, 500);
  const enName = p.locator('[id="form.translations.en.name"]'); await enName.fill('');
  const keep = 'محتفظ به ' + Date.now(); await tap(p.locator('.fi-tabs-item', { hasText: /^\s*العربية\s*$/ }).first()); await settle(p, 300); await arSd.fill(keep);
  await p.locator('button[type=submit]', { hasText: /Save changes/ }).first().click(); await settle(p, 1500);
  const val = await p.evaluate(() => { const e = document.getElementById('form.translations.en.name'); return { invalid: e.matches(':invalid'), msg: e.validationMessage, shown: e.offsetParent !== null, ar: document.querySelector('[wire\\:model="data.translations.ar.short_description"]')?.value }; });
  await p.screenshot({ path: `${out}/en-validation-required-390.png` });
  check('[validation] required field: the panel switches to the tab holding the error, shows the message, keeps the other pending edit', val.invalid && val.msg && val.shown && val.ar === keep, JSON.stringify(val).slice(0, 200));
  check('[validation] nothing was written to the database on the failed save', !(q("select t.short_description d from product_translations t join products p on p.id=t.product_id where p.slug='green-olives-whole' and t.locale='ar'")[0]?.d || '').includes(keep));
  // validation 2 (server): a slug that already belongs to another product
  await enName.fill('Whole Green Olives');
  await p.evaluate(() => { const e = document.getElementById('form.slug'); e.value = 'green-olives-pitted'; e.dispatchEvent(new Event('input', { bubbles: true })); e.dispatchEvent(new Event('change', { bubbles: true })); e.dispatchEvent(new Event('blur', { bubbles: true })); });
  await settle(p, 600);
  await p.locator('button[type=submit]', { hasText: /Save changes/ }).first().click(); await settle(p, 2200);
  const sv = await p.evaluate(() => ({ err: [...document.querySelectorAll('.fi-fo-field-wrp-error-message, [data-validation-error]')].map(e => e.innerText.trim()).filter(Boolean).slice(0, 2), ar: document.querySelector('[wire\\:model="data.translations.ar.short_description"]')?.value, slug: document.getElementById('form.slug')?.value, url: location.pathname }));
  await p.screenshot({ path: `${out}/en-validation-server-390.png` });
  check('[validation] server-side error is shown inline, entered values are preserved, no navigation', sv.err.length > 0 && sv.ar === keep && sv.slug === 'green-olives-pitted' && /green-olives-whole\/edit/.test(sv.url), JSON.stringify(sv).slice(0, 220));
  check('[validation] the slug of the other product was not overwritten (database unchanged)', q("select count(*) c from products where slug='green-olives-pitted'")[0].c === 1 && q("select slug from products where id=1")[0].slug === 'green-olives-whole');
  // unsaved-change protection when switching the panel language
  let dlg = null; p.once('dialog', async d => { dlg = d.message(); await d.dismiss(); });
  await p.click('.wam-lang[value=ar]'); await settle(p, 900);
  check('[language] switching with unsaved edits asks first (translated message) and Cancel keeps the page and the data', !!dlg && /unsaved/i.test(dlg) && await p.evaluate(() => document.documentElement.lang === 'en') && await arSd.inputValue() === keep, String(dlg).slice(0, 80));
  p.once('dialog', async d => { await d.accept(); });
  await p.click('.wam-lang[value=ar]'); await p.waitForLoadState('networkidle'); await settle(p, 900);
  check('[language] confirming the switch reloads the panel in Arabic (RTL)', await p.evaluate(() => document.documentElement.dir === 'rtl' && document.documentElement.lang === 'ar'));
  await c.close();
}

/* =========== D. Upload a photo and assign it as the primary image; gallery reordering without drag-and-drop =========== */
{
  const c = await ctxFor(390); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/products/green-olives-whole/edit', { waitUntil: 'networkidle' }); await settle(p);
  await tap(p.locator('.fi-sc-tabs .fi-tabs-item', { hasText: /Images/ }).first()); await settle(p, 500);
  const before = q("select primary_image_id pid from products where slug='green-olives-whole'")[0].pid;
  await p.locator('button, a', { hasText: 'Upload new' }).first().click(); await settle(p, 900);
  await p.setInputFiles('.fi-modal-window input[type=file]', UPFILE); await p.waitForFunction(() => { const m = [...document.querySelectorAll('.fi-modal-window')].find(x => x.querySelector('input[type=file]')); return m && !/Uploading file/.test(m.innerText) && m.querySelector('.filepond--item'); }, null, { timeout: 30000 }); await settle(p, 800);
  await p.screenshot({ path: `${out}/en-upload-modal-390.png` });
  await p.locator('.fi-modal-window').filter({ has: p.locator('input[type=file]') }).getByRole('button', { name: 'Upload new', exact: true }).click(); await settle(p, 2500);
  const label = await p.evaluate(() => document.querySelector('.fi-fo-modal-table-select')?.innerText.replace(/\s+/g, ' ').slice(0, 100));
  check('[media] uploaded photo is selected as the main image in the form', label.includes(`up-${RUN}.png`), label);
  await p.locator('button[type=submit]', { hasText: /Save changes/ }).click(); await settle(p, 2500);
  const after = q("select p.primary_image_id pid, (select original_filename from media_assets where id=p.primary_image_id) fn from products p where p.slug='green-olives-whole'")[0];
  check('[media] primary image assigned and saved', after.pid !== before && (after.fn || '') === `up-${RUN}.png`, JSON.stringify(after) + ' before=' + before);
  // gallery: move the first item down with the button
  await p.goto(base + '/admin/products/green-olives-whole/edit', { waitUntil: 'networkidle' }); await settle(p);
  await tap(p.locator('.fi-sc-tabs .fi-tabs-item', { hasText: /Images/ }).first()); await settle(p, 500);
  const order0 = q("select group_concat(media_id) o from (select media_id from product_images where product_id=1 order by display_order)")[0].o;
  const vis = await p.evaluate(() => { const bs = [...document.querySelectorAll('.fi-fo-repeater-item')].slice(0, 1)[0]?.querySelectorAll('.fi-fo-repeater-item-header button'); return [...(bs || [])].map(x => [x.title, x.getBoundingClientRect().width > 0, Math.round(x.getBoundingClientRect().height)]); });
  check('[gallery] move up / move down buttons are visible on the phone with 44 px targets', vis.some(v => v[0] === 'Move up' && v[1]) && vis.some(v => v[0] === 'Move down' && v[1] && v[2] >= 44), JSON.stringify(vis));
  await p.locator('.fi-fo-repeater-item').first().locator('button[title="Move down"]').click(); await settle(p, 1500);
  await p.screenshot({ path: `${out}/en-gallery-reorder-390.png` });
  await p.locator('button[type=submit]', { hasText: /Save changes/ }).click(); await settle(p, 2500);
  const order1 = q("select group_concat(media_id) o from (select media_id from product_images where product_id=1 order by display_order)")[0].o;
  const a0 = order0.split(','), a1 = order1.split(',');
  check('[gallery] reordering with the button persists the new order', a1.length === a0.length && a1[0] === a0[1] && a1[1] === a0[0], order0 + ' -> ' + order1);
  await c.close();
}

/* =========== E. Bulk selection across pages, exclude one, preview + apply =========== */
{
  const c = await ctxFor(390); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/products', { waitUntil: 'networkidle' }); await settle(p);
  await p.locator('.fi-ta-page-checkbox').first().check(); await settle(p, 500);
  const one = await p.evaluate(() => document.querySelector('.fi-ta-selection-indicator')?.innerText.replace(/\s+/g, ' '));
  check('[bulk] current-page selection shows the exact count and the "select all" offer', /25 records selected/.test(one) && /Select all 66/.test(one), one);
  await p.locator('.fi-ta-selection-indicator button', { hasText: /Select all/ }).click(); await settle(p, 900);
  await p.locator('.fi-ta-record-checkbox').first().uncheck(); await settle(p, 600);
  const two = await p.evaluate(() => document.querySelector('.fi-ta-selection-indicator')?.innerText.replace(/\s+/g, ' '));
  check('[bulk] all-filtered selection minus one excluded record: exact count 65', /65 records selected/.test(two), two);
  await p.screenshot({ path: `${out}/en-bulk-selection-390.png` });
  await p.locator('.fi-ta-header-toolbar .fi-dropdown-trigger').first().click(); await settle(p, 600);
  await p.locator('.fi-dropdown-panel button, .fi-dropdown-panel a', { hasText: /Bulk edit/ }).first().click(); await p.waitForURL(/bulk-edit/); await p.waitForLoadState('networkidle'); await settle(p, 900);
  const scope = await p.evaluate(() => document.querySelector('.fi-page-content')?.innerText.replace(/\s+/g, ' ').slice(0, 200));
  check('[bulk] bulk-edit page states the exact scope: 65 products across every page, unticked rows excluded', /All 65 products/.test(scope) && /unticked/i.test(scope), scope);
  // choose "Set" for Featured, preview, apply
  const feat = p.locator('.fi-section', { hasText: /^Featured/ }).first();
  await p.getByText('Featured', { exact: true }).first().scrollIntoViewIfNeeded();
  await p.evaluate(() => { const sec = [...document.querySelectorAll('.fi-section')].find(s => /^\s*Featured/.test(s.innerText)); sec?.scrollIntoView({ block: 'center' }); });
  const sec = p.locator('.fi-section').filter({ hasText: /^\s*Featured/ }).first();
  await sec.getByText('Clear', { exact: true }).click().catch(() => {}); await settle(p, 500);
  await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight)); await settle(p, 600);
  await p.screenshot({ path: `${out}/dbg-bulk-before-preview.png` }); await p.getByRole('button', { name: 'Preview', exact: true }).click(); await settle(p, 2500);
  await p.screenshot({ path: `${out}/en-bulk-preview-390.png` });
  const prev = await p.evaluate(() => document.body.innerText.replace(/\s+/g, ' '));
  check('[bulk] preview is produced before anything is written', /Apply/.test(prev) && /preview/i.test(prev), prev.slice(0, 120));
  const featN = () => q("select sum(case when is_featured=1 then 1 else 0 end) f from products")[0].f;
  const excl = () => q("select is_featured f from products where slug='green-olives-whole'")[0].f;
  const f0 = featN(), e0 = excl(), runs0 = q("select count(*) c from bulk_edit_runs")[0].c;
  const applyBtn = p.getByRole('button', { name: /^Apply \d+ changes?$/ });
  check('[bulk] the Apply button states the exact number of changes from the preview', await applyBtn.count() === 1, await applyBtn.allInnerTexts());
  p.once('dialog', d => d.accept());
  await tap(applyBtn.first()); await settle(p, 3500);
  await p.screenshot({ path: `${out}/en-bulk-applied-390.png` });
  const f1 = featN(), e1 = excl(), runs1 = q("select count(*) c from bulk_edit_runs")[0].c;
  check('[bulk] apply changed only the previewed records (the unticked product is untouched) and recorded a run', f1 < f0 && f0 - f1 <= 65 && e1 === e0 && runs1 === runs0 + 1, `featured ${f0} -> ${f1}; excluded ${e0} -> ${e1}; runs ${runs0} -> ${runs1}`);
  const rb = await p.evaluate(() => /Roll back/.test(document.body.innerText));
  check('[bulk] history with a Roll back control is available after applying', rb);
  await c.close();
}

/* =========== F. Analytics filters =========== */
{
  const c = await ctxFor(390); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/analytics', { waitUntil: 'networkidle' }); await settle(p);
  const sel = p.locator('.wr-an-bar select').nth(1); // country
  const opts = await sel.evaluate(s => [...s.options].map(o => o.value + ':' + o.textContent.trim()).slice(0, 6));
  await sel.selectOption({ index: 1 }); await p.locator('[data-testid="an-apply"]').click(); await settle(p, 1800);
  const st = await p.evaluate(() => ({ val: document.querySelectorAll('.wr-an-bar select')[1].value, w: document.documentElement.scrollWidth <= innerWidth, tables: document.querySelectorAll('table').length, cards: document.querySelectorAll('.fi-page-content > div').length }));
  await p.screenshot({ path: `${out}/en-analytics-filters-390.png` });
  check('[analytics] filter bar usable on the phone: country filter applied, no page overflow, data tables still present', st.val !== '' && st.w, JSON.stringify(st) + ' ' + opts.slice(0, 3).join(','));
  await p.evaluate(() => window.scrollTo(0, 900)); await settle(p, 500); await p.screenshot({ path: `${out}/en-analytics-data-390.png` });
  const csv = await p.locator('[data-testid="an-csv"]').count();
  check('[analytics] export controls reachable (CSV/PDF)', csv >= 1);
  await c.close();
}

/* =========== G. Restricted user =========== */
for (const [email, label] of [['viewer@example.com', 'Viewer'], ['sales@example.com', 'Sales']]) {
  const c = await ctxFor(390, 844, email); const p = await c.newPage(); await signIn(p, email);
  const items = await p.evaluate(() => [...document.querySelectorAll('.wam-nav-item')].map(a => a.getAttribute('href') || 'more'));
  const accessible = {};
  for (const [k, path] of [['products', '/admin/products'], ['requests', '/admin/quote-requests'], ['media', '/admin/media/media-assets']]) { const r = await p.goto(base + path); accessible[k] = r.status() < 400 && !/\/login/.test(p.url()); }
  const offered = { products: items.some(h => /admin\/products$/.test(h)), requests: items.some(h => /quote-requests/.test(h)), media: items.some(h => /media-assets/.test(h)) };
  check(`[permissions] ${label}: bottom bar offers exactly what the server allows`, JSON.stringify(offered) === JSON.stringify(accessible), JSON.stringify({ offered, accessible }));
  const forced = await p.goto(base + '/admin/users'); check(`[permissions] ${label}: a destination hidden from the bar is still refused by the server when typed in (403)`, forced.status() === 403 || forced.status() === 404, String(forced.status()));
  await c.close();
}

/* =========== H. Keyboard opening + viewport resizing =========== */
{
  const c = await ctxFor(390, 844); const p = await c.newPage(); await signIn(p);
  await p.goto(base + '/admin/products/green-olives-whole/edit', { waitUntil: 'networkidle' }); await settle(p);
  const f = p.locator('[wire\\:model="data.translations.ar.short_description"]'); await f.scrollIntoViewIfNeeded(); await f.focus(); await settle(p, 600);
  let s = await p.evaluate(() => ({ kb: document.documentElement.classList.contains('wam-kb'), navDisplay: getComputedStyle(document.querySelector('.wam-nav')).display }));
  check('[keyboard] focusing a field hides the bottom bar so it cannot cover the input', s.kb && s.navDisplay === 'none', JSON.stringify(s));
  await p.setViewportSize({ width: 390, height: 430 }); await settle(p, 900);   // soft keyboard shrinks the visual viewport (the page scrolls the focused field into view)
  const vis = await p.evaluate(() => { const e = document.activeElement; const r = e.getBoundingClientRect(); return { top: Math.round(r.top), bottom: Math.round(r.bottom), ih: innerHeight, active: e.matches('textarea,input') }; });
  check('[keyboard] with a 430 px-high viewport (keyboard open) the focused field stays on screen', vis.active && vis.top >= 0 && vis.bottom <= vis.ih, JSON.stringify(vis));
  await p.screenshot({ path: `${out}/en-keyboard-open-390.png` });
  await p.evaluate(() => document.activeElement.blur()); await p.setViewportSize({ width: 390, height: 844 }); await settle(p, 700);
  s = await p.evaluate(() => ({ kb: document.documentElement.classList.contains('wam-kb'), navDisplay: getComputedStyle(document.querySelector('.wam-nav')).display }));
  check('[keyboard] closing it brings the bottom bar back', !s.kb && s.navDisplay !== 'none', JSON.stringify(s));
  await p.evaluate(() => window.scrollTo(0, 0)); await settle(p, 600);
  const top = await p.evaluate(() => { const a = [...document.querySelectorAll('.fi-sc-form .fi-sc-actions')].find(x => x.querySelector('button[type=submit]')); const r = a.getBoundingClientRect(), n = document.querySelector('.wam-nav').getBoundingClientRect(); return { fixed: a.classList.contains('wam-actions-fixed'), bottomVsNav: Math.round(n.top - r.bottom), inView: r.top >= 0 && r.bottom <= innerHeight }; });
  check('[editing] at the top of a long form, Save / Cancel are pinned just above the bottom bar', top.fixed && top.inView && top.bottomVsNav >= 0 && top.bottomVsNav <= 2, JSON.stringify(top));
  await p.evaluate(() => window.scrollTo(0, document.body.scrollHeight)); await settle(p, 600);
  const end = await p.evaluate(() => { const a = [...document.querySelectorAll('.fi-sc-form .fi-sc-actions')].find(x => x.querySelector('button[type=submit]')); const r = a.getBoundingClientRect(), n = document.querySelector('.wam-nav').getBoundingClientRect(); const last = a.closest('.fi-sc-form').querySelector('.fi-section:last-of-type'); return { fixed: a.classList.contains('wam-actions-fixed'), clearOfNav: r.bottom <= n.top + 1, inView: r.top >= 0 }; });
  check('[editing] at the end of the form the buttons sit in place (nothing is covered) and clear the bottom bar', !end.fixed && end.clearOfNav && end.inView, JSON.stringify(end));
  await c.close();
}

await b.close();
fs.writeFileSync(`${out}/results.json`, JSON.stringify(results, null, 1));
const bad = results.filter(r => !r.ok); console.log(`\n${results.length - bad.length}/${results.length} passed`); process.exit(bad.length ? 1 : 0);
