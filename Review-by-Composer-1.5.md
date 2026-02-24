# Wasetzon Laravel — Review by Composer 1.5

> AI-readable. Direct and concise. Verify facts before implementing.
> **Re-verified:** 2025-02-24. Corrections applied (see end).

---

## CRITICAL (Fix Before Production)

### 1. Missing Permission: `view-staff-dashboard`
- **Location:** `resources/views/dashboard.blade.php` line 281 uses `@can('view-staff-dashboard')`
- **Problem:** Permission not in `database/seeders/RoleAndPermissionSeeder.php`
- **Impact:** Staff dashboard section never renders for editors/admins/superadmins
- **Fix:** Add `view-staff-dashboard` to editor-level permissions array in RoleAndPermissionSeeder. Run `php artisan db:seed --class=RoleAndPermissionSeeder`

### 2. `.env.example` Default Locale Wrong
- **Location:** `.env.example` line 7
- **Problem:** `APP_LOCALE=en` — plan specifies Arabic default
- **Fix:** Set `APP_LOCALE=ar`, `APP_FALLBACK_LOCALE=en`. Add comment: `# Default language Arabic per project spec`

---

## HIGH PRIORITY (Architecture)

### 3. OrderController — Inline Validation
- **Location:** `app/Http/Controllers/OrderController.php` — 15+ `$request->validate()` calls
- **Problem:** AGENTS.md requires Form Request classes (LARAVEL_PLAN does not explicitly)
- **Fix:** Create Form Requests: `StoreOrderCommentRequest`, `UpdateOrderStatusRequest`, `MergeOrdersRequest`, `UpdatePricesRequest`, `StoreOrderFileRequest`, `BulkUpdateOrdersRequest`, etc. Move validation rules into them.

### 4. OrderController — Too Large
- **Location:** `app/Http/Controllers/OrderController.php` — 1,109 lines
- **Fix:** Split into focused controllers or single-action classes: OrderController (show, index, bulk), OrderCommentController, OrderStatusController, OrderMergeController, OrderFileController. Or use invokable controllers per action.

### 5. Unused `RoleBasedThrottle` Middleware
- **Location:** `app/Http/Middleware/RoleBasedThrottle.php` registered in `bootstrap/app.php` but never applied to any route
- **Context:** Rate limiting is implemented in `NewOrder::submitOrder()` via DB (hourly/daily order count). Middleware uses Laravel RateLimiter (cache-based).
- **Fix:** Either apply `->middleware('role.throttle')` to `/new-order` route for HTTP-level defense, or remove the middleware if redundant. Recommendation: apply to `/new-order`.

### 6. Bilingual Violations — Hardcoded Strings
- **Location:** `resources/views/orders/show.blade.php`
- **Examples:** Tracking companies (أرامكس, سمسا, DHL, FedEx, UPS), bank names (الراجحي, الأهلي, etc.), phone number 0112898888
- **Fix:** Wrap in `__()`. Add keys to `lang/ar.json` and `lang/en.json`. Consider moving bank/tracking lists to Settings or DB table for admin configurability.

---

## MEDIUM PRIORITY (Documentation & Process)

### 7. Plan vs Implementation Drift
- **lang paths:** Plan line 98 says `lang/ar/`, `lang/en/`; actual: `lang/ar.json`, `lang/en.json` + `lang/ar/`, `lang/en/` dirs. Both exist; plan is inconsistent.
- **Language toggle:** LARAVEL_PLAN line 247 (Design System) says "header"; lines 16 and 91 say "footer". Actual: footer (`layouts/app.blade.php` line 222).
- **Fix:** Update LARAVEL_PLAN.md line 247 to say "footer" not "header".

---

## PHASE 5 — DATA MIGRATION (Blocking Production)

### 9. Migration Not Validated
- **Status:** Commands exist: `MigrateUsers`, `MigrateOrders`, `MigrateOrderComments`, `MigrateOrderFiles`, etc. in `app/Console/Commands/`
- **Problem:** No evidence migration has been run or validated against 66k+ orders
- **Fix:** 1) Dry-run against legacy dump. 2) Validate row counts, referential integrity. 3) Full migration in staging. 4) Spot-check sample orders. 5) Document mapping fixes in MIGRATION.md

---

## SECURITY (Summary)

| Area | Status |
|------|--------|
| CSRF | OK |
| Auth | OK (Breeze + Spatie) |
| Authorization | OK (@can, authorize()) |
| Rate limiting | Partial — DB-based in NewOrder; RoleBasedThrottle unused |
| File uploads | OK (validation, limits) |
| Dev route | OK (local-only, `app()->environment('local')`) |

---

## SCALABILITY

- **Email:** Mail classes (OrderConfirmation, CommentNotification, RegistrationWelcome) implement ShouldQueue ✓
- **Invoice:** Text-based comment, synchronous (no PDF file, no queue)

---

## UX/UI

- **No-scroll forms:** Plan requires sign-in, sign-up, new-order item row to fit on screen without scrolling on mobile. Verify manually on real devices.

---

## PRIORITIZED ACTION ORDER

1. Add `view-staff-dashboard` permission + re-seed
2. Fix `.env.example` (APP_LOCALE=ar)
3. Fix LARAVEL_PLAN.md line 247 (header → footer)
4. Run Phase 5 migration dry-run + validate
5. Create Form Requests for OrderController
6. Split OrderController
7. Apply `role.throttle` to `/new-order` or remove middleware
8. Fix bilingual violations (tracking companies, banks)

---

## KEY PATHS (Reference)

- Legacy DB dump: `Wordpress/pwa3/old-wordpress/wasetzonjan302026.sql`
- Legacy uploads: `Wordpress/pwa3/old-wordpress/old-wp-content/uploads/`
- Features reference: `Wordpress/pwa3/app/public/`
- Laravel site: `wasetzonlaraval/`

---

## WHAT'S WORKING

- Schema: orders, order_items, order_comments, order_timeline, order_files — correct
- Spatie permissions + role hierarchy
- PWA: service worker, manifest, offline page
- WordPress phpass compatibility (WpCompatUserProvider)
- nginx config: legacy redirect, security headers, sw.js no-cache
- Deploy layout in `deploy/nginx.conf`
- Tests: auth, order, rate limit coverage
- Filament Settings, Translations pages
- Bilingual: ar.json, en.json, SetLocale middleware

---

## CORRECTIONS (Re-verification 2025-02-24)

| Claim | Was | Corrected To |
|-------|-----|--------------|
| #9 wasetzon.mdc duplicate | "Lines 49–94 duplicate lines 5–47" | **REMOVED.** wasetzon.mdc is 48 lines; no duplicate. Prior read showed merged context from other files. |
| #8 Language toggle | "lines 16 and 92" | Line 92 is RTL config. Correct: lines 16 and 91 say "footer". Line 247 says "header". |
| #3 Form Request | "plan require" | LARAVEL_PLAN does not explicitly require Form Requests. AGENTS.md does. |
| Scalability | "Verify PDF/email queued" | Email: Mail classes implement ShouldQueue ✓. Invoice: text comment, synchronous (no PDF job). |
