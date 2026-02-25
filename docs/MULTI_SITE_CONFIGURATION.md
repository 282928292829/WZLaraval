# Multi-Site Configuration Guide

This document defines how to configure the codebase for reuse across 20+ separate websites. No hardcoded brand values; all site-specific data is configurable.

---

## 1. Configuration Layers

| Layer | Purpose | Where |
|-------|---------|-------|
| **`.env`** | Environment-specific (DB, cache, URLs, secrets) | Per deployment |
| **`config/*.php`** | Reads `env()` only here; never in app code | Single source |
| **`settings` table** | Site-specific (name, logo, contact, toggles) | Filament Settings |
| **`lang/*.json`** | UI strings; use placeholders for dynamic values | Per locale |

**Rule:** Use `config('key')` in app code, never `env()` directly.

---

## 2. Settings (Filament → Site Settings)

### Already Configurable
- `site_name`, `logo_image`, `logo_text`, `primary_color`, `font_family`
- `whatsapp`, `contact_email`, `commercial_registration` (contact group)
- `default_language`, `default_currency`
- Order rules, commission, exchange rates, shipping rates
- Invoice: logo, site name, field toggles
- Quick actions, email/SMTP, SEO defaults

### Now Configurable (implemented)
- **Contact section** — WhatsApp, contact email, commercial registration (Filament → Contact).
- **Certification section** — Logo upload (SVG/PNG/WebP), certificate URL, show partners toggle (Filament → Certification).
- **Auto-comment placeholders** — `:whatsapp` and `:site_name` in `orders.auto_comment_*`; passed from `NewOrder.php`.

---

## 3. Hardcoded Values to Remove

| Location | Issue | Fix |
|----------|-------|-----|
| ~~`resources/views/pages/shipping_calculator.blade.php`~~ | ~~hardcoded WhatsApp~~ | ✅ `Setting::get('whatsapp')` |
| ~~`resources/views/welcome.blade.php`~~ | ~~WhatsApp link~~ | ✅ `Setting::get('whatsapp')` |
| ~~`lang/*.json` `orders.auto_comment_*`~~ | ~~hardcoded~~ | ✅ `:whatsapp`, `:site_name` placeholders |
| `lang/*.json` `app.name` | Default "Wasetzon" | Keep as fallback; `config('app.name')` from `.env` |
| `lang/*.json` various | "Wasetzon" in strings | Replace with `:site_name` where dynamic (as needed) |
| ~~`layouts/app.blade.php`~~ | ~~SBC hardcoded~~ | ✅ `certification_logo`, `certification_url` settings |
| ~~`SettingsSeeder`~~ | ~~info@wasetzon.com~~ | ✅ Generic defaults |
| ~~`OrderController::invoiceSettings()`~~ | ~~'Wasetzon' fallback~~ | ✅ `config('app.name')` |

---

## 4. Translation Placeholders

Strings that include contact or brand must use placeholders:

```
:site_name   — from Setting::get('site_name') ?: config('app.name')
:whatsapp    — from Setting::get('whatsapp')
:contact_email — from Setting::get('contact_email')
```

**Example:** `orders.auto_comment_with_price` should be:
```
"...contact us via WhatsApp: :whatsapp"
"...:site_name commission: :commission SAR..."
```

`NewOrder.php` must pass these when calling `__()`.

---

## 5. Deployment Checklist (Per Site)

1. **`.env`**
   - `APP_NAME`, `APP_URL`
   - DB, cache, queue, mail
   - OAuth keys if using social login

2. **Database**
   - Run migrations
   - Run `SettingsSeeder` (sets defaults)
   - Edit Settings in Filament: site name, logo, contact (WhatsApp, email), commission label

3. **Optional Settings**
   - `partners_logo_url`, `certification_url` (if showing certification)
   - `invoice_comment_default` (default message when posting invoice)

4. **Translations**
   - Use Filament Translations editor to customize `lang/ar.json`, `lang/en.json` per site, OR
   - Keep shared JSON; placeholders handle brand/contact

---

## 6. Settings Seeder Defaults

`SettingsSeeder` should use **generic** defaults, not Wasetzon-specific:

- `contact_email` → `'contact@example.com'` or empty
- `whatsapp` → `'966500000000'` (placeholder)
- `site_name` → already from `config('app.name')` or `'My Store'`

Each site admin configures real values in Filament.

---

## 7. Consistency Rules

1. **No `env()` outside config files** — already followed.
2. **No hardcoded phone/email/URLs** — use Settings.
3. **Brand in translations** — use `:site_name` placeholder.
4. **Defaults** — generic in seeders; brand-specific only in `.env` per deployment.
5. **Commission label** — add `commission_label` setting; default "Commission" or use `site_name` + " commission".

---

## 8. Files to Update (Summary)

| File | Change |
|------|--------|
| `app/Filament/Pages/SettingsPage.php` | Add Contact section (whatsapp, contact_email, commercial_registration); add partners/certification URLs |
| `app/Livewire/NewOrder.php` | Pass `whatsapp`, `site_name` to `__('orders.auto_comment_*')` |
| `lang/ar.json`, `lang/en.json` | Add `:whatsapp`, `:site_name` to auto_comment templates; fix Commission string |
| `resources/views/pages/shipping_calculator.blade.php` | Use `Setting::get('whatsapp')` for WhatsApp link |
| `resources/views/welcome.blade.php` | Use `Setting::get('whatsapp')` |
| `resources/views/layouts/app.blade.php` | Use settings for partners logo and certification URL |
| `database/seeders/SettingsSeeder.php` | Generic defaults; add new keys |
| `app/Http/Controllers/OrderController.php` | Remove 'Wasetzon' fallback; use `config('app.name')` |

---

## 9. Testing Multi-Site Readiness

1. Clone project, fresh DB, run migrations + seed.
2. Set `APP_NAME=OtherBrand` in `.env`.
3. Configure Filament Settings: different site name, WhatsApp, email.
4. Place order with prices → verify auto-comment shows correct WhatsApp and brand.
5. Check footer, shipping calculator, welcome page — all show configured contact.
6. Generate invoice — shows configured logo/site name.
