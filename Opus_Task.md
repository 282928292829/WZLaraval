# Opus Task List - WordPress to Laravel Parity

## Group 1: Page Templates (Blade Views)

### Task 1.1: Create Missing Page Templates
**Files:** `resources/views/pages/calculator.blade.php`, `resources/views/pages/shipping-calculator.blade.php`, `resources/views/pages/membership.blade.php`, `resources/views/pages/refund-policy.blade.php`, `resources/views/pages/how-to-order.blade.php`, `resources/views/pages/wasetamazon-to-wasetzon.blade.php`

**Actions:**
- Create `/pages/calculator` - Currency calculator with commission + exchange rate calculation (match WP functionality)
- Create `/pages/shipping-calculator` - Weight-based pricing for Aramex, DHL, US domestic (match WP)
- Create `/pages/membership` - Monthly/yearly membership info page
- Create `/pages/refund-policy` - Refund policy page (WP had `page-refund-policy.php` template)
- Create `/pages/how-to-order` - How to order guide (WP had `template-how-to-order.php` template)
- Create `/pages/wasetamazon-to-wasetzon` - Migration info page (WP had `page-wasetamazon-to-wasetzon.php` template)
- Seed all pages in `database/seeders/PageSeeder.php`

---

### Task 1.2: Fix Payment Methods Page
**File:** `resources/views/pages/payment_methods.blade.php`

**Actions:**
- Add missing Riyad Bank (بنك الرياض) to the banks array
- Verify all 7 banks match WordPress exactly: Al Rajhi, Alinma, SABB, Riyad Bank, Al Bilad, SNB, Saudi Investment Bank

---

### Task 1.3: Add Notification Preferences to Account Page
**File:** `resources/views/account/index.blade.php`

**Actions:**
- Add new tab/section for notification preferences
- Fields: `notify_orders`, `notify_promotions`, `notify_whatsapp`, `unsubscribed_all`
- Add to account controller update methods

---

### Task 1.4: Add Image Lightbox to Order Views
**Files:** `resources/views/orders/show.blade.php`, `resources/views/livewire/partials/order-field.blade.php`

**Actions:**
- Implement lightbox for viewing uploaded images without leaving page
- Use a lightweight library (e.g., GLightbox or similar Alpine.js solution)
- Apply to all product images and order file attachments

---

### Task 1.5: Move Language Toggle to Footer Only
**Files:** `resources/views/layouts/navigation.blade.php`, `resources/views/layouts/app.blade.php`

**Actions:**
- Remove language toggle from header navigation
- Keep language toggle only in footer (`layouts/app.blade.php`)

---

### Task 1.6: Add AJAX Calculation Logic to Calculators
**Files:** `resources/views/pages/calculator.blade.php`, `resources/views/pages/shipping-calculator.blade.php`, create `app/Http/Controllers/CalculatorController.php`

**Actions:**
- Implement AJAX endpoints for currency calculator (commission + exchange rate calculation)
- Implement AJAX endpoints for shipping calculator (weight-based pricing for Aramex, DHL, US domestic)
- **Must be 100% feature-identical to WP's `calculator.php` and its AJAX endpoints** — same fields, same logic, same output format
- All WP routes (e.g. `/?action=calculate_cost`, `/?action=calc_shipping`) must have Laravel equivalents registered in `routes/web.php`
- Use Livewire or Alpine.js + fetch for real-time calculations

---

### Task 1.7: Account Page — Secondary Phone, Account Deletion Button
**File:** `resources/views/account/index.blade.php`

*(Same blade file as Task 1.3 — edit together to avoid duplicate passes)*

**Actions:**
- Add `phone_secondary` field to the profile tab (alongside existing `phone` field)
- Add "Delete Account" button in a danger zone section at the bottom of the account page
- On click, POST to controller which sets `deletion_requested = true` on the user and posts a system comment on the user's most recent active order to alert the team (matches WP behavior)
- Show confirmation modal before submitting deletion request

---

### Task 1.8: Registration Success Screen with JS Countdown
**Files:** Create `resources/views/auth/success.blade.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`

**Actions:**
- After successful registration, redirect to a success page instead of instantly to dashboard
- Success page shows a JS countdown (e.g. 5 seconds) with progress indicator before auto-redirecting to dashboard
- Match WP's post-registration success screen behavior exactly
- Update `RegisteredUserController` to redirect to success route instead of dashboard directly

