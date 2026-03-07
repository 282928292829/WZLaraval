# WordPress to Laravel Migration — Schema & Specification

> **Sole source:** `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress` (legacy production site).  
> **Do not use:** `app/public` or any test site — that is a separate rebuild, not the migration source.

---

## 1. Source Path (Canonical)

| What | Path |
|------|------|
| Legacy root | `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress` |
| SQL dump | `old-wordpress/wasetzonjan302026.sql` |
| wp-content | `old-wordpress/old-wp-content/` |
| Uploads | `old-wordpress/old-wp-content/uploads/` |
| Themes | `old-wordpress/old-wp-content/themes/` (e.g. etejarh) |
| Plugins | `old-wordpress/old-wp-content/plugins/` |

**Config:** Set `LEGACY_UPLOADS_PATH` in `.env` to the absolute path of `old-wp-content/uploads/`. Default in `config/migration.php`: `../Wordpress/pwa3/old-wordpress/old-wp-content/uploads`.

---

## 2. Database Structure (20 Sites — Same Schema)

All 20 WordPress sites share the same database structure. Core tables used for migration:

| WP Table | Purpose |
|----------|---------|
| `wp_posts` | Posts, pages, custom post types (orders, myads, comments_template, post, page) |
| `wp_postmeta` | Meta for posts (order status, products, activity_log, merged_into, etc.) |
| `wp_users` | Users |
| `wp_usermeta` | User meta (saved_addresses, etc.) |
| `wp_comments` | Comments (order comments: comment_post_ID = order post ID) |
| `wp_commentmeta` | Comment attachments, etc. |
| `wp_terms`, `wp_term_taxonomy`, `wp_term_relationships` | Categories for posts |

---

## 3. Post Types (from old-wordpress dump)

| post_type | Count (approx) | Laravel Target |
|-----------|----------------|----------------|
| orders | ~66k | orders + order_items |
| attachment | ~54k | file refs in order_files |
| post | ~70 | posts |
| page | ~42 | pages |
| myads | ~10 | ad_campaigns |
| comments_template | ~34 | comment_templates |
| revision, nav_menu_item, etc. | varies | skipped |

---

## 4. Orders — wp_posts + wp_postmeta Mapping

**Critical:** wp_post_id MUST be set for every order. No order may have NULL wp_post_id. Downstream steps (comments, timeline, files) depend on it for mapping.

**wp_posts (post_type='orders', post_status='publish'):**
- `ID` → wp_post_id (store for mapping)
- `post_author` → user_id (map WP user ID to Laravel by email)
- `post_content` → notes
- `post_name` → **order_number** (unique orders = plain number; duplicates = order_id-2, -3, etc.)
- `post_date`, `post_modified` → created_at, updated_at

**wp_postmeta (post_id = order post ID):**

| meta_key | Type | Laravel Target |
|----------|------|----------------|
| order_id | string | Display/reference; order_number comes from post_name |
| order_status | 0–7 | status (mapped to slug) |
| p_url_N, p_qty_N, p_color_N, p_size_N, p_info_N, p_price_N, p_img_N | N=1..30 | order_items |
| order_products_json | JSON | Alternative product source (legacy) |
| payment_amount, payment_date, payment_method, payment_receipt | — | orders |
| tracking_number, tracking_company | — | orders |
| shipping_address_snapshot | JSON | orders |
| activity_log | JSON | order_timeline |
| merged_into | post_id | orders.merged_into (second pass after orders exist) |

**Order status mapping (WP 0–7 → Laravel slug):**

| WP | Laravel |
|----|---------|
| 0 | pending |
| 1 | needs_payment |
| 2 | processing |
| 3 | purchasing |
| 4 | shipped |
| 5 | delivered |
| 6 | cancelled |
| 7 | on_hold |

---

## 5. Order Comments — wp_comments

- `comment_post_ID` = order post ID (join wp_posts where post_type='orders')
- `user_id` → maps to Laravel user by email (wp_users)
- `comment_content`, `comment_date`, `comment_approved`
- `comment_approved`: '1' or '0' = migrate; 'trash'/'spam' = skip

**Critical for migration:**
- Store wp_comment_id in order_comments for idempotency (skip on re-run).
- Skip comments where TRIM(comment_content) = ''.
- Build wp_post_id→order_id map from orders WHERE wp_post_id IS NOT NULL. Fail if any order has NULL wp_post_id before running this step.

---

## 6. Ad Campaigns — myads

**wp_postmeta:** unique_url, website, clicks, register, orders, purchase

---

## 7. Comment Templates — comments_template

**wp_postmeta:** usage_count, template content

---

## 8. Users — wp_users

- user_email, user_login, user_pass, display_name, etc.
- Map to Laravel users; roles assigned by separate step (assign-superadmins).

---

## 9. Addresses — wp_usermeta

- meta_key = 'saved_addresses'
- meta_value = serialized or JSON array of addresses
- Map wp user_id → Laravel user_id by email

---

## 10. Timeline — activity_log

- wp_postmeta meta_key = 'activity_log'
- meta_value = JSON array of {type, body, date, user?}
- Map post_id → Laravel order_id via orders.wp_post_id

---

## 11. Order Files

- **Product images:** p_img_N → WP attachment ID → guid → path in uploads/
- **Comment attachments:** wp_commentmeta attachmentId
- Copy files to Laravel storage; create order_files rows

---

## 12. Posts & Pages

- **Posts:** wp_posts post_type='post' + categories (wp_terms)
- **Pages:** wp_posts post_type='page'; skip slugs: new-order, orders, new-order-2, singleorders2
- **Post comments:** wp_comments where comment_post_ID in post IDs
- **Page comments:** wp_comments where comment_post_ID in page IDs (post_type='page'); MigratePages writes migration_wp_page_id_map.json for migrate:page-comments

---

## 13. Migration Order (Dependencies)

1. ad-campaigns
2. comment-templates
3. users
4. addresses (needs users)
5. orders (needs users; store wp_post_id for mapping)
6. order-comments (needs orders)
7. timeline (needs orders)
8. fix-merges (needs orders; resolves merged_into)
9. order-files (needs orders)
10. posts
11. post-comments (needs posts)
12. pages (writes migration_wp_page_id_map.json)
13. page-comments (needs pages; uses page ID map)
14. assign-superadmins (needs users)
15. validate

---

## 14. Config Requirements (Per Site)

For each of the 20 sites, set in `.env`:

- `LEGACY_DB_HOST`, `LEGACY_DB_DATABASE`, `LEGACY_DB_USERNAME`, `LEGACY_DB_PASSWORD`
- `LEGACY_UPLOADS_PATH` = absolute path to that site's wp-content/uploads

The migration code must be generic — no site-specific logic.

---

## 15. Theme Reference (old-wordpress)

The legacy theme is **etejarh** (`old-wp-content/themes/etejarh/`). Order creation uses:
- template-order.php (product slots p_url_N, p_qty_N, p_size_N, p_color_N, p_info_N, p_price_N, p_N for N=1..30)
- template-list.php
- template-dashboard.php

Use this theme (and the SQL dump) as the schema reference, not app/public.
