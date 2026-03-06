# Prompt for New Chat: Build WordPress → Laravel Migration From Scratch

Copy and paste the following into a new chat:

---

## Task

Build a **new** WordPress-to-Laravel migration from scratch for the Wasetzon Laravel project. The migration must be generic and reusable for 20 WordPress sites that share the same database schema.

## Critical Rules

1. **Sole source:** All migration logic, schema reference, and file paths must use ONLY:
   - `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress`
   - This is the legacy production site. Do NOT use `Wordpress/pwa3/app/public` — that is a separate test site and must be ignored.

2. **Data:** Import from the legacy MySQL database (connection name: `legacy` in `config/database.php`). The SQL dump is at `old-wordpress/wasetzonjan302026.sql` — import it into a MySQL database and configure `LEGACY_DB_*` in `.env`.

3. **Files:** Use `LEGACY_UPLOADS_PATH` (from `config/migration.php`) pointing to `old-wordpress/old-wp-content/uploads/` for order images and comment attachments.

4. **Generic:** No site-specific hardcoding. All 20 sites have identical DB structure. When migrating another site, only change: legacy DB connection + `LEGACY_UPLOADS_PATH`.

## Reference Document

Read `MIGRATION_SCHEMA.md` in the wasetzonlaraval project. It documents:
- Canonical source path (old-wordpress only)
- Database tables and post types
- Order meta keys (p_url_N, p_qty_N, order_status, activity_log, merged_into, etc.)
- Status mapping (WP 0–7 → Laravel slugs)
- Migration order and dependencies
- Config requirements per site

## Laravel Project Context

- Path: `wasetzonlaraval/` (Laravel 12, Filament, Livewire)
- Existing migration commands exist in `app/Console/Commands/Migration/` but we are starting fresh — you may reference them for schema understanding but the new migration should be built cleanly from `MIGRATION_SCHEMA.md`.
- Target tables: orders, order_items, order_comments, order_timeline, order_files, users, user_addresses, ad_campaigns, comment_templates, posts, post_categories, post_comments, pages.
- Orders use `order_number` (from wp post_name) and `wp_post_id` for mapping; route key is order_number.
- Order number format: unique orders = plain number (e.g. 66610); duplicates = order_id-2, -3 (e.g. 42548-2).

## Step 0: Delete Existing Migration Code First

Remove `app/Console/Commands/Migration/` and any `migrate:all` orchestrator. Build fresh from spec.

## User Decisions (Implement As-Is)

- **Duplicate order numbers:** Use WP-style suffixes (-2, -3). First order keeps plain number; 2nd = -2, 3rd = -3. Match old site exactly.
- **Orphan comments by ulgasan581@gmail.com (4):** Assign to fallback admin. User 39358 deleted; orders 24897, 25127, 66120.
- **Product URLs / test-looking data:** Migrate all. Customers can enter text or numbers; do not enforce URL format.
- **Invalid merge targets:** Skip and log (legacy has 0 merges).

## Critical Implementation Rules (Must Follow for 100% Integrity)

1. **wp_post_id is mandatory for every order.**
   - MigrateOrders MUST set wp_post_id for every inserted order. No exceptions.
   - Run a post-orders check: fail if any order has NULL wp_post_id before running order-comments.
   - Reason: migrate:order-comments builds the wp_post_id→order_id map; missing wp_post_id = skipped comments + wrong user fallback.

2. **Strict step order and dependencies.**
   - migrate:order-comments MUST run only after migrate:orders completes fully.
   - Do not run any step that depends on orders (comments, timeline, files) until all orders exist with wp_post_id set.

3. **Order comments: idempotency and empty-body alignment.**
   - Store wp_comment_id in order_comments (add column if needed) to enable skip-on-re-run and avoid duplicates.
   - Empty-body logic: skip when trim(body) is empty. Verify step must use the exact same rule (TRIM(comment_content) = '' or equivalent).

4. **Verification gates before completion.**
   - migrate:verify-order-numbers — must pass 100%.
   - migrate:verify-order-comments — must pass 100% (per-order comment count match).
   - User mapping audit: compare legacy post_author email vs Laravel user email per order; must be 0 mismatches.
   - Fail the migration if any verification fails; do not proceed.

## Steps

1. Read `MIGRATION_SCHEMA.md` thoroughly.
2. Ensure config/migration.php and database.php legacy connection are correct for old-wordpress.
3. Build migration commands in the correct dependency order (ad-campaigns → comment-templates → users → addresses → orders → order-comments → timeline → fix-merges → order-files → posts → post-comments → pages → assign-superadmins → validate).
4. Each command must read from `DB::connection('legacy')` and write to the default connection. Use `config('migration.legacy_uploads_path')` for file paths.
5. Handle edge cases per User Decisions above. Missing order authors → fallback to admin.
6. Update MIGRATION.md to remove any reference to app/public; state old-wordpress is the sole source.
7. Build migrate:verify-order-numbers and migrate:verify-order-comments. Run them after migrate:orders and migrate:order-comments respectively. Fail if mismatches.
8. Build a user-mapping verification step (or add to migrate:validate): compare post_author→email vs order.user_id→email; report and fail on any mismatch.

## Output

- Working migration commands that run via `php artisan migrate:all` (or equivalent orchestrator).
- All steps complete without errors against the legacy DB.
- Documentation updated. No code references app/public.
