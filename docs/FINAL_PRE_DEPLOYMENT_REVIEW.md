# Final Pre-Deployment Review — Wasetzon Laravel
**Date:** 2026-03-08
**Reviewer:** Senior Engineer / Pre-Deployment Audit
**Scope:** Full codebase — architecture, security, performance, UX/UI, business logic, deployment
**Stack:** Laravel 12, Livewire 3, Filament 4, Tailwind v4, Spatie Permission, MySQL 8, Redis

---

## Executive Summary

The Wasetzon Laravel rebuild is architecturally sound: proper use of policies, Form Requests, eager loading, transactions, and bilingual translation. Authorization is correctly layered, XSS protection is solid, and the deployment pipeline is production-ready in most respects.

**However, several issues require immediate attention before launch:**

- **Three critical production-blocking issues:** exposed dev routes, missing caching on `Setting::get()`, and a weak password minimum
- **Two high-priority data integrity issues:** order merge not wrapped in a transaction, and file orphaning during order edits
- **One missing feature listed in the current task:** duplicate button on the order show page

All Critical items must be resolved before go-live. High items should also be resolved; none requires major architecture changes. Medium and Low items are post-launch acceptable with appropriate tracking.

---

## CRITICAL — Must fix before launch

### Security

#### C1 — Dev/demo routes exposed unconditionally in production
**File:** `routes/web.php:80-190`
**Risk:** Information disclosure, user confusion, SEO impact

Routes registered without environment guard:
- `/new-order-design-1`, `/new-order-design-2`, `/new-order-design-3` — design prototype pages
- `/layout-demo/app`, `/layout-demo/guest`, `/layout-demo/order`, `/layout-demo/bare`
- `/homepagetest555`, `/homepagetest666`, `/homepagetest777`, `/homepagetest888`
- `/test-homepage-demo1`, `/test-homepage-demo2`, `/test-homepage-demo3`, `/test-homepage-demo4`
- Also: `OrderDesignController` is unconditionally routable

These routes expose internal UI prototypes to the public internet. Wrap them all in `if (app()->environment('local'))` or delete them before deploy. The `/_dev/login-as` route is correctly gated; these are not.

#### C2 — Minimum password length is 4 characters
**File:** `app/Http/Controllers/Auth/RegisteredUserController.php:38`
**Risk:** Brute force, credential stuffing on a financial platform

```php
'password' => ['required', 'confirmed', Rules\Password::min(4)],
```

Four characters is far too low for a platform handling real money and personal data. The minimum should be **8 characters at minimum** (12 recommended). Also consider adding `->mixedCase()->numbers()` to require complexity. This is a financial/logistics platform — weak passwords put customer accounts and their order/payment history at direct risk.

#### C3 — `Setting::get()` has no caching — 30–60+ DB queries per page load
**File:** `app/Models/Setting.php:18-31`
**Risk:** Performance collapse under load; settings are the most frequently read data in the system

The `get()` method executes a `SELECT` query on every call:
```php
public static function get(string $key, mixed $default = null): mixed
{
    $setting = static::where('key', $key)->first();  // DB hit every time
    ...
}
```

`Cache::forget()` is called in `set()`, but `get()` never reads from or writes to cache. A single page load triggers this query 30–60+ times:
- `SetLocale` middleware calls it once
- `AppServiceProvider` calls it once
- `OrderController::show()` calls it 6–8 times
- `CommissionCalculator::calculate()` calls it 6 times + an `exists()` check
- `CommissionCalculator::getSettings()` calls it 6 more times (called separately)
- `format_datetime_for_display()` calls it twice per invocation
- `NewOrder::mount()` calls it 6+ times
- Every invoice generation: 20+ calls
- Every Livewire re-render: settings are re-queried

At 100 concurrent users, this is thousands of unnecessary DB queries per second. The `Cache::forget()` in `set()` already assumes a cache-first approach; the `get()` method simply needs to complete the contract by reading from cache first, writing on miss.

