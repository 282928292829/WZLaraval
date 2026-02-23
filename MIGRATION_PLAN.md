# WordPress to Laravel Migration Plan

## Overview

Migrate ~66k+ orders and related data from the legacy WordPress system into Laravel. Data source: legacy DB dump. Behavior reference: new WordPress site (`Wordpress/pwa3/app/public/`).

## Phases

1. **Match WordPress statuses** — Align Laravel to WP's 8 order statuses (0–7) before import. Same meaning, same labels. Store as string slugs in Laravel.
2. **Import data** — Run migration script(s) against legacy DB. Map WP 0–7 → Laravel slugs. Validate counts and referential integrity.
3. **Make statuses configurable** — After migration is verified, add admin-managed statuses (e.g. Filament CRUD on `order_statuses` table). New/changed statuses from admin only.

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

## Key Paths

- Legacy DB: `Wordpress/pwa3/old-wordpress/wasetzonjan302026.sql`
- Legacy uploads: `Wordpress/pwa3/old-wordpress/old-wp-content/uploads/`
- Laravel: `wasetzonlaraval/`

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
