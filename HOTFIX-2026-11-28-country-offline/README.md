# Country in analytics without Cloudflare — use the site's own offline IP→country files

Why it said "Unknown": the host sends no country header and no country database was installed. This update lets analytics use the **offline IP→country files the site already supports** (`php artisan geo:import`, the same ones automatic language selection uses). No service is called, the visitor's IP address is read only for the lookup and never stored.

## Install (no migration)
1. Back up; unzip `wadi-country-offline.zip` over the site (4 files). `php artisan optimize:clear`.
2. Download the free **DB-IP "IP to Country Lite"** CSV from https://db-ip.com/db/download/ip-to-country-lite (file `dbip-country-lite-YYYY-MM.csv.gz`; free, licence CC BY 4.0 — keep a small "IP geolocation by DB-IP" credit link, e.g. in the footer, as the licence requires).
3. Upload it to the server, e.g. `storage/app/geo-source/` (create the folder), then:
   `php artisan geo:import storage/app/geo-source/dbip-country-lite-2026-11.csv.gz`
   It prints how many IPv4/IPv6 ranges were imported.
4. Check: `php artisan geo:lookup <your own IP>` → should print `Country: EG`. In the admin: *System Settings → Visitor analytics settings → Country source check* → "offline files: Yes" and "Country detected for this request: Egypt".
5. **New** visits now get a country. Existing sessions stay Unknown (no invented history). Refresh the file every few months (repeat step 2–3).

## Notes
* Country = approximate location of the connection (mobile carriers/VPNs can show another country).
* If the site is ever put behind a CDN/proxy, country then comes from the CDN header (TRUSTED_PROXIES) first.
* Tested: 33 analytics tests pass incl. new ones (country from offline files for IPv4/IPv6, unlisted address stays Unknown, a header sent by the visitor is still ignored). **Not tested:** the live server, the real DB-IP file, MySQL 5.7.
