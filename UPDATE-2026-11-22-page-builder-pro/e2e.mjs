import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1500, height: 1000 } }); const p = await ctx.newPage();
const note = async () => (await p.locator('.fi-no-notification').allTextContents()).join(' | ').replace(/\s+/g,' ').slice(0,160);
await p.goto('http://127.0.0.1:8000/admin/login'); await p.fill('input[type=email]','admin@example.com'); await p.fill('input[type=password]','LocalTest#2026'); await p.click('button[type=submit]'); await p.waitForTimeout(2500);
// 1) page editor: select heading, put English into all other boxes, translate
await p.goto('http://127.0.0.1:8000/admin/page-builder?page=quality'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(2000);
await p.locator('.wr-lib button[data-type="heading"]').click(); await p.waitForTimeout(3000);
const boxes = p.locator('textarea[wire\\:model\\.blur^="translations."], input[wire\\:model\\.blur^="translations."]');
const cnt = await boxes.count(); console.log('builder boxes', cnt);
for (let i=0;i<cnt;i++){ await boxes.nth(i).fill('From Egypt to the World'); await boxes.nth(i).blur(); }
await p.waitForTimeout(1500);
await p.getByTestId('ai-translate-node').click(); await p.waitForTimeout(4000);
console.log('builder note:', await note());
const vals = []; for (let i=0;i<cnt;i++) vals.push((await boxes.nth(i).inputValue()).slice(0,28));
console.log(vals.join(' || '));
await p.screenshot({ path: 'shots/e2e-builder.png' });
// 2) content block modal
await p.goto('http://127.0.0.1:8000/admin/content/content-blocks'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(1500);
await p.getByText('Edit',{exact:true}).first().click(); await p.waitForTimeout(2000);
await p.getByText('Translate to all 10 languages').click(); await p.waitForTimeout(1000);
await p.getByRole('button',{name:'Submit'}).click(); await p.waitForTimeout(6000);
console.log('block note:', await note());
await p.getByRole('heading',{name:/Other languages \(/}).click(); await p.waitForTimeout(600);
await p.screenshot({ path: 'shots/e2e-block.png' });
console.log('es title:', await p.locator('input[id*="extra.es.title"], input[id$="extra.es.title"]').first().inputValue().catch(()=> 'n/a'));
// 3) homepage manager
await p.goto('http://127.0.0.1:8000/admin/homepage-manager'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(2500);
console.log('homepage buttons:', await p.getByText('Translate to all 10 languages').count());
// 4) settings
await p.goto('http://127.0.0.1:8000/admin/manage-settings'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(2000);
console.log('settings buttons:', await p.getByText('Translate to all 10 languages').count());
await b.close();
