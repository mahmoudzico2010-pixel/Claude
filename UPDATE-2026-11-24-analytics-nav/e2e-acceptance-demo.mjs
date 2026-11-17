import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const BASE = 'http://127.0.0.1:8000';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
const b = await chromium.launch();
async function ctx(country) { const c = await b.newContext({ userAgent: UA, viewport: { width: 1280, height: 900 }, extraHTTPHeaders: country ? { 'CF-IPCountry': country } : {} }); return c; }
async function fillRfq(p, who) {
  await p.fill('input[name=company_name]', who + ' Importers S.L.'); await p.fill('input[name=contact_person]', who + ' Buyer');
  await p.fill('input[name=email]', who.toLowerCase() + '@example.test');
  const ct = p.locator('select[name=company_type]'); if (await ct.count()) { await ct.selectOption({ index: 1 }).catch(() => {}); }
  const cc = p.locator('select[name=country_code]'); if (await cc.count()) { await cc.selectOption('ES').catch(() => {}); }
  await p.fill('textarea[name=message]', 'Test request ' + who).catch(() => {});
  const q = p.locator('input[name$="[quantity]"]'); for (let i = 0; i < await q.count(); i++) { await q.nth(i).fill('12'); }
}
// ---- Visit 1: consented, Spain, product -> RFQ -> submit -> reload thank-you
{
  const c = await ctx('ES'); const p = await c.newPage();
  await p.goto(BASE + '/en', { waitUntil: 'networkidle' });
  console.log('banner visible before choice:', await p.locator('#wr-consent').isVisible());
  await p.screenshot({ path: 'shots/an-consent.png' });
  await p.locator('[data-wr-consent=granted]').click(); await p.waitForTimeout(800);
  await p.goto(BASE + '/en/products/green-olives-sliced', { waitUntil: 'networkidle' });
  for (let i = 0; i < 9; i++) { await p.mouse.move(200 + i * 30, 300 + i * 10); await p.waitForTimeout(2000); }   // ~18 s active
  await p.locator('a[href*="request-a-quote?product="]').first().click(); await p.waitForLoadState('networkidle');
  await p.locator('input[name=company_name]').focus(); await p.waitForTimeout(500);
  await fillRfq(p, 'Spain1');
  await p.locator('form button[type=submit]').last().click(); await p.waitForURL(/received/, { timeout: 15000 }).catch(() => console.log('submit did not reach received:', p.url()));
  console.log('after submit:', p.url().split('/').slice(-3).join('/'));
  await p.waitForTimeout(1500);
  await p.reload({ waitUntil: 'networkidle' }); await p.waitForTimeout(1500);   // reload the success page
  await c.close();
}
// ---- Visit 2: direct RFQ (no product) from Spain, consented
{
  const c = await ctx('ES'); const p = await c.newPage();
  await c.addCookies([{ name: 'wr_consent', value: 'granted', url: BASE }]);
  await p.goto(BASE + '/en/request-a-quote', { waitUntil: 'networkidle' }); await p.waitForTimeout(800);
  await p.locator('input[name=company_name]').focus(); await fillRfq(p, 'Direct2');
  await p.locator('form button[type=submit]').last().click(); await p.waitForURL(/received/, { timeout: 15000 }).catch(() => console.log('direct submit failed', p.url()));
  await p.waitForTimeout(1200); await c.close();
}
// ---- Visit 3: multi-product RFQ (two lines) from Egypt, consented
{
  const c = await ctx('EG'); const p = await c.newPage();
  await c.addCookies([{ name: 'wr_consent', value: 'granted', url: BASE }]);
  await p.goto(BASE + '/en/products/kalamata-style-olives', { waitUntil: 'networkidle' }); await p.waitForTimeout(1200);
  await p.locator('a[href*="request-a-quote?product="]').first().click(); await p.waitForLoadState('networkidle'); await p.waitForTimeout(800);
  await p.locator('input[name=company_name]').focus(); await fillRfq(p, 'Multi3');
  await p.getByRole('button', { name: /add/i }).first().click().catch(() => {}); await p.waitForTimeout(500);
  const sels = p.locator('select[name$="[product_id]"]'); console.log('product lines:', await sels.count());
  if (await sels.count() > 1) { await sels.nth(1).selectOption('8').catch(() => {}); }
  const q2 = p.locator('input[name$="[quantity]"]'); for (let i = 0; i < await q2.count(); i++) { await q2.nth(i).fill('12'); }
  await p.locator('form button[type=submit]').last().click(); await p.waitForURL(/received/, { timeout: 15000 }).catch(() => console.log('multi submit failed', p.url()));
  await p.waitForTimeout(1200); await c.close();
}
// ---- Visit 4: refused consent: nothing may be recorded
{
  const c = await ctx('FR'); const p = await c.newPage();
  await p.goto(BASE + '/en', { waitUntil: 'networkidle' }); await p.locator('[data-wr-consent=denied]').click(); await p.waitForTimeout(500);
  await p.goto(BASE + '/en/products/cherry-peppers', { waitUntil: 'networkidle' }); await p.waitForTimeout(1500); await c.close();
}
await b.close();
