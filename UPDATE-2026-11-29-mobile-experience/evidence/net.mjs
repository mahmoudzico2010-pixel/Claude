import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
const { chromium } = require('playwright');
const [,, port, path] = process.argv;
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, reducedMotion: 'reduce' });
await ctx.addCookies([{ name: 'wr_consent', value: 'denied', url: `http://127.0.0.1:${port}` }]);
const p = await ctx.newPage(); const rows = [];
p.on('response', async r => { try { const buf = await r.body(); rows.push([r.url().replace(/^https?:\/\/[^/]+/, ''), buf.length]); } catch {} });
await p.goto(`http://127.0.0.1:${port}${path}`, { waitUntil: 'load' }); await p.waitForTimeout(2500);
const total = rows.reduce((a, r) => a + r[1], 0);
console.log(port, path, 'requests', rows.length, 'KiB', Math.round(total / 1024));
const byType = {}; rows.forEach(([u, n]) => { const t = /\.(png|jpe?g|webp|avif|svg)(\?|$)/.test(u) ? (u.includes('/flags/') ? 'flags' : 'images') : /\.css/.test(u) ? 'css' : /\.js/.test(u) ? 'js' : 'html/other'; byType[t] = (byType[t] || 0) + n; });
console.log(JSON.stringify(Object.fromEntries(Object.entries(byType).map(([k, v]) => [k, Math.round(v / 1024)]))));
fs_out(rows); function fs_out(r) { console.log(r.sort((a, b) => b[1] - a[1]).slice(0, 6).map(x => x[0].slice(0, 70) + ' ' + Math.round(x[1] / 1024)).join('\n')); }
await b.close();
