import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const S='/tmp/claude-0/-home-user-Claude/0a3fdaf9-e36f-576f-b421-e0836fea1fe2/scratchpad/shots/a-';
const BASE='http://127.0.0.1:8000';
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1600, height: 1000 } }); const p = await ctx.newPage();
const anon = await (await b.newContext({ viewport:{width:1300,height:900} })).newPage();
p.on('pageerror', e => { if(!/Object|showModal/.test(e.message)) console.log('PAGEERR', e.message.slice(0,200)); });
const note = async () => (await p.locator('.fi-no-notification').allTextContents()).join(' | ').replace(/\s+/g,' ').slice(0,160);
await p.goto(BASE+'/admin/login'); await p.fill('input[type=email]','admin@example.com'); await p.fill('input[type=password]','LocalTest#2026'); await p.click('button[type=submit]'); await p.waitForTimeout(2500);
const slug='amira-demo-hassan';
await p.goto(BASE+'/admin/team/team-members'); await p.waitForLoadState('networkidle'); await p.waitForTimeout(2200);
await p.screenshot({ path: S+'admin-list.png' });
let r = await anon.goto(`${BASE}/en/team/${slug}`); console.log('1. draft -> public status', r.status());
// publish
await p.locator('tr',{hasText:'Amira Demo Hassan'}).getByText('Publish',{exact:true}).click(); await p.waitForTimeout(900);
await p.getByRole('button',{name:/Confirm|Submit/}).click(); await p.waitForTimeout(2500); console.log('2. publish notification:', await note());
r = await anon.goto(`${BASE}/en/team/${slug}`); let html = await anon.content();
console.log('3. published -> status', r.status(), '| name shown:', html.includes('Amira Demo Hassan'), '| manager link shown:', html.includes('Test Export Lead'), '| email in HTML:', html.includes('amira.demo@example.test'), '| phone in HTML:', html.includes('555 0101'));
await anon.screenshot({ path: S+'profile-private-contacts.png', fullPage: true });
// edit: switch email on
await p.goto(BASE+'/admin/team/team-members'); await p.waitForTimeout(2200);
await p.locator('tr',{hasText:'Amira Demo Hassan'}).getByText('Edit',{exact:true}).click(); await p.waitForLoadState('networkidle'); await p.waitForTimeout(1200);
await p.getByRole('tab',{name:/Contact/}).click(); await p.waitForTimeout(400);
const tog = () => p.locator('.fi-fo-field', { hasText: 'Show email publicly' }).locator('button[role=switch]');
await tog().click(); await p.getByRole('button',{name:'Save changes'}).click(); await p.waitForTimeout(2500);
await anon.goto(`${BASE}/en/team/${slug}`); html = await anon.content();
console.log('4. email ON -> mailto present:', html.includes('mailto:amira.demo@example.test'));
await anon.screenshot({ path: S+'profile-email-visible.png', fullPage: true });
// edit: switch email off
await p.getByRole('tab',{name:/Contact/}).click(); await p.waitForTimeout(300);
await tog().click(); await p.getByRole('button',{name:'Save changes'}).click(); await p.waitForTimeout(2500);
const prof = await (await anon.goto(`${BASE}/en/team/${slug}`)).text(); const team = await (await anon.goto(`${BASE}/en/team`)).text(); const sm = await (await anon.goto(`${BASE}/sitemap.xml`)).text(); const ar = await (await anon.goto(`${BASE}/ar/team/${slug}`)).text();
console.log('5. email OFF -> absent from: profile', !prof.includes('amira.demo'), '| team page', !team.includes('amira.demo'), '| sitemap', !sm.includes('amira.demo'), '| arabic profile', !ar.includes('amira.demo'), '| JSON-LD email key', !/"email"/.test(prof));
await anon.goto(`${BASE}/en/team/${slug}`); await anon.screenshot({ path: S+'profile-email-hidden.png', fullPage: true });
await b.close();
