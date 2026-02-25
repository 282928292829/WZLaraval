# WordPress to Laravel Data Migration

> Read this file only when migrating a new site. Not needed for routine development.

## Overview

Migrate ~66k+ orders and related data from the legacy WordPress system into Laravel. Data source: legacy DB dump. Behavior reference: new WordPress site (`Wordpress/pwa3/app/public/`). WordPress DB is accessible for migration.

**Source:** Legacy site SQL dump (`pwa3/old-wordpress/wasetzonjan302026.sql`) — all 20 sites use this format.

## Key Paths

- Legacy DB dump: `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/wasetzonjan302026.sql`
- Legacy uploads: `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/old-wp-content/uploads/`
- Laravel site: `wasetzonlaraval/`

**Config:** `config/migration.php` defines `legacy_uploads_path` (from `LEGACY_UPLOADS_PATH` env). Set in `.env` for migration.

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
| Ad campaigns | `wp_posts` (post_type=myads) + post_meta | `ad_campaigns` |
| Comment templates | `wp_posts` (post_type=comments_template) + post_meta usage_count | `comment_templates` |
| Users | `wp_users` | `users` |
| Addresses | `wp_usermeta` (saved_addresses) | `user_addresses` |
| Orders | Order posts + post_meta | `orders` |
| Order items | order_products_json / p_* meta | `order_items` |
| Comments | `wp_comments` (comment_post_ID = order post) | `order_comments` |
| Timeline | post_meta `activity_log` | `order_timeline` |
| Files | payment_receipt, product images, attachments | `order_files` + storage |
| Merges | post_meta merged_into / merged_from | `orders.merged_into` (resolve after orders exist) |

## Import Order

1. Ad campaigns (myads → ad_campaigns)
2. Comment templates (comments_template → comment_templates)
3. Users
4. User addresses
5. Orders (with status map, merge IDs resolved in second pass)
6. Order items
7. Order comments
8. Order timeline
9. Order files (copy to Laravel storage)
10. Fix merge references (merged_into, merged_at, merged_by)

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

**Final mapping:** WP 5 (تــم التسليم) → Laravel `delivered`. Laravel `completed` is for closed/finalized orders (use when order is fully done and archived). Migration script maps WP 0–7 to Laravel slugs; keep Order model `getStatuses()` and migration in sync.

## Import Command

```bash
php -d memory_limit=512M artisan wp:import --all
```

Or run step-by-step: `--ad-campaigns`, `--comment-templates`, `--users`, `--addresses`, `--orders`, `--items`, `--comments`, `--timeline`.

## Validation

- Row counts: users, orders, order_items, order_comments, order_timeline, order_files
- Spot-check: sample orders with full chain (user → order → items → comments → timeline)
- Referential integrity: no orphaned FKs
- Status distribution: compare WP vs Laravel status counts

## Phase 5 — Pre-Production Checklist (Blocking)

**Do not go live until all steps pass.**

1. **Configure legacy DB** — Add `legacy` connection in `config/database.php` pointing at the legacy dump (or imported MySQL). Set `LEGACY_UPLOADS_PATH` in `.env` for file migration.

2. **Dry-run** — `php artisan migrate:all --dry-run` runs `migrate:validate` only. Use this to verify the legacy connection and schema before importing.

3. **Full migration in staging** — Run `php artisan migrate:all --fresh` (or incremental without `--fresh`) against a staging copy of the legacy DB. Expect ~66k+ orders.

4. **Validate** — `php artisan migrate:validate --sample=20` checks row counts, spot-checks orders, and FK integrity. Fix any issues before production.

5. **Spot-check** — Manually verify sample orders in the Laravel UI: user → order → items → comments → timeline. Document any mapping fixes here.

## Migration Template Design

The Wasetzon codebase doubles as the template for all 19 remaining legacy sites:

- **Features reference:** New WordPress site (`pwa3/app/public/`) — replicate functionality only, design UI from scratch
- **Data migration:** Read from legacy site SQL dump (custom post types, meta tables)
- Migration scripts are written as generic transformers (legacy WordPress meta → relational schema)
- No Wasetzon-specific hardcoding in migration logic
- Document the mapping: legacy custom post type fields → Laravel schema columns
- AI can later run: "Use this Laravel structure, migrate [legacy site X] into it"
