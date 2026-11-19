import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
import fs from 'fs';
const BASE = process.argv[2]; const OUT = process.argv[3]; fs.mkdirSync(OUT, { recursive: true });
const b = await chromium.launch(); const log = [];
async function page(w, h, loc, consent = true) {
  const ctx = await b.newContext({ viewport: { width: w, height: h }, isMobile: true, hasTouch: true, deviceScaleFactor: 2, reducedMotion: 'reduce' });
  if (consent) await ctx.addCookies([{ name: 'wr_consent', value: 'denied', url: BASE }]);
  const p = await ctx.newPage(); p.on('pageerror', e => log.push('pageerror ' + e.message)); return { ctx, p };
}
const shot = async (p, name, opt = {}) => { await p.waitForTimeout(450); await p.screenshot({ path: `${OUT}/${name}.png`, ...opt }); };
for (const loc of ['en', 'ar']) {
  const L = '/' + loc;
  let { ctx, p } = await page(390, 844, loc);
  await p.goto(BASE + L, { waitUntil: 'networkidle' }); await shot(p, `${loc}-01-home-top`);
  await p.evaluate(() => document.querySelector('.wrm-langs-list').scrollBy({ left: 9999 })); await shot(p, `${loc}-02-language-strip-scrolled-to-end`, { clip: { x: 0, y: 0, width: 390, height: 70 } });
  await p.evaluate(() => window.scrollTo(0, 900)); await shot(p, `${loc}-03-home-scrolled-whatsapp-and-bar`);
  await p.locator('.wrm-nav [data-wrm-open]').click(); await shot(p, `${loc}-04-more-sheet`);
  await ctx.close();
  ({ ctx, p } = await page(390, 844, loc)); await p.goto(BASE + L + '/products', { waitUntil: 'networkidle' }); await shot(p, `${loc}-05-products-top`);
  await p.locator('.wrm-filter-btn').click(); await shot(p, `${loc}-06-filter-sheet`);
  await p.keyboard.press('Escape'); await p.locator('main article.card').first().scrollIntoViewIfNeeded(); await shot(p, `${loc}-07-two-column-cards`);
  await ctx.close();
  ({ ctx, p } = await page(390, 844, loc)); await p.goto(BASE + L + '/products/green-olives-whole', { waitUntil: 'networkidle' }); await shot(p, `${loc}-08-product-gallery`);
  await ctx.close();
  ({ ctx, p } = await page(390, 844, loc)); await p.goto(BASE + L + '/request-a-quote', { waitUntil: 'networkidle' }); await p.locator('[name=email]').scrollIntoViewIfNeeded(); await p.locator('[name=email]').focus(); await shot(p, `${loc}-09-quote-form-keyboard-state-bar-hidden`); await ctx.close();
  ({ ctx, p } = await page(390, 844, loc, false)); await p.goto(BASE + L, { waitUntil: 'networkidle' }); await p.waitForTimeout(700); await shot(p, `${loc}-10-consent-notice-above-bar`); await ctx.close();
}
for (const w of [360, 430]) { const { ctx, p } = await page(w, 800, 'en'); await p.goto(BASE + '/en', { waitUntil: 'networkidle' }); await shot(p, `en-11-home-${w}`); await ctx.close(); }
{ const { ctx, p } = await page(768, 900, 'en'); await p.goto(BASE + '/en', { waitUntil: 'networkidle' }); await shot(p, 'tablet-768-unchanged-header'); await ctx.close(); }
await b.close(); console.log('errors:', log.length, log.join(' | '));
