// Phone screenshots (EN + AR). usage: node shots.mjs <port> <outDir> [baseline]   (baseline = the pre-update panel, EN only on 8030 / AR on 8031)
import { chromium } from './lib.mjs';
import fs from 'fs';
const [,, port, out, mode] = process.argv; const base = `http://127.0.0.1:${port}`; fs.mkdirSync(out, { recursive: true });
const FREEZE = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important}';
const b = await chromium.launch();
const settle = (p, ms = 800) => p.waitForTimeout(ms);
async function session(w, loc) {
  const ctx = await b.newContext({ viewport: { width: w, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }); const p = await ctx.newPage();
  await p.goto(base + '/admin/login', { waitUntil: 'networkidle' });
  if (!mode) await p.screenshot({ path: `${out}/${loc}-login-${w}.png` });
  await p.fill('input[type=email]', 'admin@example.com'); await p.fill('input[type=password]', 'LocalTest#2026'); await p.click('form[wire\\:submit] button[type=submit]'); await p.waitForURL(/\/admin(?!\/login)/); await p.waitForLoadState('networkidle');
  if (!mode && loc === 'ar') { await p.click('.wam-lang[value=ar]'); await p.waitForLoadState('networkidle'); }
  return { ctx, p };
}
const go = async (p, path) => { await p.goto(base + path, { waitUntil: 'networkidle' }); await p.addStyleTag({ content: FREEZE }); await settle(p); };
const tap = async (loc) => { await loc.evaluate(e => e.scrollIntoView({ block: 'center' })); await loc.click(); };
for (const loc of mode ? ['en'] : ['en', 'ar']) {
  for (const w of mode ? [390] : [360, 390, 430]) {
    const { ctx, p } = await session(w, loc); const n = (x) => `${out}/${loc}-${x}-${w}.png`;
    await go(p, '/admin/products'); await p.screenshot({ path: n('products') });
    await go(p, '/admin/quote-requests'); await p.screenshot({ path: n('requests') });
    await go(p, '/admin/analytics'); await p.screenshot({ path: n('analytics') });
    await p.evaluate(() => window.scrollTo(0, 1000)); await settle(p, 400); await p.screenshot({ path: n('analytics-data') });
    await go(p, '/admin/media/media-assets'); await p.screenshot({ path: n('media') });
    if (!mode) {
      await go(p, '/admin/products'); await p.click('[data-wam-more]'); await settle(p, 900); await p.screenshot({ path: n('more-drawer') }); await p.keyboard.press('Escape'); await settle(p, 500);
    }
    if (w === 390) {
      await go(p, '/admin/products');
      await p.click('.fi-ta-filters-dropdown .fi-dropdown-trigger'); await settle(p, 800); await p.screenshot({ path: n('filters-sheet') }); await p.keyboard.press('Escape'); await settle(p, 400);
      await go(p, '/admin/products/green-olives-whole/edit'); await p.screenshot({ path: n('product-edit') });
      await p.evaluate(() => window.scrollTo(0, 1200)); await settle(p, 500); await p.screenshot({ path: n('product-edit-scrolled-save-bar') });
      await tap(p.locator('.fi-sc-tabs > .fi-tabs .fi-tabs-item').nth(3)); await settle(p, 600);
      await p.evaluate(() => document.querySelector('.fi-fo-repeater')?.scrollIntoView({ block: 'start' })); await settle(p, 500); await p.screenshot({ path: n('gallery-reorder-buttons') });
      if (!mode) {
        await go(p, '/admin/products');
        await p.locator('.fi-ta-page-checkbox').first().check(); await settle(p, 500);
        await p.locator('.fi-ta-selection-indicator button').first().click(); await settle(p, 900);
        await p.locator('.fi-ta-record-checkbox').first().uncheck(); await settle(p, 700); await p.screenshot({ path: n('bulk-selection') });
        await p.locator('.fi-ta-header-toolbar .fi-dropdown-trigger').first().click(); await settle(p, 500);
        await p.locator('.fi-dropdown-panel button, .fi-dropdown-panel a').filter({ hasText: /Bulk edit|تعديل/ }).first().click(); await p.waitForURL(/bulk-edit/); await p.waitForLoadState('networkidle'); await settle(p, 900);
        await p.screenshot({ path: n('bulk-edit-scope') });
      }
    }
    await ctx.close();
  }
}
await b.close(); console.log('done', out);