---

## Group 2: Livewire Components

### Task 2.1: Fix Commission Calculation in Order Form (CRITICAL)
**File:** `app/Livewire/NewOrder.php`

**Actions:**
- **CRITICAL:** Change from flat 3% margin to tiered system: 8% commission if order >= 500 SAR, flat 50 SAR minimum if order < 500 SAR
- Current implementation incorrectly calculates: `total += price * qty * rate * (1 + margin)` with flat 0.03 margin
- Must implement: Calculate total order value first, then apply 8% if >= 500 SAR, else 50 SAR flat minimum
- Make commission rules configurable from admin settings (store in `settings` table)
- Update real-time cost calculation to use correct commission logic

---

### Task 2.2: Add Arabic Numeral Conversion
**File:** `app/Livewire/NewOrder.php`, `resources/views/livewire/new-order.blade.php`

**Actions:**
- Implement `convert2num()` JavaScript function to convert Arabic numerals (٠١٢٣٤٥٦٧٨٩) to Western digits
- Apply to all numeric inputs (quantity, price fields)
- Ensure conversion happens on input/blur events

---

### Task 2.3: Fix Auth Modal (No Username, Password >4 Chars)
**File:** `app/Livewire/NewOrder.php` (guest login modal), `app/Http/Controllers/Auth/RegisteredUserController.php`

**Actions:**
- Remove username requirement entirely (email only)
- Change password validation to minimum 4 characters (not 8)
- Update registration form validation rules

---

### Task 2.4: Add Facebook Social Login
**Files:** `resources/views/auth/login.blade.php`, `resources/views/auth/register.blade.php`, `app/Http/Controllers/Auth/SocialAuthController.php`, `routes/web.php`

