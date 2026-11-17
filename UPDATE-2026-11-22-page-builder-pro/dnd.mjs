import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const S='/tmp/claude-0/-home-user-Claude/0a3fdaf9-e36f-576f-b421-e0836fea1fe2/scratchpad/shots/';
const b = await chromium.launch(); const p = await (await b.newContext({ viewport: { width: 1600, height: 1000 } })).newPage();
p.on('pageerror', e => console.log('PAGEERR', e.message));
await p.goto('http://127.0.0.1:8000/admin/login'); await p.fill('input[type=email]','admin@example.com'); await p.fill('input[type=password]','LocalTest#2026'); await p.click('button[type=submit]'); await p.waitForTimeout(2500);
await p.goto('http://127.0.0.1:8000/admin/page-builder?page=quality'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(2500);
await p.screenshot({ path: S+'pro-1-elements.png' });
// templates tab: insert one
await p.getByRole('tab',{name:/Templates/}).click(); await p.waitForTimeout(400);
await p.screenshot({ path: S+'pro-2-templates.png' });
await p.locator('.wr-tpl').first().click(); await p.waitForTimeout(3000);
// drag an element tile onto the preview
await p.getByRole('tab',{name:/Elements/}).click(); await p.waitForTimeout(400);
const tile = p.locator('.wr-lib button[data-type="icon_box"]').first();
await p.locator('#wr-frame').scrollIntoViewIfNeeded(); await p.evaluate(()=>window.scrollTo(0,300)); await p.waitForTimeout(300);
const tb = await tile.boundingBox();
await p.mouse.move(tb.x+tb.width/2, tb.y+tb.height/2); await p.mouse.down();
await p.mouse.move(tb.x+30, tb.y+30, {steps:4});
const fr = await p.locator('#wr-frame').boundingBox(); console.log('frame',JSON.stringify(fr),'tile',JSON.stringify(tb));
await p.mouse.move(fr.x+fr.width/2, Math.min(fr.y+400, 800), {steps:15}); await p.waitForTimeout(800);
await p.screenshot({ path: S+'pro-3-dragging.png' });
const boxVis = await p.locator('#wr-dropbox').isVisible(), lineVis = await p.locator('#wr-dropline').isVisible();
console.log('dropbox', boxVis, 'dropline', lineVis);
await p.mouse.up(); await p.waitForTimeout(3500);
await p.screenshot({ path: S+'pro-4-dropped.png' });
// structure tab
await p.getByRole('tab',{name:/Structure/}).click(); await p.waitForTimeout(500);
await p.screenshot({ path: S+'pro-5-structure.png' });
console.log('icon_box rows', await p.locator('#wr-tree li[data-type="icon_box"]').count());
// context menu on preview
const f = p.frameLocator('#wr-frame');
await f.locator('.wr-heading, .wr-rich-text').first().click({button:'right'}); await p.waitForTimeout(600);
await p.screenshot({ path: S+'pro-6-context.png' });
await b.close();
