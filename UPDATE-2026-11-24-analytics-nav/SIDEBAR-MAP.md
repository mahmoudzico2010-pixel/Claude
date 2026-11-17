# Sidebar: before → after map

Every previously available screen keeps its **URL, route name, policy and permission**. Only its *group* and *position* changed (decided in one place: `app/Support/Admin/NavigationMap.php`). A screen the map does not know (for example one added later by a plugin) is never lost: it appears at the end of **System Settings**. A group with nothing the signed-in user may open is not drawn. Dashboard is a stand-alone entry above the groups.

BEFORE = 11 groups (Catalogue, Inquiries, Website Content, Media, Company & Markets, Languages & Translations, Settings, Users & Security, Maintenance, Plugins + Dashboard). AFTER = Dashboard + 6 groups. The briefed "Analytics & Reports" group is new; the **Tracking settings** page lives in System Settings and is linked from the report filter bar for users who may configure it.

Screenshots: `screenshots/sidebar/` (`nav-before-*` and `nav-after-*`, English and Arabic, desktop 1440 px and phone 390 px drawer). Test data only.

| Screen (sidebar label) | URL (unchanged) | BEFORE: group · position | AFTER: group · position |
|---|---|---|---|
| Quote Requests | `/admin/quote-requests` | Inquiries · 1 | Sales & Enquiries · 1 |
| Contact Messages | `/admin/contact-messages` | Inquiries · 2 | Sales & Enquiries · 2 |
| Products | `/admin/products` | Catalogue · 1 | Catalogue & Packaging · 1 |
| Product Categories | `/admin/classifications/categories` | Catalogue · 2 | Catalogue & Packaging · 2 |
| Olive Varieties | `/admin/classifications/varieties` | Catalogue · 3 | Catalogue & Packaging · 3 |
| Product Forms | `/admin/classifications/product-forms` | Catalogue · 4 | Catalogue & Packaging · 4 |
| Calibres | `/admin/classifications/calibers` | Catalogue · 5 | Catalogue & Packaging · 5 |
| Packaging Catalog | `/admin/packaging-catalog` | Catalogue · 6 | Catalogue & Packaging · 6 |
| Photo review | `/admin/photo-review` | Catalogue · 7 | Catalogue & Packaging · 7 |
| Product image frame | `/admin/product-image-frame` | Catalogue · 8 | Catalogue & Packaging · 8 |
| Frame Designs | `/admin/product-frames` | Catalogue · 9 | Catalogue & Packaging · 9 |
| Pages | `/admin/content/pages` | Website Content · 2 | Content & Design · 1 |
| Homepage Manager | `/admin/homepage-manager` | Website Content · 1 | Content & Design · 2 |
| Page headers | `/admin/page-headers` | Website Content · 3 | Content & Design · 3 |
| Navigation Menu | `/admin/content/navigation-items` | Website Content · 5 | Content & Design · 4 |
| Menu structure | `/admin/menu-structure` | Website Content · 6 | Content & Design · 5 |
| Media Library | `/admin/media/media-assets` | Media · 1 | Content & Design · 6 |
| Upload large files | `/admin/upload-large-files` | Media · 2 | Content & Design · 7 |
| Documents | `/admin/documents` | Website Content · 9 | Content & Design · 8 |
| News & Exhibitions | `/admin/news/news-posts` | Website Content · 7 | Content & Design · 9 |
| News Categories | `/admin/classifications/news-categories` | Website Content · 8 | Content & Design · 10 |
| Content Blocks | `/admin/content/content-blocks` | Website Content · 4 | Content & Design · 11 |
| Team members | `/admin/team/team-members` | Website Content · 10 | Content & Design · 12 |
| Team departments | `/admin/team/team-departments` | Website Content · 11 | Content & Design · 13 |
| Team structure | `/admin/team-structure` | Website Content · 12 | Content & Design · 14 |
| Locations | `/admin/company/company-locations` | Company & Markets · 1 | Content & Design · 15 |
| Export Markets | `/admin/classifications/export-markets` | Company & Markets · 2 | Content & Design · 16 |
| Certifications | `/admin/classifications/certifications` | Company & Markets · 3 | Content & Design · 17 |
| Overview | `/admin/analytics` | — (new in this update) | Analytics & Reports · 1 |
| Audience | `/admin/analytics/audience` | — (new in this update) | Analytics & Reports · 2 |
| Pages & Products | `/admin/analytics/content` | — (new in this update) | Analytics & Reports · 3 |
| Acquisition & Conversions | `/admin/analytics/acquisition` | — (new in this update) | Analytics & Reports · 4 |
| Saved reports | `/admin/analytics/saved` | — (new in this update) | Analytics & Reports · 5 |
| Users | `/admin/users` | Users & Security · 1 | Users & Roles · 1 |
| Audit Log | `/admin/system/audit-logs` | Users & Security · 2 | Users & Roles · 2 |
| Settings | `/admin/manage-settings` | Settings · 1 | System Settings · 1 |
| Languages | `/admin/languages` | Languages & Translations · 1 | System Settings · 2 |
| Lookup texts (other languages) | `/admin/content-translations` | Languages & Translations · 2 | System Settings · 3 |
| Interface texts | `/admin/interface-texts` | Languages & Translations · 3 | System Settings · 4 |
| Automatic language | `/admin/automatic-language` | Languages & Translations · 4 | System Settings · 5 |
| Theme & appearance | `/admin/manage-theme` | Settings · 2 | System Settings · 6 |
| Email | `/admin/email` | Settings · 3 | System Settings · 7 |
| Upload limits | `/admin/upload-limits` | Settings · 4 | System Settings · 8 |
| Visitor analytics | `/admin/analytics/settings` | — (new in this update) | System Settings · 9 |
| Plugins | `/admin/modules` | Plugins · 1 | System Settings · 10 |
| Launch Checklist | `/admin/launch-checklist` | Maintenance · 1 | System Settings · 11 |
| Database & Backups | `/admin/database-backups` | Maintenance · 2 | System Settings · 12 |
| Diagnostics | `/admin/diagnostics` | Maintenance · 3 | System Settings · 13 |
| Dashboard | `/admin` | (standalone, first) | Dashboard (standalone, first) |
