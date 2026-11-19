// usage: node shoot.mjs <port> <outDir> <widths comma> [locales comma]
import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
import fs from 'fs';
const [,, port, out, widthsArg, locArg] = process.argv;
const widths = widthsArg.split(',').map(Number); const locales = (locArg || 'en,ar').split(',');
const pages = [['home', ''], ['products', '/products'], ['category', '/products/category/green-olives'], ['product', '/products/green-olives-whole'], ['quality', '/quality'], ['export', '/export'], ['private-label', '/private-label'], ['about', '/about'], ['team', '/team'], ['news', '/news'], ['contact', '/contact'], ['quote', '/request-a-quote']];
const FREEZE = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important;scroll-behavior:auto!important}';
const b = await chromium.launch();
for (const loc of locales) for (const w of widths) {
  const mobile = w < 600;
  const ctx = await b.newContext({ viewport: { width: w, height: mobile ? 800 : 900 }, reducedMotion: 'reduce', deviceScaleFactor: 1, ...(mobile ? { isMobile: true, hasTouch: true } : {}) });
  await ctx.addCookies([{ name: 'wr_consent', value: 'denied', url: `http://127.0.0.1:${port}` }]);
  const p = await ctx.newPage();
  for (const [name, path] of pages) {
    const dir = `${out}/${loc}`; fs.mkdirSync(dir, { recursive: true });
    try {
      await p.goto(`http://127.0.0.1:${port}/${loc}${path}`, { waitUntil: 'load', timeout: 45000 });
      await p.addStyleTag({ content: FREEZE });
      // load every lazy image deterministically, then return to the top
      await p.evaluate(async () => { const h = document.documentElement.scrollHeight; for (let y = 0; y < h; y += 500) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 60)); } window.scrollTo(0, 0); });
      await p.waitForLoadState('networkidle').catch(() => {}); await p.waitForTimeout(400);
      await p.screenshot({ path: `${dir}/${name}-${w}.png`, fullPage: true });
    } catch (e) { console.log('FAIL', loc, name, w, e.message.slice(0, 80)); }
  }
  await ctx.close(); console.log('done', loc, w);
}
await b.close();
