import { chromium, login } from './lib.mjs';
import { execFileSync } from 'child_process'; import fs from 'fs';
const base = 'http://127.0.0.1:8023', db = 'dev-pz.sqlite', out = 'ashots/ng-out'; fs.mkdirSync(out, { recursive: true });
const q = (sql) => JSON.parse(execFileSync('php', ['ashots/q.php', db, sql]).toString());
const res = []; const check = (n, ok, d = '') => { res.push({ name: n, ok: !!ok, detail: String(d).slice(0, 200) }); console.log((ok ? 'PASS ' : 'FAIL ') + n + (ok ? '' : '  -> ' + d)); };
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1440, height: 1000 } }); const p = await ctx.newPage();
p.on('pageerror', e => console.log('pageerror', e.message.slice(0, 100)));
await login(p, base);
await p.goto(base + '/admin/news/news-posts', { waitUntil: 'networkidle' });
const href = await p.evaluate(() => [...document.querySelectorAll('a')].map(a => a.href).find(h => /sial-paris-2026.*edit|news-posts\/.*\/edit/.test(h))); console.log('edit url:', href);
await p.goto(href, { waitUntil: 'networkidle' }); await p.waitForTimeout(800);
// the exhibition period: the date fields are visible when editing an existing exhibition; set a range 17 -> 21 Oct 2026
check('[admin] period fields (From / To) are visible and filled on an existing exhibition', await p.evaluate(() => { const v = id => document.getElementById(id)?.value; return v('form.event_start_date') === 'Oct 17, 2026' && v('form.event_end_date') === 'Oct 21, 2026'; }));
await p.screenshot({ path: `${out}/00-admin-period.png` });
await p.getByText('Photo & video gallery').first().scrollIntoViewIfNeeded(); await p.screenshot({ path: `${out}/01-admin-gallery-empty.png`, fullPage: false });
const addPhoto = async (file) => {
  await p.getByRole('button', { name: 'Add photo' }).click(); await p.waitForTimeout(900);
  const up = p.getByRole('button', { name: 'Upload new' }).last(); await up.click(); await p.waitForTimeout(900);
  await p.setInputFiles('.fi-modal-window input[type=file]', file);
  await p.waitForFunction(() => { const m = [...document.querySelectorAll('.fi-modal-window')].find(x => x.querySelector('input[type=file]')); return m && m.querySelector('.filepond--item') && !/Uploading/.test(m.innerText); }, null, { timeout: 60000 }); await p.waitForTimeout(500);
  await p.locator('.fi-modal-window').filter({ has: p.locator('input[type=file]') }).getByRole('button', { name: 'Upload new', exact: true }).click(); await p.waitForTimeout(2500);
};
for (const f of ['ashots/news-1.jpg', 'ashots/news-2.jpg', 'ashots/news-3.jpg', 'ashots/news-4.jpg']) await addPhoto(f);
await p.getByRole('button', { name: 'Add video' }).click(); await p.waitForTimeout(900);
await p.getByRole('button', { name: 'Upload new' }).last().click(); await p.waitForTimeout(900);
await p.setInputFiles('.fi-modal-window input[type=file]', 'intro-test.mp4');
await p.waitForFunction(() => { const m = [...document.querySelectorAll('.fi-modal-window')].find(x => x.querySelector('input[type=file]')); return m && m.querySelector('.filepond--item') && !/Uploading/.test(m.innerText); }, null, { timeout: 90000 }); await p.waitForTimeout(500);
await p.locator('.fi-modal-window').filter({ has: p.locator('input[type=file]') }).getByRole('button', { name: 'Upload new', exact: true }).click(); await p.waitForTimeout(4000);
await p.getByText('Photo & video gallery').first().scrollIntoViewIfNeeded(); await p.waitForTimeout(500); await p.screenshot({ path: `${out}/02-admin-gallery-filled.png` });
// set the exhibition period 17 -> 21 Oct 2026 through the two date fields
await p.getByLabel('From').first().scrollIntoViewIfNeeded(); await p.screenshot({ path: `${out}/00b-admin-period-set.png` });
await p.getByRole('button', { name: 'Save changes' }).click(); await p.waitForTimeout(3500);
const rows = q("select kind, count(*) c from news_post_media group by kind");
check('[admin] 4 photos and 1 video saved to the gallery of the exhibition', rows.find(r => r.kind === 'photo')?.c === 4 && rows.find(r => r.kind === 'video')?.c === 1, JSON.stringify(rows));
const order = q("select m.original_filename f from news_post_media n join media_assets m on m.id=n.media_id where n.kind='photo' order by n.display_order, n.id").map(r => r.f);
check('[admin] the four photos are saved in the order added', order.join(',') === 'news-1.jpg,news-2.jpg,news-3.jpg,news-4.jpg', order.join(','));
const per = q("select event_start_date s, event_end_date e from news_posts where slug='sial-paris-2026'")[0];
check('[admin] the saved exhibition period (start and end) survives a save of the gallery', String(per.s).startsWith('2026-10-17') && String(per.e).startsWith('2026-10-21'), JSON.stringify(per));
// public page
const pub = await ctx.newPage(); await pub.goto(base + '/en/news/sial-paris-2026', { waitUntil: 'networkidle' }); await pub.waitForTimeout(800);
check('[public] 4 photos in the grid and 1 video player', await pub.locator('[data-wr-ng-open]').count() === 4 && await pub.locator('video').count() === 1);
await pub.locator('[data-wr-ng]').scrollIntoViewIfNeeded(); await pub.waitForTimeout(500); await pub.screenshot({ path: `${out}/03-public-gallery-desktop.png` });
await pub.locator('[data-wr-ng-open]').first().click(); await pub.waitForTimeout(900);
check('[public] clicking a photo opens the lightbox (modal dialog) with the full image', await pub.evaluate(() => { const d = document.querySelector('[data-wr-ng-dialog]'); const i = d.querySelector('[data-wr-ng-img]'); return d.open && i.complete && i.naturalWidth > 600; }));
await pub.screenshot({ path: `${out}/04-lightbox-desktop.png` });
await pub.keyboard.press('ArrowRight'); await pub.waitForTimeout(500);
check('[public] arrow key moves to the next photo', /Photo 2 of 4/.test(await pub.locator('[data-wr-ng-count]').innerText()));
await pub.keyboard.press('Escape'); await pub.waitForTimeout(400);
check('[public] Escape closes the lightbox and focus returns to the thumbnail', await pub.evaluate(() => !document.querySelector('[data-wr-ng-dialog]').open && document.activeElement?.hasAttribute('data-wr-ng-open')));
check('[public] the period is shown as a range', /17.21 Oct 2026/.test(await pub.locator('body').innerText()));
check('[public] one H1 only', await pub.locator('h1').count() === 1);
// phone EN + AR
const mctx = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 });
for (const [loc, path] of [['en', '/en/news/sial-paris-2026'], ['ar', '/ar/news/sial-paris-2026']]) {
  const mp = await mctx.newPage(); await mp.goto(base + path, { waitUntil: 'networkidle' }); await mp.waitForTimeout(800);
  await mp.locator('[data-wr-ng]').scrollIntoViewIfNeeded(); await mp.waitForTimeout(500);
  check(`[phone ${loc}] gallery fits the screen without horizontal overflow`, await mp.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
  await mp.screenshot({ path: `${out}/05-phone-${loc}-gallery.png` });
  await mp.locator('[data-wr-ng-open]').first().click(); await mp.waitForTimeout(900); await mp.screenshot({ path: `${out}/06-phone-${loc}-lightbox.png` });
  await mp.close();
}
await b.close(); fs.writeFileSync(`${out}/results.json`, JSON.stringify(res, null, 1)); console.log(res.some(r => !r.ok) ? 'SOME FAILED' : 'ALL PASSED');