---

### Feature Completeness

#### C4 — Duplicate button missing on order show page
**File:** `resources/views/orders/show.blade.php`
**Risk:** Stated Current Task in `wasetzon.mdc` is not implemented

Per `wasetzon.mdc` Current Task: *"Duplicate button on order show page (`/orders/{id}`) — visible to customers and staff, links to `/new-order?duplicate_from={id}`"*

The `prefillFromDuplicate()` logic in `NewOrder.php` is complete and tested. The `?duplicate_from={id}` parameter is handled in `mount()`. The button is simply not rendered on the order detail page. Without it, customers have no discoverable path to re-order.

---

## HIGH — Fix before launch

### Security

#### H1 — Path traversal risk in PageController
**File:** `app/Http/Controllers/PageController.php:36-38`

```php
$dedicatedView = 'pages.'.str_replace('-', '_', $slug);
if (view()->exists($dedicatedView)) {
    return view($dedicatedView, $viewData);
}
```

`$slug` comes from the database (admin-created), but if an admin accidentally or maliciously creates a slug like `../../config/app`, Laravel's view loader would attempt to resolve that path. Admins are trusted, but slugs are not validated before use in a view path. Fix: validate that `$slug` matches `/^[a-z0-9_-]+$/` before using it in a view name.

#### H2 — Social auth creates users with null email (Apple)
**File:** `app/Http/Controllers/Auth/SocialAuthController.php:34-75`

Apple Sign-In can return a null email on subsequent logins (only shares email on first auth). The `loginOrCreate()` method can create a user with `$email = null`. A null-email user:
- Cannot use the forgot-password flow
- Has no email to send notifications to
- Cannot have their email verified
- Could cause null-reference errors in email-dependent code paths

When `$email` is null, either require it (abort with a user-friendly error asking them to use email/password login), or generate a placeholder and flag the account for email collection on next login.

#### H3 — Social auth does not fire `Registered` event
**File:** `app/Http/Controllers/Auth/SocialAuthController.php:69`

New users created via social auth get `$user->assignRole('customer')` but no `event(new Registered($user))` is dispatched. This means:
- Welcome email is not sent (the check in `RegisteredUserController` is bypassed)
- Email verification is not triggered
- Any listeners hooked to the `Registered` event are silently skipped

This should match the behavior of password registration.

#### H4 — Order merge not wrapped in a transaction
**File:** `app/Http/Controllers/OrderMergeController.php:21-37`

```php
$source->items()->update(['order_id' => $order->id]);  // Step 1
$source->update([...]);                                  // Step 2
$order->timeline()->create([...]);                       // Step 3
$source->timeline()->create([...]);                      // Step 4
```

If Step 2 fails after Step 1 succeeds, the source order's items have been moved to the target but the source is not marked as merged. The data is now in an inconsistent state. Wrap the entire merge operation in `DB::transaction()`.

#### H5 — Old order files are orphaned during order edit
**File:** `app/Livewire/NewOrder.php:839` in `submitOrderEdit()`

```php
$order->items()->delete();  // Hard deletes all items
```

When an order is edited and resubmitted, all existing `order_items` are deleted. However, `order_files` that reference those items via `order_item_id` remain in the database and on disk — they are orphaned. The file cleanup service may eventually catch some of these, but there's no explicit cleanup during the edit. At minimum, `order_files` associated with the deleted items should be deleted (or soft-deleted) along with the items. The images directory `orders/{id}/` will accumulate stale files over time.

#### H6 — `insertSystemComment` and `Activity::create` are outside the transaction
**File:** `app/Livewire/NewOrder.php:737-771`

```php
DB::transaction(function () use (..., &$createdOrder) {
    // Order and items created here
    $createdOrder = $order;
});

if ($createdOrder) {
    $this->insertSystemComment($createdOrder);  // Outside transaction
    Activity::create([...]);                     // Outside transaction
    UserActivityLog::fromRequest(...);           // Outside transaction
}
```

