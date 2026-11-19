import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const BASE = process.argv[2] || 'http://127.0.0.1:8020';
const results = []; const check = (name, ok, detail = '') => { results.push({ name, ok: !!ok, detail }); console.log((ok ? 'PASS ' : 'FAIL ') + name + (detail ? '  — ' + detail : '')); };
const b = await chromium.launch();
async function ctxFor(w, h = 844, extra = {}) { const c = await b.newContext({ viewport: { width: w, height: h }, isMobile: w < 600, hasTouch: w < 600, reducedMotion: 'reduce', ...extra }); await c.addCookies([{ name: 'wr_consent', value: 'denied', url: BASE }]); return c; }
const vis = (p, sel) => p.locator(sel).first().isVisible().catch(() => false);

/* ---------- phone widths ---------- */
for (const w of [360, 390, 430]) {
  const c = await ctxFor(w); const p = await c.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(BASE + '/en/products/green-olives-whole', { waitUntil: 'networkidle' });
  const T = `[${w}px] `;
  check(T + 'language strip shows all enabled languages', (await p.locator('.wrm-lang').count()) === 10, 'count=' + await p.locator('.wrm-lang').count());
  const dims = await p.evaluate(() => [...document.querySelectorAll('.wrm-lang')].map(a => { const r = a.getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height)]; }));
  check(T + 'language tap targets >= 44x44', dims.every(([ww, hh]) => ww >= 44 && hh >= 44), JSON.stringify(dims[0]));
  const act = await p.evaluate(() => { const a = document.querySelector('.wrm-lang[aria-current]'); const l = document.querySelector('.wrm-langs-list'); const r = a.getBoundingClientRect(), lr = l.getBoundingClientRect(); return { code: a.getAttribute('lang'), inView: r.left >= lr.left - 1 && r.right <= lr.right + 1 }; });
  check(T + 'current language highlighted and scrolled into view', act.code === 'en' && act.inView, JSON.stringify(act));
  check(T + 'more-languages hint is shown', await p.evaluate(() => document.querySelector('[data-wrm-langs]').hasAttribute('data-more-end')));
  check(T + 'font size of language names >= 12px', await p.evaluate(() => parseFloat(getComputedStyle(document.querySelector('.wrm-lang-name')).fontSize) >= 12));
  const nav = await p.evaluate(() => [...document.querySelectorAll('.wrm-nav .wrm-nav-item')].map(a => { const r = a.getBoundingClientRect(); return { t: a.textContent.trim(), w: Math.round(r.width), h: Math.round(r.height), cur: a.getAttribute('aria-current') }; }));
  check(T + 'bottom nav has 5 items, each >= 44px tall', nav.length === 5 && nav.every(n => n.h >= 44 && n.w >= 44), nav.map(n => n.t).join('|'));
  check(T + 'bottom nav marks Products active on a product page', nav.find(n => n.t === 'Products')?.cur === 'page');
  const navBox = await p.evaluate(() => { const r = document.querySelector('.wrm-nav').getBoundingClientRect(); return { bottom: Math.round(innerHeight - r.bottom), h: Math.round(r.height) }; });
  check(T + 'bottom nav sits at the bottom edge', navBox.bottom === 0, JSON.stringify(navBox));
  const wa = await p.evaluate(() => { const a = document.querySelector('a[aria-label="WhatsApp"]'); if (!a) return null; const r = a.getBoundingClientRect(), n = document.querySelector('.wrm-nav').getBoundingClientRect(); return { gap: Math.round(n.top - r.bottom) }; });
  check(T + 'WhatsApp button clears the bottom bar', wa === null || wa.gap >= 4, JSON.stringify(wa));
  check(T + 'no horizontal page overflow', await p.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), await p.evaluate(() => document.documentElement.scrollWidth + ' vs ' + innerWidth));
  check(T + 'the desktop header is hidden', !(await vis(p, 'header[data-wr-site-header]')));
  check(T + 'exactly one <h1> on the page', (await p.locator('h1:visible').count()) === 1, 'h1=' + await p.locator('h1:visible').count());
  check(T + 'no script errors', errs.length === 0, errs.join(';'));
  if (w === 390) {
    // More sheet: open, focus, Tab trap, Escape, focus return
    const opener = p.locator('.wrm-nav [data-wrm-open]'); await opener.focus(); await opener.click(); await p.waitForTimeout(350);
    check(T + 'More sheet opens and takes focus', await p.evaluate(() => !!document.querySelector('#wrm-more:not([hidden])') && document.activeElement.classList.contains('wrm-close')));
    await p.keyboard.press('Tab'); await p.keyboard.press('Tab');
    check(T + 'focus stays inside the sheet while tabbing', await p.evaluate(() => !!document.activeElement.closest('#wrm-more')));
    check(T + 'page behind is inert while the sheet is open', await p.evaluate(() => document.querySelector('main').hasAttribute('inert')));
    await p.keyboard.press('Escape'); await p.waitForTimeout(350);
    check(T + 'Escape closes the sheet and returns focus to the opener', await p.evaluate(() => document.querySelector('#wrm-more').hidden && document.activeElement.hasAttribute('data-wrm-open')));
    check(T + 'inert removed after closing', await p.evaluate(() => !document.querySelector('main').hasAttribute('inert')));
    await p.locator('.wrm-bar [data-wrm-open]').click(); await p.waitForTimeout(300);
    const links = await p.locator('#wrm-more .wrm-more-link').allTextContents();
    check(T + 'More lists the remaining navigation', links.length >= 5, links.map(s => s.trim()).join('|'));
    await p.locator('#wrm-more .wrm-backdrop').click({ position: { x: 20, y: 40 } }); await p.waitForTimeout(350);
    check(T + 'backdrop tap closes the sheet', await p.evaluate(() => document.querySelector('#wrm-more').hidden));
    // keyboard: bottom nav hides while a field is focused (quote page)
  }
  await c.close();
}

