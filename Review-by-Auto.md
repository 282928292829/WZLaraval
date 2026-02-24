# Review by Auto — Wasetzon Laravel

AI-readable checklist. Fix in order of tiers. Be direct; no fluff.

---

## Tier 1 — Blockers before production

### 1.1 Default locale is English
- **Files:** `config/app.php`, `.env.example`
- **Issue:** `config/app.php` line 81: `'locale' => env('APP_LOCALE', 'en')`. Plan: default language is Arabic.
- **Fix:** Change default to `'ar'`. In `.env.example` set `APP_LOCALE=ar` and `APP_FALLBACK_LOCALE=en`. Document in deploy notes.

### 1.2 Missing permission `view-staff-dashboard`
- **Files:** `database/seeders/RoleAndPermissionSeeder.php`, `resources/views/dashboard.blade.php` line 281
- **Issue:** `@can('view-staff-dashboard')` used in dashboard; permission not in seeder. Gate always false.
- **Fix:** Add `'view-staff-dashboard'` to editor-level permissions array in RoleAndPermissionSeeder. Run `php artisan db:seed --class=RoleAndPermissionSeeder` (or add migration that creates permission and assigns to editor, admin, superadmin).

### 1.3 PWA manifest icons missing
- **Files:** `public/manifest.json`, `public/icons/`
- **Issue:** Manifest references `/icons/icon-72x72.png` … `icon-512x512.png`. `public/icons/` is empty or missing.
- **Fix:** Add icon set to `public/icons/` (72, 96, 128, 144, 152, 192, 384, 512) or point manifest at existing assets. Ensure icons exist in repo and deploy.

### 1.4 Production env and deploy docs
- **Files:** `.env.example`, `README.md`
- **Issue:** `.env.example` uses SQLite; plan assumes MySQL 8 + Redis. No Wasetzon deploy steps.
- **Fix:** Add production `.env` example or section: MySQL, Redis, `APP_DEBUG=false`, `APP_ENV=production`, `APP_LOCALE=ar`. In README add: stack (Laravel 12, Breeze, Livewire, Filament, Spatie, MySQL, Redis), required env vars, `composer install`, `npm run build`, `php artisan migrate`, seeders, nginx 301 `^/order/(.*)$` → `/orders/$1`, queue workers, SSL.

---

## Tier 2 — Correctness and maintainability

### 2.1 OrderController: no Form Requests
- **Files:** `app/Http/Controllers/OrderController.php`
- **Issue:** All actions use `Request $request` and inline `$request->validate()`. Project rule: use Form Request classes.
- **Fix:** Add Form Requests per action (e.g. `StoreOrderCommentRequest`, `UpdateOrderStatusRequest`, `MarkPaidRequest`, …). Move rules and messages there. Inject in controller. Start with comment and status.

### 2.2 No OrderPolicy
- **Files:** `app/Http/Controllers/OrderController.php`, missing `app/Policies/OrderPolicy.php`
- **Issue:** Order access is ad-hoc (`$isOwner`, `$isStaff`, `$user->can()`). No single place for “who can do what on Order.”
- **Fix:** Create `OrderPolicy`. Use `$this->authorize('view', $order)` (and `update` etc.) in controller. Map Spatie permissions inside policy methods. Keep fine-grained `can()` for specific actions; policy for resource-level access.

### 2.3 env() outside config
- **File:** `app/Console/Commands/Migration/MigrateOrderFiles.php` line 53
- **Issue:** `env('LEGACY_UPLOADS_PATH', base_path(...))` — rule: no `env()` outside config.
- **Fix:** Add `config('migration.legacy_uploads_path')` (or similar). Define in `config/migration.php` reading from env. Use config in command. Document in MIGRATION.md.

