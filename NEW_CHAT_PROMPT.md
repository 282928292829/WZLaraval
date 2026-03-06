# Prompt: Start New Chat for Migration

Copy and paste the following into a new chat:

---

## Task

Run the WordPress → Laravel migration for the wasetzonlaraval project and achieve 100% data integrity.

**Context:**
- Project: `wasetzonlaraval/` (Laravel 12)
- Sole source: `Wordpress/pwa3/old-wordpress` (legacy DB + uploads)
- Legacy DB: connection `legacy` in config; set `LEGACY_DB_*` and `LEGACY_UPLOADS_PATH` in `.env`
- Migration commands exist in `app/Console/Commands/Migration/`
- Read `MIGRATION_PROMPT.md`, `MIGRATION_SCHEMA.md`, and `MIGRATION.md` for full spec

**What to do:**

1. **Run full migration:** `php artisan migrate:all --fresh` (or `php -d memory_limit=512M artisan migrate:all --fresh`)
2. **Run verification and fix until 100% pass:**
   - `php artisan migrate:verify-order-numbers` — must report "All X orders match legacy 100%"
   - `php artisan migrate:verify-order-comments` — must report 0 mismatches
   - User mapping audit: compare legacy `post_author` email vs Laravel `order.user_id` email for every order; must be 0 mismatches
   - If any order has `wp_post_id = NULL`, fix before proceeding
3. Fix any issues that prevent 100% pass. Do not consider the migration complete until all verifications pass.

**Critical rules (from MIGRATION_PROMPT.md):**
- Every order must have `wp_post_id` set. Fail if any NULL.
- migrate:order-comments depends on the wp_post_id→order_id map. Missing wp_post_id = wrong user + missing comments.
- Verification gates are blocking — do not proceed if they fail.