/* ---------- language switching keeps the equivalent page ---------- */
{
  const c = await ctxFor(390); const p = await c.newPage();
  await p.goto(BASE + '/en/products/green-olives-whole', { waitUntil: 'networkidle' });
  await p.locator('.wrm-lang[lang="es"]').click(); await p.waitForLoadState('networkidle');
  check('[390px] switching to Español opens the equivalent product page (not the homepage)', new URL(p.url()).pathname === '/es/products/green-olives-whole', new URL(p.url()).pathname);
  check('[390px] Español becomes the highlighted language', await p.evaluate(() => document.querySelector('.wrm-lang[aria-current]').getAttribute('lang')) === 'es');
  await p.locator('.wrm-lang[lang="ar"]').click(); await p.waitForLoadState('networkidle');
  check('[390px] Arabic page is RTL with the strip mirrored', (await p.evaluate(() => document.documentElement.dir)) === 'rtl' && new URL(p.url()).pathname === '/ar/products/green-olives-whole');
  await c.close();
}

/* ---------- filters sheet, gallery, Back navigation ---------- */
{
  const c = await ctxFor(390); const p = await c.newPage();
  await p.goto(BASE + '/en/products', { waitUntil: 'networkidle' });
  const btn = p.locator('.wrm-filter-btn'); check('[390px] a Filters button is available', await btn.isVisible());
  await btn.click(); await p.waitForTimeout(300);
  check('[390px] filter sheet opens as a dialog', await p.evaluate(() => document.querySelector('[data-wr-filter-panel]').getAttribute('role') === 'dialog'));
  await p.locator('[data-wr-filter-panel] input[type=checkbox]').nth(1).check(); await p.locator('[data-wr-filter-panel] input[type=checkbox]').nth(2).check();
  check('[390px] active-filter count updates live', (await p.locator('.wrm-filter-count').textContent()).trim() === '2');
  await p.locator('.wrm-fclear').click(); check('[390px] Clear unticks everything and the count disappears', (await p.locator('[data-wr-filter-panel] input:checked').count()) === 0 && await p.locator('.wrm-filter-count').isHidden());
  await p.locator('[data-wr-filter-panel] input[type=checkbox]').nth(1).check();
  await Promise.all([p.waitForNavigation(), p.locator('[data-wr-filter-panel] button[type=submit]').click()]);
  check('[390px] Apply filters navigates with the filter in the URL', /category/.test(decodeURIComponent(p.url())), decodeURIComponent(p.url()).split('?')[1]);
  check('[390px] the count shows the active filter after reload', (await p.locator('.wrm-filter-count').textContent()).trim() === '1');
  await p.locator('.wrm-filter-btn').click(); await p.keyboard.press('Escape'); await p.waitForTimeout(150);
  check('[390px] Escape closes the filter sheet and returns focus to its button', await p.evaluate(() => !document.querySelector('.wrm-fopen') && document.activeElement.classList.contains('wrm-filter-btn')));
  // back navigation: scroll position and filters are kept
  await p.goto(BASE + '/en/products', { waitUntil: 'networkidle' });
  const link = p.locator('main a[href*="/products/"]:not([href*="category"])').nth(3);
  await link.scrollIntoViewIfNeeded(); await p.evaluate(() => window.scrollBy(0, 300)); await p.waitForTimeout(300);
  const y1 = await p.evaluate(() => Math.round(scrollY));
  await link.click(); await p.waitForLoadState('networkidle');
  await p.goBack({ waitUntil: 'networkidle' }); await p.waitForTimeout(500);
  const y2 = await p.evaluate(() => Math.round(scrollY));
  check('[390px] Back from a product restores the list scroll position', Math.abs(y2 - y1) < 120, `before=${y1} after=${y2}`);
  // gallery
  await p.goto(BASE + '/en/products/green-olives-whole', { waitUntil: 'networkidle' });
  const arrows = await p.locator('.wrm-gal-btn').count();
  check('[390px] gallery has visible previous/next controls', arrows === 2 && await p.locator('.wrm-gal-next').isVisible());
  const before = await p.evaluate(() => document.querySelector('.media-frame-magnify img').getAttribute('src'));
  await p.locator('.wrm-gal-next').click(); await p.waitForTimeout(250);
  const after = await p.evaluate(() => document.querySelector('.media-frame-magnify img').getAttribute('src'));
  check('[390px] next arrow changes the gallery image', before !== after);
  await c.close();
}