If `insertSystemComment()` throws after the transaction commits, the order exists without a system comment. More critically, if any of these outside-transaction calls fail, there is no rollback of the already-committed order. The system comment and activity log should be inside the transaction. If the system comment template query is a concern, pre-load it before the transaction.

#### H7 — CSV export headers hardcoded in English
**File:** `app/Http/Controllers/OrderController.php` (exportCsv/exportExcel method)
**Rule violation:** Bilingual rule — never ship strings without `__()`

The Excel/CSV export uses hardcoded English column headers. Bilingual staff who have the site set to Arabic will see English column headers. Wrap all headers in `__()`.

#### H8 — Invoice PDF generation is synchronous
**File:** `app/Http/Controllers/OrderController.php:224` — comment in code acknowledges this

The code itself notes: *"PDF is built synchronously. For heavy invoices... consider queueing."* An order with 30+ items in "both" language invoice mode can take 5–10 seconds. nginx's `fastcgi_read_timeout 120s` provides headroom but the user request is blocked. This should be queued — dispatch a job, return a "generating" response, and notify staff when ready (via timeline/comment).

---

### Performance

#### H9 — AccountController runs 4 separate COUNT queries for order stats
**File:** `app/Http/Controllers/AccountController.php:44-55`

```php
$orderStats = [
    'total'     => Order::where('user_id', $user->id)->count(),
    'active'    => Order::where('user_id', $user->id)->whereNotIn(...)->count(),
    'shipped'   => Order::where('user_id', $user->id)->where('status', 'shipped')->count(),
    'cancelled' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
];
```

Four queries where one `GROUP BY status` aggregate query would suffice. On the account page, this runs for every logged-in customer.

---

## MEDIUM — Address before or shortly after launch

### Security

#### M1 — LIKE wildcard injection in order search
**File:** `OrderController` (index/staff/export methods)
**Risk:** Low severity; % and _ wildcards broaden matches unintentionally

`$query->where('order_number', 'like', "%{$search}%")` — user can enter `%` to match all orders (though authorization limits what they can see). Escape `%` and `_` in `$search` before the LIKE clause.

#### M2 — Error message exposes exception details to users
**File:** `app/Http/Controllers/OrderController.php:287`

```php
->with('error', __('orders.invoice_generation_failed').': '.$e->getMessage());
```

`$e->getMessage()` may contain internal paths, library details, or stack traces. Log the exception internally and show only a generic user-facing message.

#### M3 — `SESSION_SECURE_COOKIE` not set in production `.env.example`
**File:** `deploy/.env.production.example`

The production config sets `SESSION_ENCRYPT=true` but does not set `SESSION_SECURE_COOKIE=true`. On an HTTPS-only site (which this is via nginx), session cookies should have the `Secure` flag. Add `SESSION_SECURE_COOKIE=true` to the production env template.

#### M4 — REDIS_PASSWORD literal "null" in env
**File:** `.env:49`, `deploy/.env.production.example:39`

`REDIS_PASSWORD=null` — the string `"null"` is not the same as an empty value. If Redis has no password, use `REDIS_PASSWORD=` (empty). The string "null" may cause connection errors with some Redis clients.

---

### Architecture

#### M5 — `OrderPolicy::update` allows customer owner unrestricted update access
**File:** `app/Policies/OrderPolicy.php:43-53`

```php
if ($order->user_id === $user->id) {
    return true;  // Customer can "update" their own order
}
```

This broad grant means any route that calls `$this->authorize('update', $order)` passes for the order owner. Currently `updateShippingAddress` uses this policy check — confirm no other update endpoints are inadvertently opened to customers. Tighten the policy or document what customer-owned "update" is intended to cover.

