import { chromium, login } from './lib.mjs';
import fs from 'fs';
const [,, port, widthsArg = '390', locArg = 'en', outJson] = process.argv; const base = `http://127.0.0.1:${port}`;
const b = await chromium.launch(); const rows = [];
for (const loc of locArg.split(',')) for (const w of widthsArg.split(',').map(Number)) {
  const ctx = await b.newContext({ viewport: { width: w, height: 844 }, isMobile: w < 600, hasTouch: w < 600 });
  const p = await ctx.newPage(); const errs = []; p.on('pageerror', e => errs.push(e.message.slice(0, 120)));
  await login(p, base);
  if (loc !== 'en') { await p.click(`.wam-lang[value=${loc}]`); await p.waitForLoadState('networkidle'); }
  const hrefs = [...new Set(await p.$$eval('.fi-sidebar a', a => a.map(x => x.href)))].filter(h => !h.endsWith('/admin') || true);
  for (const h of hrefs) {
    errs.length = 0; let status = 0;
    const r = await p.goto(h, { waitUntil: 'networkidle', timeout: 45000 }).catch(e => null); status = r ? r.status() : 0;
    await p.waitForTimeout(250);
    const m = await p.evaluate(() => ({ sw: document.documentElement.scrollWidth, iw: innerWidth, nav: !!document.querySelector('.wam-nav'), langs: document.querySelectorAll('.wam-lang').length, dir: document.documentElement.dir, title: document.querySelector('.wam-title strong')?.textContent?.slice(0, 40) || null,
      wide: [...document.querySelectorAll('body *')].filter(e => { const r = e.getBoundingClientRect(); return r.right > innerWidth + 1 && r.width > 0 && getComputedStyle(e).position !== 'fixed' && !e.closest('.fi-ta-content-ctn, .fi-tabs, .wam-langs-list, [style*="overflow"], table, .fi-sidebar, .fi-modal, .filepond--root, pre, .fi-fo-rich-editor, .fi-fo-rich-editor-main, .tiptap'); }).slice(0, 3).map(e => e.tagName + '.' + String(e.className).slice(0, 50)) }));
    rows.push({ loc, w, url: h.replace(base, ''), status, ...m, overflow: m.sw > m.iw, errs: [...errs] });
  }
  await ctx.close();
}
await b.close(); fs.writeFileSync(outJson || 'ashots/sweep.json', JSON.stringify(rows, null, 1));
const bad = rows.filter(r => r.overflow || r.status >= 400 || !r.nav || r.errs.length);
console.log('pages', rows.length, 'problems', bad.length); bad.slice(0, 40).forEach(r => console.log(r.loc, r.w, r.url, r.status, 'sw', r.sw, r.iw, r.errs[0] || '', r.wide.join('|')));