/* ---------- forms: quote page ---------- */
{
  const c = await ctxFor(390); const p = await c.newPage();
  await p.goto(BASE + '/en/request-a-quote', { waitUntil: 'networkidle' });
  const types = await p.evaluate(() => ({ email: document.querySelector('[name=email]').type, tel: document.querySelector('[name=phone]').type, emailAc: document.querySelector('[name=email]').autocomplete, im: document.querySelector('[name=phone]').inputMode, size: parseFloat(getComputedStyle(document.querySelector('[name=email]')).fontSize) }));
  check('[390px] quote fields use phone-friendly types / autofill / 16px text', types.email === 'email' && types.tel === 'tel' && types.emailAc === 'email' && types.im === 'tel' && types.size >= 16, JSON.stringify(types));
  await p.locator('[name=company_name]').focus(); await p.waitForTimeout(400);
  check('[390px] bottom bar hides while a field has the keyboard', await p.evaluate(() => document.documentElement.classList.contains('wrm-kb') && document.querySelector('.wrm-nav').getBoundingClientRect().top >= innerHeight - 1));
  await p.locator('[name=company_name]').blur(); await p.waitForTimeout(300);
  check('[390px] bottom bar returns after the field loses focus', await p.evaluate(() => !document.documentElement.classList.contains('wrm-kb')));
  // invalid submit keeps the data; valid submit cannot be sent twice
  await p.fill('[name=company_name]', 'Test Importers SL'); await p.fill('[name=contact_person]', 'Test Buyer'); await p.fill('[name=email]', 'buyer@example.com'); await p.selectOption('[name=country_code]', { index: 1 }).catch(() => {});
  let posts = 0; p.on('request', r => { if (r.method() === 'POST' && /request-a-quote/.test(r.url())) posts++; });
  const btn = p.locator('main form button[type=submit]').first(); await btn.scrollIntoViewIfNeeded();
  await btn.evaluate(b => { b.click(); b.click(); b.click(); }); await p.waitForLoadState('networkidle'); await p.waitForTimeout(700);
  check('[390px] double tap on Submit sends the request only once', posts === 1, 'posts=' + posts);
  await c.close();
}

/* ---------- consent banner ---------- */
{
  const c = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true }); const p = await c.newPage();
  await p.goto(BASE + '/en', { waitUntil: 'networkidle' }); await p.waitForTimeout(600);
  const o = await p.evaluate(() => { const k = document.querySelector('#wr-consent'); const n = document.querySelector('.wrm-nav'); if (!k || k.hidden) return null; const kr = k.getBoundingClientRect(), nr = n.getBoundingClientRect(); return { gap: Math.round(nr.top - kr.bottom), fits: kr.top >= 0 }; });
  check('[390px] consent notice sits above the bottom bar', o === null || (o.gap >= 0 && o.fits), JSON.stringify(o));
  await c.close();
}