#### M6 — Order show page: authorization after eager loading
**File:** `app/Http/Controllers/OrderController.php:43-55`

```php
$order->loadMissing([...]); // Loads all relationships
...
$this->authorize('view', $order); // Authorization check after
```

Relationships are eager-loaded before authorization is checked. If the user is unauthorized, the extra DB queries are wasted. Move `$this->authorize('view', $order)` to the first line of `show()`.

#### M7 — `APP_FALLBACK_LOCALE=ar` in production env template
**File:** `deploy/.env.production.example:8`

If a translation key is missing from `ar.json`, Laravel falls back to... Arabic — which doesn't help. The fallback locale should be `en` so English is shown as a fallback instead of a missing key error. Change to `APP_FALLBACK_LOCALE=en`.

#### M8 — `RoleBasedThrottle` returns JSON 429 for a browser page (GET)
**File:** `app/Http/Middleware/RoleBasedThrottle.php:36-42`

The middleware wraps `/new-order` (a GET route served as HTML). When throttled, it returns:
```php
return response()->json(['message' => __('Too many requests...')], 429);
```
This renders as raw JSON in the browser, not a user-friendly HTML error page. Return an HTML redirect or Blade view for non-JSON requests (`$request->expectsJson()`).

---

### Performance

#### M9 — CommissionCalculator does repeated DB queries without caching
**File:** `app/Services/CommissionCalculator.php`

Every call to `calculate()` or `getSettings()` executes:
- `Setting::where('key', 'commission_below_type')->exists()` — one query
- Up to 5 × `Setting::get()` — five more queries

These are called on every order submission (for each new order), on every order show page, and in invoice generation. Once `Setting::get()` is fixed to use cache (C3), this resolves automatically.

#### M10 — `format_datetime_for_display()` calls `Setting::get()` twice per invocation
**File:** `app/Support/helpers.php:149,153`

If called once per order in a list of 50 orders and once per comment — easily 100+ calls per page. Resolves with C3 fix.

#### M11 — Order rate limiting in `submitOrder()` uses DB COUNT, not Redis
**File:** `app/Livewire/NewOrder.php:554-606`

The per-hour, per-day, and per-month rate limits in `submitOrder()` each run a full `COUNT(*)` on the orders table:
```php
$hourlyCount = Order::where('user_id', $user->id)
    ->where('created_at', '>=', now()->subHour())
    ->count();
```
Three queries per submission. The `RoleBasedThrottle` middleware handles the route-level Redis rate limit, but these DB-backed limits inside the Livewire component are redundant and slow. Consider using Redis counters or removing the Livewire-level limits in favor of the middleware.

---

### UX / Business Logic

#### M12 — `robots.txt` disallows all crawlers
**File:** `public/robots.txt`

```
User-agent: *
Disallow: /
```

The entire site is blocked from indexing. This may be intentional during the parallel-run phase, but it must be updated to allow the blog, static pages, and homepage before DNS cutover, or organic search traffic from the legacy site will be lost. Define what should and should not be indexed.

#### M13 — Account email change bypasses verification silently
**File:** `app/Http/Controllers/AccountController.php:90-98`

When a user changes their email in the profile form, `email_verified_at` is cleared but no verification email is sent and there is no UI indication that the new email requires verification. The user may not realize their email is now unverified. The dedicated email change flow (via `requestEmailChange`/`verifyEmailChange`) exists but isn't linked from the profile update path — the profile update path allows direct email change without the OTP step.

---

### Bilingual

#### M14 — Timeline body stored in the locale active at creation time
**File:** `app/Http/Controllers/OrderCommentController.php:57-58` and throughout controllers

Timeline entries are created with `__()`:
```php
'body' => __('orders.timeline_comment_added'),
```
This stores the Arabic (or English) translation at insertion time. If the timeline was created when locale was 'ar', the body is Arabic. Staff with English locale will see Arabic in the timeline. This is a known design trade-off, but verify it's intentional and that the `order_timeline.body` column is treated as immutable display text, not re-translated.

