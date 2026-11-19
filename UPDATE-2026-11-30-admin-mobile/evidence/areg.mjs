// usage: node areg.mjs <port> <outDir> <en|ar> <widths comma> [ar-baseline]
// AFTER server: Arabic is chosen through the phone language strip (cookie). BASELINE server: started with APP_LOCALE=ar (there was no switcher before).
import { chromium } from './lib.mjs';
import fs from 'fs';
const [,, port, out, loc, widthsArg, arBaseline] = process.argv; const base = `http://127.0.0.1:${port}`; fs.mkdirSync(out, { recursive: true });
const PAGES = [['products', '/admin/products'], ['product-edit', '/admin/products/green-olives-whole/edit'], ['quotes', '/admin/quote-requests'], ['media', '/admin/media/media-assets'], ['analytics', '/admin/analytics'], ['settings', '/admin/manage-settings'], ['users', '/admin/users'], ['photo-review', '/admin/photo-review'], ['packaging', '/admin/packaging-catalog'], ['homepage', '/admin/homepage-manager'], ['pages', '/admin/content/pages']];
const FREEZE = '*,*::before,*::after{animation:none!important;transition:none!important;caret-color:transparent!important;scroll-behavior:auto!important}';
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 390, height: 900 } }); const p = await ctx.newPage();
await p.goto(base + '/admin/login'); await p.fill('input[type=email]', 'admin@example.com'); await p.fill('input[type=password]', 'LocalTest#2026'); await p.click('form[wire\\:submit] button[type=submit]'); await p.waitForURL(/\/admin(?!\/login)/);
if (loc === 'ar' && !arBaseline) { await p.click('.wam-lang[value=ar]'); await p.waitForLoadState('networkidle'); }
const log = [];
for (const w of widthsArg.split(',').map(Number)) {
  await p.setViewportSize({ width: w, height: 900 });
  for (const [name, path] of PAGES) {
    await p.goto(base + path, { waitUntil: 'networkidle' }); await p.addStyleTag({ content: FREEZE }); await p.waitForTimeout(700);
    // the notification toast and relative times are the only live elements; everything else comes from the same database
    const dir = await p.evaluate(() => document.documentElement.dir);
    await p.screenshot({ path: `${out}/${name}-${w}.png`, fullPage: true });
    log.push(`${name} ${w} ${dir}`);
  }
}
await b.close(); fs.writeFileSync(`${out}/captured.txt`, log.join('\n')); console.log('captured', log.length, loc, port);
