import { createRequire } from 'module';
const require = createRequire('/opt/node22/lib/node_modules/');
export const { chromium } = require('playwright');
export async function login(p, base) { await p.goto(base + '/admin/login'); await p.fill('input[type=email]', 'admin@example.com'); await p.fill('input[type=password]', 'LocalTest#2026'); await p.click('form[wire\\:submit] button[type=submit]'); await p.waitForURL(/admin(?!\/login)/); }