---

## LOW — Schedule post-launch

### Repository Hygiene

#### L1 — `debugbar` session files committed to repository
**File:** `storage/debugbar/` — 100+ JSON files in the repo

These are Debugbar profiling artifacts from local development. They should not be in git. Add `storage/debugbar/` to `.gitignore`.

#### L2 — SQLite test databases in repository
**Files:** `database/database.sqlite`, `database/testing.sqlite`

Empty or test SQLite databases committed to git. Add to `.gitignore`.

#### L3 — Dev test file publicly accessible
**File:** `public/cache-demo.html`

A dev test page is in `public/` and therefore publicly accessible at `/cache-demo.html`. Remove before deploy.

#### L4 — `routes_output.txt` in project root
**File:** `routes_output.txt`

A debug artifact in the root. Add to `.gitignore` or delete.

#### L5 — `.DS_Store` files in repository
macOS filesystem metadata files are committed in several directories. Add `**/.DS_Store` to `.gitignore`.

---

### Deployment

#### L6 — nginx config references `php8.3-fpm.sock` but production should use PHP 8.4
**File:** `deploy/nginx.conf:115`

```nginx
fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
```
Local development is on PHP 8.4.18. The deploy script uses `php8.3`. The nginx config must match the actual PHP-FPM version on the production server. Confirm and align.

#### L7 — Deploy script does not seed `CurrencySeeder` on first deploy
**File:** `deploy/deploy.sh:58-61`

The `--fresh` flag seeds `RoleAndPermissionSeeder` and `SettingsSeeder` but not `CurrencySeeder`, `ShippingCompanySeeder`, or `TestimonialSeeder`. Without currencies, the order form currency dropdown is empty. Add all required seeders to the first-deploy sequence.

#### L8 — No Content-Security-Policy header in nginx config
**File:** `deploy/nginx.conf`

Security headers include `X-Frame-Options`, `X-Content-Type-Options`, `X-XSS-Protection`, and `Referrer-Policy` — but no `Content-Security-Policy`. CSP is the primary defense against XSS. A strict policy should be added, even if permissive at first, to protect users and flag violations.

#### L9 — Supervisor config uses `php` not `php8.3`
**File:** `deploy/supervisor.conf:11,29`

```
command=php /var/www/wasetzon/artisan queue:work redis ...
command=php /var/www/wasetzon/artisan schedule:work
```
If the server has multiple PHP versions installed, `php` may not resolve to the correct version. Use the explicit binary path (`php8.3` or `php8.4`) to match the application.

---

### UX / Accessibility

#### L10 — Dev toolbar blocks submit button on new-order form (local dev only)
**File:** `resources/views/components/dev-toolbar.blade.php`
**Previously identified:** H3 in prior review

Dev toolbar `z-[9999]` overlays the order summary card. Reposition or lower z-index when the new-order form is active.

#### L11 — Touch targets below 44px minimum
- **"Add product" button** in `new-order.blade.php` — below 44×44px minimum
- **File remove (×) button** in `_order-item-card-option3.blade.php` — `w-4 h-4` (16px)

Add `min-h-[44px] min-w-[44px]` and use padding to expand the hit area.

#### L12 — Missing `aria-label` on desktop remove button
**File:** `_order-item-card-option3.blade.php` — mobile has `aria-label`, desktop does not.

#### L13 — Toast container missing safe-area inset for notched devices
**File:** `resources/css/app.css` — `#toast-container` does not account for notched screens. Add `padding-top: env(safe-area-inset-top)`.

#### L14 — Alpine `:key="idx"` is unstable for list reorders
**File:** `new-order.blade.php` — Using array index as key causes incorrect DOM reuse if items are reordered. Use a stable `_id` field (e.g. UUID) generated on item creation.

---

### Business Logic

