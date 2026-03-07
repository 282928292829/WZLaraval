# Rate Limiting & Throttling

This document describes where rate limiting is applied and how limits are configured.

## RoleBasedThrottle middleware (`role.throttle`)

Applies permission-aware rate limiting using Laravel's `RateLimiter`. Limits are read from the `settings` table; defaults are used when keys are missing.

| Setting Key | Default | Used For |
|-------------|---------|----------|
| `orders_per_hour_admin` | 50 | Staff (users with `view-all-orders`) |
| `orders_per_hour_customer` | 50 | Customers and guests |

**Applied to:**
- `GET /new-order` — Throttles form page loads. Key: `new-order`. Prevents abuse of the order form page. Actual order submission is additionally rate-limited in `NewOrder::submitOrder()` via DB-based hourly/daily/monthly limits.

## Standard Laravel throttle

| Route | Limit | Window |
|-------|-------|--------|
| `POST /blog/{post}/comments` | 5 | 15 min |
| `POST /pages/{page}/comments` | 5 | 15 min |
| `POST /contact` | 5 | 15 min |
| `POST /orders/{order}/comments` | 10 | 1 min |

## In-component rate limiting (NewOrder)

Order creation in `App\Livewire\NewOrder::submitOrder()` enforces:

- **Hourly:** `orders_per_hour_customer` (customers) or `orders_per_hour_admin` (staff)
- **Daily:** `orders_per_day_customer` / `orders_per_day_staff`
- **Monthly:** `orders_per_month_customer` / `orders_per_month_admin`

These are enforced before the order is created and use DB queries. They complement the `role.throttle` on the form page.
