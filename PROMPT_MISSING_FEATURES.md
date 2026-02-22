# Missing Features — Implementation Prompt

**Context:** Wasetzon Laravel (`wasetzonlaraval/`) is the build target. WordPress (`Wordpress/pwa3/app/public/`) is the feature reference. Read `LARAVEL_PLAN.md` and `.cursor/rules/wasetzon.mdc` before starting.

**Task:** Implement the missing features below. Work in small, atomic commits. After each feature, update `LARAVEL_PLAN.md` → Current Task section.

**When finished:** Reply with exactly: `Done.` Then stop.

---

## 1. New Order Form

- **Hourly rate limiting:** Add `orders_per_hour_customer` and `orders_per_hour_admin` settings. Enforce in `NewOrder::submitOrder()` or route middleware. WP: 10/hr user, 50/hr admin.
- **Duplicate order:** Support `?duplicate_from={id}`. In `NewOrder::mount()`, load order products + notes and pre-fill form.
- **Product URL pre-fill:** Support `?product_url=...` from homepage. Pre-fill first item URL in `NewOrder` init (Alpine or Livewire).
- **Success screen:** After order creation, show full success screen for first 3 orders; for 4+ use toast + redirect (like WP).
- **Order creation email:** Send confirmation email (subtotal, commission, total, payment info) after order creation. Use queued job.
- **Customer metadata:** Capture IP, device type, browser, OS, GeoIP on order creation. Store in `orders` or `user_activity_logs`.
- **Ad campaign tracking:** If user has `myad` (ad campaign), increment its order count on new order.
- **JSON input for app:** Accept `application/json` POST body and map to form fields for API clients.
- **Password reset in modal:** Wire the reset step in the login modal to actual password reset flow (Laravel `password.request` or API).

---

## 2. Order Detail / Show

- **Admin customer notes:** Add staff-only field to store notes about the customer (like WP `admin_customer_notes`). Show in staff panel.
- **Comment read tracking:** Track when comment notifications were sent (email/WhatsApp) and whether read. Store in `comment_meta` or new table.
- **Send comment via WhatsApp:** Add option to send comment notification via WhatsApp (in addition to email).
- **Customer device/IP metadata panel:** Staff-only panel showing IP, device, browser, OS, GeoIP for the order.
- **Duplicate order button:** Add "Duplicate" button on order show → links to `/new-order?duplicate_from={id}`.
- **Edit products flow:** Ensure `?edit={id}` on new-order loads order items for editing within the edit window. Verify and fix if broken.

---

## 3. Orders List

- **Per-page "all" option:** Add option to show all orders (e.g. 100 for customer, 500 for staff) like WP.
- **Admin view toggle:** On staff orders page, add toggle to view list as "customer" (own orders only) vs "all".
- **AJAX load (optional):** Consider AJAX pagination for smoother UX (lower priority).

---

## 4. Account / Profile

- **Order stats on account:** Show total, active, shipped, cancelled counts on account page (like WP).
- **Quick actions on account:** Add "New Order" and "My Orders" buttons on account page.
- **Unsubscribe all:** One-click to turn off all notifications (orders, promotions, WhatsApp).
- **Profile changes → last order comment:** When user updates profile, add a comment to their last order (like WP).
- **Change email with verification code:** Full flow: request new email → send 6-digit code → verify and update (WP has this; Laravel may use standard verification).

---

## Priority Order (suggested)

1. Hourly rate limiting  
2. Duplicate order + product URL pre-fill  
3. Order creation email  
4. Success screen  
5. Duplicate order button on show page  
6. Unsubscribe all  
7. Admin customer notes  
8. Order stats + quick actions on account  
9. Rest as needed  

---

**Handover for next chat:** Paste this to continue:

```
Continue from PROMPT_MISSING_FEATURES.md. Read LARAVEL_PLAN.md first. Implement the next unchecked item. Update LARAVEL_PLAN.md Current Task when done. Reply "Done." when all items are complete.
```