#### L15 — Auto-comment uses hardcoded page URL slugs
**File:** `app/Livewire/NewOrder.php:944-946`

```php
'payment_url' => $baseUrl.'/payment-methods',
'terms_url'   => $baseUrl.'/terms-and-conditions',
'faq_url'     => $baseUrl.'/faq',
'shipping_url' => $baseUrl.'/shipping-calculator',
```

These URLs are hardcoded. If the page slugs differ on the production site (e.g. `/payment` instead of `/payment-methods`), the auto-comment has broken links. Make these configurable in Settings.

#### L16 — Order edit race condition (minor)
**File:** `app/Livewire/NewOrder.php:132-186`

`prefillFromEdit()` sets `can_edit_until` on the order. Two concurrent edit submissions from the same browser tab (double-click on submit) could both pass the window check and both succeed, creating two updated versions of the order sequentially. Mitigate with a unique constraint or optimistic locking check.

#### L17 — `sendNotification` checks `email_comment_notification` not a dedicated per-type toggle
**File:** `app/Http/Controllers/OrderCommentController.php:205`

Both checks reference "email disabled" but use different keys. Verify the second check correctly uses the comment-notification toggle and returns a distinct error message.

#### L18 — PWA manifest icons use combined `"purpose": "any maskable"`
**File:** `public/manifest.json`

Google recommends separate `any` and `maskable` entries for each icon size. Combined purpose declarations are valid per spec but may cause display issues on some Android launchers. Best practice is to provide separate icon entries.

---

## What Is Working Well

These are strengths to preserve and not accidentally break:

| Area | Details |
|------|---------|
| **Authorization** | `OrderPolicy`, `OrderCommentPolicy`, and `@can` gates are consistent. Staff-only actions properly check granular permissions. Filament access requires `access-filament`. |
| **XSS prevention** | `comment_body_safe()` uses `e()` before linkify and nl2br. All Blade output uses `{{ }}` correctly. |
| **Form validation** | Form Request classes used throughout. Order validation is comprehensive. |
| **Bilingual** | ~2700 lines of translation keys. `__()` wrapper used consistently in controllers and views. |
| **Rate limiting** | `RoleBasedThrottle` for new-order; `throttle:5,15` for comments/contact; `throttle:10,1` for order comments. |
| **Transactions** | Order creation is correctly wrapped in `DB::transaction()`. |
| **Eager loading** | Order show page loads all relationships upfront. N+1 problems are avoided on the critical paths. |
| **File security** | `safe_item_url()` rejects `javascript:`, `data:`, `vbscript:`. Files stored via Laravel `Storage` facade, not in `public/` directly. |
| **WordPress migration** | `WpCompatUserProvider` transparently handles phpass hash verification and upgrades on first login. |
| **Dev route protection** | `/_dev/login-as` correctly gated by `app()->environment('local')` + `EnsureAppIsLocal` middleware. |
| **Config safety** | `env()` only used in config files; application code uses `config()`. |
| **Ban enforcement** | `EnsureUserNotBanned` middleware properly invalidates session and redirects. |
| **Service worker** | PWA correctly skips Livewire requests (`/livewire/`) and admin routes from service worker interception. |
| **Legacy redirects** | Both nginx-level (301) and Laravel-level (`/order/{id}` → `/orders/{id}`) legacy URL redirect are implemented. |
| **Deployment** | `deploy.sh` follows the correct sequence: maintenance mode → pull → composer → npm → cache → migrate → queue:restart → up. |
| **Scheduler** | Exchange rate fetch and order automation both registered in `routes/console.php`. Supervisor config handles both workers and scheduler. |

---

## Prioritized Action Plan

### P0 — Before any production traffic
| ID | Action |
|----|--------|
| C1 | Remove or gate all dev/demo/test/prototype routes with `app()->environment('local')` |
| C2 | Raise password minimum to 8 characters minimum |
| C3 | Add Redis/file caching to `Setting::get()` — this is the single biggest performance issue |
| C4 | Add duplicate button to order show page |

