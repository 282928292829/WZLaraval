# WordPress to Laravel Data Migration

> Read this file only when migrating a new site. Not needed for routine development.

## Overview

Migrate ~66k+ orders and related data from the legacy WordPress system into Laravel. Data source: legacy DB dump. Behavior reference: new WordPress site (`Wordpress/pwa3/app/public/`). WordPress DB is accessible for migration.

**Source:** Legacy site SQL dump (`pwa3/old-wordpress/wasetzonjan302026.sql`) — all 20 sites use this format.

## Key Paths

- Legacy DB dump: `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/wasetzonjan302026.sql`
- Legacy uploads: `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/old-wp-content/uploads/`
- Laravel site: `wasetzonlaraval/`

## Phases

1. **Match WordPress statuses** — Align Laravel to WP's 8 order statuses (0–7) before import. Same meaning, same labels. Store as string slugs in Laravel.
2. **Import data** — Run migration script(s) against legacy DB. Map WP 0–7 → Laravel slugs. Validate counts and referential integrity.
3. **Make statuses configurable** — After migration is verified, add admin-managed statuses (e.g. Filament CRUD on `order_statuses` table). New/changed statuses from admin only.

## Phase 5 — Data Migration (from build plan)

Write generic, reusable migration scripts:

- Export: orders (66k+), users, blog posts (66), comments, static pages, files/attachments from the **legacy site** (custom post types, meta tables)
- Transform: WordPress custom post types/meta → proper Laravel relational schema (orders → `orders` + `order_items`, order comments → `order_comments`, post comments → `post_comments`, etc.)
- Import: seed MySQL with all migrated data
- File migration: WordPress uploads → Laravel `storage/app/public/`
- Integrity validation: row counts, spot-check orders, verify comment threading
- **Design migration scripts as generic transformers** — no Wasetzon-specific hardcoding, so AI can reuse them for the other 19 sites
- Document the mapping: WordPress field → Laravel column

**Dry-run (Phase 1):** Run migration scripts against the legacy SQL dump early to validate schema design against real data — surface surprises before building the full app.

## Data to Import

| Entity | WP Source | Laravel Target |
|--------|-----------|----------------|
| Users | `wp_users` | `users` |
| Addresses | `wp_usermeta` (saved_addresses) | `user_addresses` |
| Orders | Order posts + post_meta | `orders` |
| Order items | order_products_json / p_* meta | `order_items` |
| Comments | `wp_comments` (comment_post_ID = order post) | `order_comments` |
| Timeline | post_meta `activity_log` | `order_timeline` |
| Files | payment_receipt, product images, attachments | `order_files` + storage |
| Merges | post_meta merged_into / merged_from | `orders.merged_into` (resolve after orders exist) |

## Import Order

1. Users
2. User addresses
3. Orders (with status map, merge IDs resolved in second pass)
4. Order items
5. Order comments
6. Order timeline
7. Order files (copy to Laravel storage)
8. Fix merge references (merged_into, merged_at, merged_by)

## Status Mapping (WP 0–7 → Laravel slugs)

| WP | WP Label (AR) | Laravel slug |
|----|---------------|--------------|
| 0 | جاري حساب قيمة الطلب | pending |
| 1 | تم حساب قيمة الطلب | needs_payment |
| 2 | جاري التنفيذ وانتظار وصول الطلبات لمقرنا | processing |
| 3 | تم اصدار الفاتورة النهائية | purchasing |
| 4 | تم الشحن | shipped |
| 5 | تــم التسليم | delivered |
| 6 | طلب ملغي | cancelled |
| 7 | بإنتظار توضيح العميل | on_hold |

Note: Laravel currently has 9 statuses (includes `completed`). Consolidate to 8 to match WP, or map WP 5 → delivered/completed as appropriate.

## Import Command

```bash
php -d memory_limit=512M artisan wp:import --all
```

Or run step-by-step: `--users`, `--addresses`, `--orders`, `--items`, `--comments`, `--timeline`.

## Validation

- Row counts: users, orders, order_items, order_comments, order_timeline, order_files
- Spot-check: sample orders with full chain (user → order → items → comments → timeline)
- Referential integrity: no orphaned FKs
- Status distribution: compare WP vs Laravel status counts

## Migration Template Design

The Wasetzon codebase doubles as the template for all 19 remaining legacy sites:

- **Features reference:** New WordPress site (`pwa3/app/public/`) — replicate functionality only, design UI from scratch
- **Data migration:** Read from legacy site SQL dump (custom post types, meta tables)
- Migration scripts are written as generic transformers (legacy WordPress meta → relational schema)
- No Wasetzon-specific hardcoding in migration logic
- Document the mapping: legacy custom post type fields → Laravel schema columns
- AI can later run: "Use this Laravel structure, migrate [legacy site X] into it"