**Actions:**
- Install Laravel Socialite package
- Add Facebook OAuth integration (match WP's nextend-facebook-connect plugin functionality)
- Add "Login with Facebook" button to login and register pages
- Handle OAuth callback and create/login user
- Store Facebook user ID in users table

---

## Group 3: Controllers

### Task 3.1: Add Rate Limiting Per Role
**File:** `app/Http/Controllers/OrderController.php`, `routes/web.php`

**Actions:**
- Implement per-role rate limits: 10 orders/hour for customers, 50/hour for admins/staff
- Create custom middleware or update throttle middleware to check role
- Apply to `/new-order` route

---

### Task 3.2: Add Comment Templates for Staff
**Files:** `app/Http/Controllers/OrderController.php`, create `app/Models/CommentTemplate.php`

**Actions:**
- Create `comment_templates` table migration (title, content, usage_count, sort_order)
- Create CommentTemplate model
- Add quick-reply template selector in order comment form (staff only)
- Track usage count, make sortable by usage
- Add Filament resource for managing templates

---

### Task 3.3: Add MyAds/Affiliate Tracking
**Files:** Create `app/Models/AdCampaign.php`, `app/Http/Controllers/OrderController.php`

**Actions:**
- Create `ad_campaigns` table migration (title, slug, tracking_code, etc.)
- Add `ad_campaign_id` and `google_click_id` to users table
- Track campaign attribution on order creation
- Add Filament resource for managing campaigns

---

### Task 3.5: Order-Level Address Selector
**Files:** `resources/views/orders/show.blade.php`, `app/Http/Controllers/OrderController.php`

*(Same order show blade as Task 1.4 — edit together to avoid duplicate passes)*

**Actions:**
- On the order detail page, allow both Admins and Customers to change `shipping_address_id` if the order has not yet been definitively processed (e.g. status is pending/new)
- Show a dropdown of the user's saved addresses
- On change: update `shipping_address_id` and refresh the `shipping_address_snapshot` JSON field with the new address data
- Match WP's order-view address-change behavior exactly

---

### Task 3.6: Account Controller — WhatsApp Logging + Account Deletion Protocol
**File:** `app/Http/Controllers/AccountController.php`

*(Companion controller logic for Task 1.7)*

**Actions:**
- In `updateNotifications()`: detect if `notify_whatsapp` value changed; if so, post a system comment on the user's latest active order (e.g. "Customer updated WhatsApp notification preference to: ON/OFF") so the team is aware — matches WP behavior exactly
- Add `requestDeletion()` method: sets `deletion_requested = true` on the user, then posts a system comment on the user's most recent active order alerting the team
- Register routes for both actions in `routes/web.php`

---

### Task 3.4: Auto-Attach Shipping Address to Orders
**File:** `app/Livewire/NewOrder.php`, `app/Http/Controllers/OrderController.php`

**Actions:**
- On order creation, automatically fetch user's default shipping address
- Create snapshot of address data and attach to order (store in `shipping_address_snapshot` JSON field)
- Match WP behavior: WP automatically attaches default address snapshot to new orders
- If no default address exists, allow order creation but mark address as missing

---

## Group 4: Models & Migrations

### Task 4.1: Add Notification Preferences to Users
**File:** `database/migrations/xxxx_add_notification_preferences_to_users_table.php`

**Actions:**
- Add columns: `notify_orders` (boolean), `notify_promotions` (boolean), `notify_whatsapp` (boolean), `unsubscribed_all` (boolean)
- Update User model fillable/casts

---

### Task 4.2: Add Geolocation & Detailed Device Metadata to Activity Logs
**File:** `database/migrations/2026_02_20_100002_create_user_activity_logs_table.php`, create `app/Services/UserAgentParser.php`, create `app/Services/GeoIPService.php`

**Actions:**
- Add columns: `browser` (string), `browser_version` (string), `device` (string), `device_model` (string, e.g., "iPhone 15 Pro"), `os` (string), `os_version` (string), `country` (string), `city` (string)
- Create UserAgentParser service to extract: exact device model (e.g., iPhone 15 Pro), OS version, browser version
- Create GeoIPService to query ip-api.com (free API) for country/city based on IP
- Update activity logging during order creation to capture all metadata
- Parse user agent string to extract device model, OS, browser details (match WP's `wz_parse_browser()` and `wz_parse_device()` functions)
- Perform background GeoIP API query on order creation (use ip-api.com like WP's `wz_geo_from_ip()`)
- Add indexes on new columns

---

### Task 4.3: Add Ad Campaign Tracking
**File:** `database/migrations/xxxx_add_ad_campaign_tracking_to_users_table.php`

**Actions:**
- Add `ad_campaign_id` (foreign key) and `google_click_id` (string nullable) to users table
- Create `ad_campaigns` table migration

---

### Task 4.5: Add Secondary Phone & Account Deletion Fields to Users
**File:** Create `database/migrations/xxxx_add_secondary_phone_and_deletion_to_users_table.php`, `app/Models/User.php`

**Actions:**
- Add `phone_secondary` (string, nullable) to users table — WP saves both `phone` and `phone_secondary`
- Add `deletion_requested` (boolean, default false) to users table
- Update User model `$fillable` and `$casts`

---

### Task 4.4: Make File Size Limit Configurable
**File:** `app/Models/Setting.php`, `app/Livewire/NewOrder.php`

**Actions:**
- Add `max_file_size_mb` setting (default: 2MB to match WP)
- Update file upload validation to use setting value
- Add to Filament settings page

---

## Group 5: Email System (Mailables)

### Task 5.1: Create Email Classes
**Files:** Create `app/Mail/OrderConfirmation.php`, `app/Mail/PasswordReset.php`, `app/Mail/EmailVerification.php`, `app/Mail/RegistrationWelcome.php`

**Actions:**
- Create OrderConfirmation mailable (sent on order creation - manual trigger)
- Create PasswordReset mailable (auto-sent by Breeze, verify it works)
- Create EmailVerification mailable (auto-sent by Breeze, verify it works)
- Create RegistrationWelcome mailable (auto-sent on registration)
- All emails should be HTML with RTL support, branded headers
- Respect notification preferences (`notify_orders`, `unsubscribed_all`)

---

### Task 5.2: Manual Email Triggers
**Files:** `app/Http/Controllers/OrderController.php`

**Actions:**
- Add manual "Send Email" button/action for order status updates (staff only)
- Add manual "Send Email" button for comment notifications (staff only)
- Store email send history/logs

---

## Group 6: Scheduled Jobs & Commands

### Task 6.1: Exchange Rate Auto-Fetch Cron
**Files:** Create `app/Console/Commands/FetchExchangeRates.php`, `app/Console/Kernel.php`

**Actions:**
- Create artisan command to fetch exchange rates from same free API WordPress uses
- Implement same functions as WP (extra %, conversion logic)
- Schedule daily/hourly cron job
- Store rates in `settings` table (JSON)
- Add manual "Fetch Now" button in Filament admin

---

## Group 7: JavaScript/Frontend

### Task 7.1: Arabic Numeral Conversion Script
**File:** `resources/js/app.js` or create `resources/js/arabic-numerals.js`

**Actions:**
- Implement `convert2num()` function
- Convert ٠١٢٣٤٥٦٧٨٩ to 0123456789
- Apply to all numeric inputs via event listeners
- Ensure works with Livewire reactive inputs

---

### Task 7.2: Image Lightbox Library
**File:** `resources/js/app.js`, `package.json`

**Actions:**
- Install lightweight lightbox library (GLightbox or similar)
- Initialize on order detail pages
- Apply to product images and file attachments

---

## Group 8: Settings & Configuration

### Task 8.1: Commission Rules in Admin Settings
**File:** `app/Filament/Pages/SettingsPage.php`

**Actions:**
- Add commission threshold setting (default: 500 SAR)
- Add commission percentage above threshold (default: 8%)
- Add flat commission below threshold (default: 50 SAR)
- Update order form to read from settings

---

### Task 8.2: File Size Limit Setting
**File:** `app/Filament/Pages/SettingsPage.php`

**Actions:**
- Add `max_file_size_mb` setting (default: 2MB)
- Update validation rules to use this setting

---

### Task 8.3: Comprehensive Admin Settings Panel
**File:** `app/Filament/Pages/SettingsPage.php`

*(Same file as Tasks 8.1 & 8.2 — edit all together in one pass)*

**Actions:**
- Implement a full settings UI matching WP's specialized order-settings admin page; all values stored as JSON in the `settings` table
- **Exchange rates:** manual override fields per currency, extra % markup, auto-fetch toggle
- **Commission structures:** threshold amount, percentage above threshold, flat fee below threshold (already in 8.1 — consolidate here)
- **Order limits:** max products per order, max orders per user per day
- **File limits:** max file size MB (already in 8.2 — consolidate here), allowed file types
- **Delivery rules:** shipping zones, per-zone fees, estimated delivery days
- **Quick Action button toggles:** enable/disable individual quick-action buttons shown to staff on order detail (e.g. "Mark Paid", "Mark Shipped")
- **UI field toggles:** e.g. URL validation strictness (strict vs. lenient product URL checking), show/hide optional order fields
- All settings must be readable app-wide via a `Setting::get('key')` helper

---

## Current Task

**Status:** ALL TASKS FULLY COMPLETE ✓

**What was completed this session:**

**Shipping Rates — Admin Settings + Dynamic Calculator (COMPLETED):**
- Added a collapsible **"Shipping Rates"** section to `app/Filament/Pages/SettingsPage.php` with three sub-sections (Aramex, DHL, US Domestic), each exposing `first_half_kg`, `rest_half_kg`, `over21_per_kg`, and `delivery_days` fields backed by the `settings` table. Keys: `aramex_*`, `dhl_*`, `domestic_*`.
- Updated `resources/views/pages/shipping_calculator.blade.php` to read all carrier prices from `Setting::get(...)` with WP-parity fallbacks. Both the Alpine.js `carriers` object and the static HTML pricing table now use PHP variables — no more hardcoded SAR values.
- This completes **Task 8.3 (Delivery rules: shipping zones + fees)** — the last outstanding item.

**Previously completed (all done):**
- Task 6.1: `FetchExchangeRates` command + daily schedule + SettingsPage Exchange Rates section with "Fetch Now" button.
- Task 6.1 steps 5–6: `calculator.blade.php` reads exchange rates and commission settings from `Setting::get`.
- Tasks 8.1–8.2: Commission rules (threshold, rate, flat fee) in SettingsPage.
- Quick-action toggles (`qa_mark_paid`, `qa_mark_shipped`, `qa_request_info`, `qa_cancel_order`) in SettingsPage.
- All wiring tasks: `url_validation_strict`, `max_orders_per_day`, quick-action buttons on order detail.

**Next Action:** All Opus tasks are fully complete. The Laravel app has full WordPress parity.

---

## Instructions

After completing a task, update the `## Current Task` section in `Opus_Task.md` with:
- What was just completed
- Exactly what the next session should start with

Then tell the user: **"Start next session with: `Opus_Task.md` then continue from the Current Task section."**