### P1 — Before DNS cutover
| ID | Action |
|----|--------|
| H1 | Validate page slug before use in view path |
| H2 | Handle null email in social auth flow |
| H3 | Fire `Registered` event in social auth |
| H4 | Wrap order merge in `DB::transaction()` |
| H5 | Delete orphaned `order_files` during order edit resubmit |
| H6 | Move `insertSystemComment` and `Activity::create` inside the order creation transaction |
| H7 | Translate CSV/Excel export headers |
| L6 | Align nginx PHP-FPM sock version with production PHP version |
| L7 | Add all required seeders to first-deploy sequence |
| M3 | Set `SESSION_SECURE_COOKIE=true` in production env template |
| M7 | Change `APP_FALLBACK_LOCALE` to `en` |
| M12 | Update `robots.txt` to allow appropriate public pages before launch |

### P2 — First sprint post-launch
| ID | Action |
|----|--------|
| H8 | Queue invoice PDF generation |
| M1 | Escape LIKE wildcards in order search |
| M2 | Remove `$e->getMessage()` from user-facing error |
| M4 | Fix `REDIS_PASSWORD=null` to empty |
| M8 | Return HTML (not JSON) 429 from RoleBasedThrottle for browser requests |
| M9 | Composite index on `orders (user_id, created_at)` |
| M13 | Link email change flow or add verification prompt after profile email change |
| L1-L5 | Repository hygiene (.gitignore, remove debug artifacts) |
| L8 | Add Content-Security-Policy header to nginx config |

### P3 — Ongoing
| ID | Action |
|----|--------|
| H9 | Consolidate account order stats to single aggregate query |
| M11 | Replace DB-backed rate limits in Livewire with Redis counters |
| L10-L14 | Accessibility pass: touch targets, aria-labels, safe-area insets, Alpine keys |
| L15-L18 | Business logic refinements |

---

## Files Reviewed

**Routes & Middleware:** `routes/web.php`, `routes/auth.php`, `routes/console.php`, `bootstrap/app.php`

**Controllers:** `OrderController` (full, ~800 lines), `OrderCommentController`, `OrderStatusController`, `OrderMergeController`, `AccountController`, `PageController`, `BlogController`, `ContactController`, `DevController`, `ActivityFileController`, `Auth/RegisteredUserController`, `Auth/SocialAuthController`, `Auth/AuthenticatedSessionController`

**Livewire:** `NewOrder.php` (full, ~1000+ lines)

**Models:** `Order`, `User`, `Setting`, `OrderItem`, `OrderComment`, `OrderCommentRead`, `OrderFile`

**Policies:** `OrderPolicy`, `OrderCommentPolicy`

**Middleware:** `SetLocale`, `EnsureUserNotBanned`, `RoleBasedThrottle`, `EnsureAppIsLocal`

**Services:** `CommissionCalculator`, `ImageConversionService`, `WpCompatUserProvider`

**Migrations:** `create_orders_table`, `create_order_items_table`, + 40 additional migrations reviewed

**Config:** `auth.php`, `session.php`, `filesystems.php`, `queue.php`, `cache.php`

**Environment:** `.env` (local), `deploy/.env.production.example`

**Deploy:** `deploy/deploy.sh`, `deploy/nginx.conf`, `deploy/supervisor.conf`

**Assets:** `public/sw.js`, `public/manifest.json`, `public/robots.txt`

**Support:** `app/Support/helpers.php`, `lang/ar.json` + `lang/en.json` (2713 keys each)

**Seeders:** `RoleAndPermissionSeeder`, `SettingsSeeder`

**Existing reviews cross-referenced:** `docs/PRE_DEPLOYMENT_REVIEW.md`, `docs/NEW_ORDER_PAGE_ISSUES_REPORT.md`