### 2.4 Email layout hardcoded ar/rtl
- **Files:** `resources/views/emails/layout.blade.php`, `resources/views/components/emails/layout.blade.php`
- **Issue:** `lang="ar" dir="rtl"` hardcoded. Breaks if emails are sent in user locale (e.g. English).
- **Fix:** Set `lang` and `dir` from notification locale (e.g. `app()->getLocale()` or passed variable). Use RTL when locale is `ar`.

---

## Tier 3 — Process and docs

### 3.1 README not Wasetzon-specific
- **File:** `README.md`
- **Issue:** Stock Laravel README. No local/production setup for this project.
- **Fix:** Add short Wasetzon section: stack, required env (APP_LOCALE=ar, DB, Redis), install (composer, npm, migrate, seed), link to LARAVEL_PLAN.md and MIGRATION.md.

### 3.2 New-order rate limit (optional)
- **Files:** `routes/web.php`, `app/Http/Middleware/RoleBasedThrottle.php`
- **Issue:** Hourly limit enforced inside `NewOrder::submitOrder()`; route has no `role.throttle` middleware.
- **Fix:** Optional. Add `->middleware('role.throttle:new-order')` to new-order route for defense in depth. Not required if in-component limit is sufficient.

---

## Tier 4 — Quality and consistency

### 4.1 Tests: Policy and controller auth
- **Files:** `tests/Feature/`, no OrderPolicy or OrderController auth tests
- **Issue:** No tests that assert 403 for customers on staff actions or 200 for editors.
- **Fix:** Add feature tests: customer cannot update status / merge / export; editor can. Optionally one Policy test for Order.

### 4.2 Order status count (8 vs 9)
- **Files:** `LARAVEL_PLAN.md`, `MIGRATION.md`, `app/Models/Order.php`
- **Issue:** Plan says “match WP 8 statuses”; Laravel has 9 (includes `completed`). MIGRATION.md suggests mapping WP 5 → delivered/completed.
- **Fix:** Document final mapping in MIGRATION.md (e.g. WP 5 → delivered; Laravel `completed` for closed). Keep migration script and Order model in sync.

### 4.3 N+1 before QA
- **Files:** `app/Http/Controllers/OrderController.php` (index/staff), `app/Http/Controllers/InboxController.php`
- **Issue:** OrderController::show() eager-loads; list and inbox may not.
- **Fix:** Before Phase 6 QA, audit orders list and inbox; add `with([...])` where needed. Check query count in logs.

---

## Quick reference

| Tier | Item | Key file(s) |
|------|------|--------------|
| 1.1 | Locale default | config/app.php, .env.example |
| 1.2 | view-staff-dashboard | RoleAndPermissionSeeder, dashboard.blade.php |
| 1.3 | PWA icons | public/manifest.json, public/icons/ |
| 1.4 | Deploy env/docs | .env.example, README.md |
| 2.1 | Form Requests | OrderController.php |
| 2.2 | OrderPolicy | OrderController.php, app/Policies/ |
| 2.3 | env() in command | MigrateOrderFiles.php, config/ |
| 2.4 | Email layout locale | emails/layout.blade.php, components/emails/layout.blade.php |
| 3.1 | README | README.md |
| 3.2 | Rate limit (opt) | routes/web.php |
| 4.1 | Auth tests | tests/Feature/ |
| 4.2 | Status mapping | MIGRATION.md |
| 4.3 | N+1 | OrderController, InboxController |

---

## Verified facts (no change needed)

- Language toggle is in footer (layouts/app.blade.php). Matches wasetzon.mdc.
- RTL: `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"` on main layouts. Correct.
- Order rate limit: enforced in NewOrder::submitOrder() from settings. Acceptable.
- Authorization: OrderController uses Spatie permissions via `authorize('permission-name')` and `$user->can()`. No Policy yet but permissions are correct.
- Raw SQL in NewOrder (order_number max): uses fixed regex/CAST, no user input. Safe.
- order_comment_edits and order_comment_reads tables exist. Comment edit/read flow supported.
- Translation keys opus46.* and order.* exist in lang/ar.json and lang/en.json.