/* ---------- consent can still be changed from the phone ---------- */
{
  const c = await ctxFor(390); const p = await c.newPage();
  await p.goto(BASE + '/en', { waitUntil: 'networkidle' });
  check('[390px] the floating Privacy-choices pill is not drawn over the page', await p.evaluate(() => { const e = document.getElementById('wr-consent-open'); return !e || getComputedStyle(e).display === 'none'; }));
  await p.locator('.wrm-nav [data-wrm-open]').click(); await p.waitForTimeout(300);
  const entry = p.locator('[data-wrm-consent]'); check('[390px] More sheet offers Privacy choices once a choice was made', await entry.isVisible());
  await entry.click(); await p.waitForTimeout(400);
  check('[390px] tapping it reopens the consent notice', await p.evaluate(() => { const k = document.getElementById('wr-consent'); return !!k && !k.hidden; }));
  await c.close();
}

/* ---------- desktop / tablet: nothing from the phone shell, and live resizing ---------- */
for (const w of [600, 768, 1024, 1440, 1920]) {
  const c = await ctxFor(w, 900); const p = await c.newPage();
  await p.goto(BASE + '/en/products', { waitUntil: 'networkidle' });
  const T = `[${w}px] `;
  const hidden = await p.evaluate(() => [...document.querySelectorAll('.wrm')].every(e => getComputedStyle(e).display === 'none'));
  check(T + 'every phone-only element is display:none', hidden);
  check(T + 'existing header is visible', await vis(p, 'header[data-wr-site-header]'));
  check(T + 'no phone class or wrapped table left on <html>', await p.evaluate(() => !document.documentElement.classList.contains('wrm-on') && !document.querySelector('.wrm-table, .wrm-filter-btn, .wrm-gal-btn, .wrm-fbar')));
  let focusedWrm = false; for (let i = 0; i < 40; i++) { await p.keyboard.press('Tab'); if (await p.evaluate(() => !!document.activeElement.closest('.wrm'))) { focusedWrm = true; break; } }
  check(T + 'keyboard focus never enters a phone-only control', !focusedWrm);
  check(T + 'body has no extra bottom padding', await p.evaluate(() => parseFloat(getComputedStyle(document.body).paddingBottom) === 0));
  await c.close();
}
{
  const c = await ctxFor(599, 900); const p = await c.newPage(); await p.goto(BASE + '/en/products', { waitUntil: 'networkidle' });
  check('[599px] phone shell is active at the upper bound', await p.evaluate(() => document.documentElement.classList.contains('wrm-on') && getComputedStyle(document.querySelector('.wrm-nav')).display !== 'none'));
  await p.setViewportSize({ width: 600, height: 900 }); await p.waitForTimeout(300);
  check('[600px] resizing across the boundary switches the shell off', await p.evaluate(() => !document.documentElement.classList.contains('wrm-on') && getComputedStyle(document.querySelector('.wrm-nav')).display === 'none' && !document.querySelector('.wrm-filter-btn')));
  await p.setViewportSize({ width: 390, height: 844 }); await p.waitForTimeout(300);
  check('[390px] and back on again after resizing down', await p.evaluate(() => document.documentElement.classList.contains('wrm-on') && !!document.querySelector('.wrm-filter-btn') && getComputedStyle(document.querySelector('.wrm-nav')).display !== 'none'));
  await p.locator('.wrm-nav [data-wrm-open]').click(); await p.waitForTimeout(300); await p.setViewportSize({ width: 1024, height: 800 }); await p.waitForTimeout(300);
  check('[1024px] an open sheet is closed and scrolling restored when the viewport grows', await p.evaluate(() => document.querySelector('#wrm-more').hidden && !document.documentElement.classList.contains('wrm-locked') && !document.querySelector('main').hasAttribute('inert')));
  await c.close();
}
await b.close();
const failed = results.filter(r => !r.ok); console.log(`\n${results.length - failed.length}/${results.length} passed`); 
import fs from 'fs'; fs.writeFileSync(process.argv[3] || 'mtest-results.json', JSON.stringify(results, null, 1));
process.exit(failed.length ? 1 : 0);
