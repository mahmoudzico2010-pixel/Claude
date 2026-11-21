import { chromium } from './lib.mjs';
import fs from 'fs';
const base = 'http://127.0.0.1:8023', url = base + '/en/news/sial-paris-2026', out = 'ashots/ss-out'; fs.mkdirSync(out, { recursive: true });
const res = []; const check = (n, ok, d = '') => { res.push({ name: n, ok: !!ok }); console.log((ok ? 'PASS ' : 'FAIL ') + n + (ok ? '' : ' -> ' + d)); };
const b = await chromium.launch();
const ctx = await b.newContext({ viewport: { width: 1280, height: 900 } }); const p = await ctx.newPage(); p.on('pageerror', e => console.log('pageerror', e.message));
await p.goto(url, { waitUntil: 'networkidle' }); await p.evaluate(() => document.querySelector('[data-wr-ss]').scrollIntoView({ block: 'center' })); await p.mouse.move(5, 5);
const idx = () => p.evaluate(() => [...document.querySelectorAll('.wr-ss-slide')].findIndex(s => s.classList.contains('is-active')));
check('[slideshow] starts on photo 1 and is upgraded (is-js, playing)', (await idx()) === 0 && await p.evaluate(() => { const s = document.querySelector('[data-wr-ss]'); return s.classList.contains('is-js') && s.classList.contains('is-playing'); }));
await p.screenshot({ path: `${out}/01-slide1.png` });
await p.waitForTimeout(5800);
check('[slideshow] advances by itself after ~5 s (cross-fade)', (await idx()) === 1, String(await idx()));
await p.waitForTimeout(700); await p.screenshot({ path: `${out}/02-slide2.png` });
// hover pauses
await p.hover('.wr-ss-stage'); const at = await idx(); await p.waitForTimeout(6500);
check('[slideshow] pauses while the pointer is on it', (await idx()) === at);
await p.mouse.move(5, 5); await p.waitForTimeout(500);
// pause button
await p.click('[data-wr-ss-play]'); await p.mouse.move(5, 5); const at2 = await idx(); await p.waitForTimeout(6500);
check('[slideshow] the pause button stops autoplay (aria-pressed=false)', (await idx()) === at2 && await p.getAttribute('[data-wr-ss-play]', 'aria-pressed') === 'false');
// manual: next arrow, thumbnail
await p.click('[data-wr-ss-next]'); await p.waitForTimeout(300);
check('[manual] next arrow moves to the following photo', (await idx()) === (at2 + 1) % 7, String(await idx()));
await p.click('[data-wr-ss-go="5"]'); await p.waitForTimeout(300);
check('[manual] a thumbnail jumps to that photo and marks it current', (await idx()) === 5 && await p.getAttribute('[data-wr-ss-go="5"]', 'aria-current') === 'true');
await p.screenshot({ path: `${out}/03-thumbs.png` });
// press a slide -> lightbox
await p.click('.wr-ss-slide.is-active .wr-ss-open'); await p.waitForTimeout(700);
check('[lightbox] pressing the slide opens the full photo', await p.evaluate(() => { const d = document.querySelector('[data-wr-ng-dialog]'); const i = d.querySelector('[data-wr-ng-img]'); return d.open && i.complete && i.naturalWidth > 500; }));
await p.screenshot({ path: `${out}/04-lightbox.png` });
await p.keyboard.press('ArrowRight'); await p.waitForTimeout(500);
check('[lightbox] arrow key moves manually to the next photo (7 → 1 wrap)', /Photo 7 of 7/.test(await p.locator('[data-wr-ng-count]').innerText()), await p.locator('[data-wr-ng-count]').innerText());
await p.click('[data-wr-ng-prev]'); await p.waitForTimeout(500);
check('[lightbox] previous button moves back', /Photo 6 of 7/.test(await p.locator('[data-wr-ng-count]').innerText()));
await p.keyboard.press('Escape'); await p.waitForTimeout(500);
check('[lightbox] closing returns to the slideshow on the photo the visitor ended on', (await idx()) === 5);
check('[lightbox] focus returns to that slide', await p.evaluate(() => document.activeElement?.hasAttribute('data-wr-ng-open')));
// resume autoplay
await p.click('[data-wr-ss-play]'); await p.mouse.move(5, 5); const at3 = await idx(); await p.waitForTimeout(5800);
check('[slideshow] play resumes the auto-advance', (await idx()) === (at3 + 1) % 7);
// reduced motion: no autoplay
const rctx = await b.newContext({ viewport: { width: 1280, height: 900 }, reducedMotion: 'reduce' }); const rp = await rctx.newPage(); await rp.goto(url, { waitUntil: 'networkidle' });
await rp.waitForTimeout(6500);
check('[reduced motion] the slideshow does not auto-advance (manual only)', await rp.evaluate(() => [...document.querySelectorAll('.wr-ss-slide')].findIndex(s => s.classList.contains('is-active'))) === 0);
// no JS: a swipeable strip, all photos reachable
const nctx = await b.newContext({ viewport: { width: 1280, height: 900 }, javaScriptEnabled: false }); const np = await nctx.newPage(); await np.goto(url);
check('[no JavaScript] all 7 slides are in a scroll-snap strip (nothing hidden)', await np.evaluate(() => { const t = document.querySelector('[data-wr-ss-track]'); return getComputedStyle(t).overflowX !== 'hidden' && document.querySelectorAll('.wr-ss-slide').length === 7 && getComputedStyle(document.querySelectorAll('.wr-ss-slide')[3]).visibility !== 'hidden'; }));
// phone en + ar
const mctx = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
for (const [loc, path] of [['en', '/en/news/sial-paris-2026'], ['ar', '/ar/news/sial-paris-2026']]) {
  const mp = await mctx.newPage(); await mp.goto(base + path, { waitUntil: 'networkidle' }); await mp.evaluate(() => document.querySelector('[data-wr-ss]').scrollIntoView({ block: 'center' })); await mp.waitForTimeout(700);
  check(`[phone ${loc}] slideshow fits without horizontal overflow`, await mp.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
  await mp.screenshot({ path: `${out}/05-phone-${loc}.png` });
  await mp.close();
}
await b.close(); fs.writeFileSync(`${out}/results.json`, JSON.stringify(res, null, 1)); console.log(res.some(r => !r.ok) ? 'SOME FAILED' : 'ALL PASSED');
