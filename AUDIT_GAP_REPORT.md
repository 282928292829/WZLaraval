# Wasetzon Audit: WordPress (Reference) vs Laravel (Target)

**Reference:** `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/app/public/` (wasetzon-modern theme)  
**Target:** `/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/`

---

## 1. New Order Form

**WordPress:** `wp-content/themes/wasetzon-modern/template-order-new.php`  
**Laravel:** `app/Livewire/NewOrder.php` + `resources/views/livewire/new-order.blade.php`

| Feature | WordPress has it | Laravel has it | Notes |
|--------|-------------------|----------------|-------|
| Guest can view form | ✓ | ✓ | Both allow viewing; login required on submit |
| Login modal on submit (email → login/register) | ✓ | ✓ | WP: AJAX check_email_exists, then login/register; Laravel: checkModalEmail → login/register |
| Duplicate order pre-fill (`?duplicate_from=id`) | ✓ | ✓ | Both prefill products + notes for owner/staff |
| Product URL from query (`?product_url=`) | ✓ | ✓ | Both prefill first item URL |
| Multiple products (url, qty, color, size, price, currency, notes) | ✓ | ✓ | Same fields |
| Optional per-item file upload (1 per item, max 10/order) | ✓ | ✓ | WP: product_file_N; Laravel: itemFiles indexed, Livewire WithFileUploads |
| General order notes | ✓ | ✓ | order_notes / orderNotes |
| Exchange rates + SAR total in footer | ✓ | ✓ | WP: wasetzon_get_exchange_rates(), 3% margin; Laravel: buildExchangeRates(), 0.03 margin |
| Commission calc (threshold/above % / below flat) | ✓ | ✓ | WP: wasetzon_commission; Laravel: commissionThreshold/Pct/Flat from Setting |
| Max products per order (configurable) | ✓ | ✓ | WP: wasetzon_order_limits max_products; Laravel: Setting max_products_per_order |
| Rate limit (hourly: 10 user / 50 admin) | ✓ | ✓ | Laravel also has daily limit for customers (max_orders_per_day) |
| Daily rate limit (200 admin) | ✓ | ✓ | WP: 200/day admin; Laravel: staff no daily cap in code (only hourly) |
| CSRF / nonce | ✓ | ✓ | wp_nonce_field / Laravel CSRF |
| Auto default shipping address from profile | ✓ | ✓ | Both attach default_address_id + snapshot |
| Success screen (first 3 orders) vs toast + redirect (4+) | ✓ | ✓ | Both use order count; WP localStorage wz_order_count |
| 45s countdown then redirect to order | ✓ | ✓ | WP inline; Laravel Alpine countdown |
| Draft persistence (localStorage) | ✓ | ✓ | WP: wz_order_draft, wz_order_general_notes; Laravel: wz_opus46_draft, wz_opus46_notes |
| Tips box + "don't show for 30 days" | ✓ | ✓ | WP: wz_hide_tips_until; Laravel: wz_opus46_tips_until |
| Reset all products | ✓ | ✓ | Both with confirm |
| JSON API input (php://input) for app | ✓ | ✗ | WP lines 30–35: accepts JSON body for app compatibility |
| Mobile/device metadata (IP, UA, device type, OS, browser, GeoIP) | ✓ | ✗ | WP: extensive device detection + ip-api.com + scheduled wz_update_order_geo_data |
| Ad campaign order count increment (myad) | ✓ | ✓ | WP: user meta myad → update post meta orders; Laravel: user ad_campaign_id → AdCampaign::increment order_count |
| Order confirmation email (scheduled/queued) | ✓ | Partial | WP: wp_schedule_single_event wz_send_order_email; Laravel: no auto email on create; staff can send via "Send confirmation email" on show page |
| System comment with price breakdown on create | ✓ | ✓ | WP: wp_insert_comment content1; Laravel: insertSystemComment with auto_comment_with_price / auto_comment_no_price |
| Register modal requires name | ✗ | ✓ | WP: register form only email + password; Laravel: modalName required in registerFromModal |
| Register modal phone optional | ✗ | ✓ | Laravel has modalPhone in register form |
| Password min length (6 vs 4) | 6 | 4 | WP minlength="6"; Laravel validation min:4 in registerFromModal |

### Top 3 missing features (New Order) by business impact

1. **Order confirmation email on create** — WordPress sends a queued email after order creation with breakdown and next steps. Laravel does not send automatically; staff must use "Send confirmation email" from the order page. **Impact:** Customers may not get immediate confirmation and instructions.
2. **Device/geo metadata on order** — WordPress stores IP, user agent, device type/model, OS, browser, and schedules GeoIP update. Laravel does not persist this. **Impact:** Less context for support and fraud/abuse handling.
3. **JSON API for app** — WordPress accepts `php://input` JSON for app clients. Laravel new-order is form/Livewire only. **Impact:** Any future native app would need a dedicated API endpoint.

---

## 2. Order Detail / Show

**WordPress:** `wp-content/themes/wasetzon-modern/single-orders.php`  
**Laravel:** `resources/views/orders/show.blade.php` + `OrderController` (show, comment, status, etc.)

| Feature | WordPress has it | Laravel has it | Notes |
|--------|-------------------|----------------|-------|
| Auth required, owner or admin | ✓ | ✓ | Both redirect unauthenticated; enforce owner or staff |
| Order number, date, status, customer (staff) | ✓ | ✓ | Same |
| Status labels + colors | ✓ | ✓ | WP: 0–7 numeric; Laravel: string status + statusLabel() |
| Payment badge (paid/unpaid) | ✓ | ✓ | payment_amount / is_paid |
| Products table (url, qty, color, size, price, currency, notes) | ✓ | ✓ | WP: JSON + legacy meta; Laravel: order_items |
| Per-item images (creation + post-submit) | ✓ | ✓ | WP: p_img_N + product_X_images; Laravel: OrderItem image_path + OrderFile |
| Shipping address block + snapshot | ✓ | ✓ | Both show snapshot; customer can add/change when status allows |
| Add address on order page (owner/admin) | ✓ | ✓ | WP: add_address_on_order; Laravel: modal to account.addresses.store with _order_id + _redirect_back |
| Select existing address (dropdown) | ✓ | ✓ | WP: select_shipping_address; Laravel: orders.shipping-address.update PATCH |
| Payment tracking (amount, date, method, receipt file) | ✓ | ✓ | WP: update_payment + payment_receipt upload; Laravel: payment amount/date/method + receipt in staff panel |
| Tracking number + company + carrier link | ✓ | ✓ | WP: getTrackingLink(); Laravel: carrier URL from Setting |
| Status update (admin) + email notification | ✓ | ✓ | WP: send_order_notification status_changed; Laravel: status update + optional notification |
| Comments section (customer + staff) | ✓ | ✓ | Both |
| Internal/private notes (staff only) | ✓ | ✓ | WP: comment_type internal_note; Laravel: is_internal |
| Comment file upload (multiple, 10MB, 5 files) | ✓ | ✓ | WP: comment_file; Laravel: comment attachments |
| Send comment via email (staff) | ✓ | Partial | WP: send_comment_action=email, wp_mail, send_history in comment meta; Laravel: has send-comment-notification permission and UI to "Send confirmation email" for order, not per-comment email |
| Send comment via WhatsApp (staff log) | ✓ | ✗ | WP: send_comment_action=whatsapp, logs in comment meta; Laravel: no per-comment WhatsApp log |
| Activity log (admin) | ✓ | ✓ | WP: activity_log post meta; Laravel: OrderTimeline + activity in comments |
| Add activity entry as comment (expose to customer) | ✓ | ✗ | WP: add_activity_as_comment posts activity text as order_note comment |
| Invoice generation (admin): text block as comment | ✓ | Partial | WP: generate_invoice → invoice comment type + status 0→1; Laravel: PDF invoice via permission, different flow |
| Customer payment notification (form: amount, bank, notes) | ✓ | ✓ | WP: notify_payment_transfer; Laravel: payment notify modal → comment |
| Customer merge request (dropdown of own orders) | ✓ | ✓ | WP: request_merge; Laravel: customer merge flow |
| Admin merge execution (two orders → new merged order) | ✓ | ✓ | Both create new order and mark originals merged |
| Order transfer to another user (admin) | ✓ | ✓ | WP: transfer_order, create user if new; Laravel: transfer order flow |
| New user credentials email (admin after transfer) | ✓ | ✗ | WP: send_new_user_creds sends temp password to email; Laravel: transfer can create user but no dedicated "send credentials email" |
| Customer cancel order (status 0 or 1 only) | ✓ | ✓ | WP: can_cancel; Laravel: isCancellable() |
| Inline product edit (admin always; customer when status 0) | ✓ | Partial | WP: update_product form per product; Laravel: customer "Edit items" links to new-order?edit=id (edit window); staff has edit prices (unit_price, commission, shipping, final_price) |
| Staff notes (admin-only text area) | ✓ | ✓ | WP: admin_customer_notes user meta; Laravel: order.staff_notes |
| Estimated delivery (e.g. +14 days) | ✓ | ✗ | WP: estimated_delivery from order_date + 14 days; Laravel: not shown |
| Merged order notice + link to merged order | ✓ | ✓ | Both |
| Lightbox for images | ✓ | ✓ | Laravel: orderLightboxImages + open-lightbox |
| Copy order number | ✓ | ✓ | Both |
| Success/error flash after actions | ✓ | ✓ | Both |

### Top 3 missing features (Order Detail) by business impact

1. **Send single comment to customer by email** — WordPress lets staff send a specific comment to the customer via email and logs it in comment meta. Laravel has order-level "Send confirmation email" but not "send this comment to customer." **Impact:** Staff cannot resend or highlight one reply by email.
2. **New user credentials email after transfer** — When admin transfers an order to a new email, WordPress can send a "credentials" email with temp password and login link. Laravel creates the user but does not send this email. **Impact:** New users may not know how to log in after transfer.
3. **Estimated delivery date** — WordPress shows estimated delivery (order date + 14 days). Laravel does not. **Impact:** Customers have less expectation setting on the order page.

---

## 3. Orders List / Dashboard

**WordPress:** `wp-content/themes/wasetzon-modern/template-list.php` (dashboard_url() → site_url('orders')); archive-orders.php redirects to it.  
**Laravel:** `resources/views/orders/index.blade.php` (customer), `resources/views/orders/staff.blade.php` (staff)

| Feature | WordPress has it | Laravel has it | Notes |
|--------|-------------------|----------------|-------|
| Login required | ✓ | ✓ | Both |
| Customer: list own orders | ✓ | ✓ | index: Order::forUser |
| Staff: list all orders | ✓ | ✓ | staff: separate route + view |
| Admin view toggle (my orders vs all) on same URL | ✓ | ✗ | WP: single page, view_mode admin/customer in JS, localStorage; Laravel: separate routes (index vs staff) |
| Search by order number | ✓ | ✓ | Both |
| Filter by status | ✓ | ✓ | Both |
| Date range filter (from/to) | ✓ | ✓ | WP: date_from, date_to; Laravel: from, to in staff |
| Sort (newest/oldest) | ✓ | ✓ | Both |
| Per-page (10, 25, 50, 100, all) | ✓ | ✓ | Staff has 25,50,100, all; customer 10,25,50 |
| Pagination | ✓ | ✓ | WP: AJAX loadOrders; Laravel: paginate |
| Row click → order detail | ✓ | ✓ | Both |
| Customer name column (staff view) | ✓ | ✓ | Staff table has customer |
| Status badge with color | ✓ | ✓ | Both |
| Uploaded images count badge on row | ✓ | ✗ | WP: order.uploaded_images in row; Laravel: not in list |
| Bulk select (checkboxes) | ✓ | ✓ | Staff: Alpine selected[] |
| Bulk change status | ✓ | ✓ | WP: bulk_action status; Laravel: bulk-update |
| Bulk merge (multiple orders → one) | ✓ | ✗ | WP: bulk_action merge, wz_bulk_action; Laravel: no bulk merge from list |
| Bulk delete (with confirm + type "حذف") | ✓ | ✗ | WP: bulk_action delete, double confirm; Laravel: no bulk delete |
| Loading overlay during AJAX | ✓ | N/A | Laravel server-side render, no overlay |
| Empty state | ✓ | ✓ | Both |
| Sticky header hide on scroll down | ✓ | ✗ | WP: header hidden on scroll |
| Filters collapsible (default collapsed) | ✓ | ✓ | Both |

### Top 3 missing features (Orders List) by business impact

1. **Bulk merge from list** — WordPress lets staff select multiple orders and merge in one action from the list. Laravel has merge only from the order detail page. **Impact:** Staff workflow is slower when merging many orders.
2. **Bulk delete** — WordPress allows bulk permanent delete with strong confirmation. Laravel has no bulk delete. **Impact:** Cleanup of test or duplicate orders is harder.
3. **Single-page admin/customer toggle** — WordPress uses one URL and toggles view mode (admin vs my orders) with localStorage. Laravel uses separate routes. **Impact:** Minor UX difference; staff must know to go to staff list.

---

## 4. User Account / Profile

**WordPress:** `wp-content/themes/wasetzon-modern/template-profile.php` (used for /account/)  
**Laravel:** `resources/views/account/index.blade.php` + `AccountController`

| Feature | WordPress has it | Laravel has it | Notes |
|--------|-------------------|----------------|-------|
| Login required | ✓ | ✓ | Both |
| Profile: name, email, phone | ✓ | ✓ | Both |
| Secondary phone | ✓ | ✓ | WP: phone_secondary meta; Laravel: phone_secondary |
| Update profile (with validation) | ✓ | ✓ | Both |
| Write last-order comment on profile change | ✓ | ✓ | WP: last_order_id comment; Laravel: latest active order system comment |
| Password change | ✓ | ✓ | Both (Laravel: current_password required) |
| Email change (with verification) | ✓ | ✓ | WP: edit_user; Laravel: 2-step email change (code) |
| Email verification status / resend | Partial | ✓ | Laravel: email_verified_at + verification.send; WP: email_verified meta, no built-in flow in template |
| Saved addresses (CRUD) | ✓ | ✓ | WP: saved_addresses user meta; Laravel: UserAddress model |
| Default address | ✓ | ✓ | Both |
| Cannot delete only default address | ✓ | ✓ | WP: redirect cannot_delete_only_default; Laravel: set first as default on delete |
| Notification prefs: orders, promotions, WhatsApp | ✓ | ✓ | notify_orders, notify_promotions, notify_whatsapp |
| Unsubscribe all | ✓ | ✗ | WP: unsubscribe_all sets unsubscribed_all + zeros; Laravel: no single "unsubscribe all" |
| Order stats (total, active, shipped, cancelled) | ✓ | ✓ | Both |
| Quick links (new order, my orders) | ✓ | ✓ | Both |
| Delete account request (flag for admin) | ✓ | ✓ | WP: deletion_requested meta; Laravel: deletion_requested |
| Comment on last order when delete requested | ✓ | ✓ | Both post to latest order |
| Cancel deletion request | ✗ | ✓ | Laravel: cancelDeletion |
| Activity log (profile/address/notification events) | ✓ | ✓ | WP: wz_log_customer_activity; Laravel: UserActivityLog + activity tab |
| Balance tab (credit/debit, totals) | ✗ | ✓ | Laravel: UserBalance, balance tab; WP: none |
| Tabs: Profile, Addresses, Notifications, Activity, Balance | Partial | ✓ | WP: single long page with cards; Laravel: tabbed UI |
| Address fields: label, recipient, phone, country, city, district, address | ✓ | ✓ | Laravel also has short_address (national) |
| National/short address hint (WhatsApp, apps) | ✗ | ✓ | Laravel: national_address_tip_* in address form |

### Top 3 missing features (Account) by business impact

1. **Unsubscribe all** — WordPress has one action to turn off orders, promotions, and WhatsApp and set unsubscribed_all. Laravel has no single "unsubscribe from all" control. **Impact:** Users who want to opt out of everything must toggle each off.
2. **Email verification flow in theme** — WordPress template has email_verified meta but no clear resend/verify UI in template-profile. Laravel has full verification flow. **Impact:** WP is weaker here; Laravel is ahead.
3. **Cancel deletion request** — Laravel allows users to cancel their account deletion request; WordPress does not in the template. **Impact:** Laravel reduces accidental permanent requests.

---

## Summary: Cross-cutting gaps

- **Order creation email:** Automatically send order confirmation email on create in Laravel (queue) to match WP.
- **Device/geo metadata:** Optionally add IP, UA, device, and geo to orders for support/fraud.
- **Per-comment "send to customer" email and WhatsApp log** on order detail for staff.
- **Bulk merge and bulk delete** on staff orders list.
- **Unsubscribe all** on account notifications.
- **Credentials email** when order is transferred to a new user (optional, for parity with WP).
