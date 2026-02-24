---
name: Laravel Full Rebuild
overview: Full production-ready rebuild of Wasetzon from WordPress to Laravel 12 (TALL stack), with complete feature parity, MySQL, RTL/Arabic from day one, and a zero-downtime migration path — WordPress stays live throughout the entire build.
isProject: false
---

# Laravel Full Rebuild — Wasetzon

## Context

- WordPress is live and taking real orders daily. It stays live until Laravel is fully validated.
- Full feature parity, no MVP phase — build everything once, correctly.
- Local development only until production-ready. Manual SSH deploy (no Forge/Ploi).
- **Features reference:** New WordPress site (`pwa3/app/public/`) — replicate functionality only, design UI from scratch with modern clean aesthetic (AI has full creative freedom for layout, spacing, placement).
- **Data migration:** See `MIGRATION.md` (read only when migrating a new site).
- **100% bilingual:** Every page/string translatable (Arabic/English), language toggle in **footer only** (bottom bar), RTL auto-switches with Arabic. **Default language is always Arabic** (`APP_LOCALE=ar`).
- **Mobile-first:** 95% of users on mobile — all customer-facing pages designed mobile-first, desktop secondary. Filament admin is desktop-first.
- **Rewrite all templates from scratch** — no copy/paste from WordPress, build clean Laravel/Livewire components.
- **PWA features:** Offline support, service worker, app manifest, performance optimizations (lazy loading, code splitting).
- **Feature audit:** Before building each feature, AI audits the corresponding source in the new WordPress site (`pwa3/app/public/`) to capture all behavior, edge cases, and micro-interactions — no features missed.
- **AI behavior:** When blocked or uncertain, ask the owner before guessing or hallucinating a solution.

## Local Environment

**Recommended: [Laravel Herd**](https://herd.laravel.com/) (macOS native, zero config)

- Install Herd → it manages PHP, nginx, and `.test` domains automatically
- Create the project at:
  - Path: `/Users/abdul/Desktop/Wasetzon/wasetzonlaraval` → served at `wasetzonlaraval.test` (or configure custom domain in Herd)
- Install MySQL via Homebrew (`brew install mysql`) or [DBngin](https://dbngin.com/) (GUI, zero config)
- Install Redis via Homebrew (`brew install redis`)

## Stack

- **Laravel 12** + **Laravel Breeze** (auth scaffolding)
- **Livewire 3** (server-side reactivity)
- **Alpine.js** (client-side UI behavior)
- **Tailwind CSS v4** (RTL-aware utility classes)
- **Filament 4** (admin panel — blog, pages, settings only; stable since Aug 2025, ships with Tailwind v4 and TipTap editor built-in)
- **Spatie Laravel Permission** (role + permission management)
- **MySQL 8** (database)
- **Redis** (sessions, cache, queues)

## User Roles & Permissions

### 5 Role Levels

1. **Guest** — Browse site, fill order form, must login to submit
2. **Customer** — Create/view own orders, comment, upload receipts, manage profile
3. **Editor (5-10 per site)** — View all orders, reply to comments, update status, add internal notes, merge orders
4. **Admin (2-4 per site)** — Everything editors can do + Filament access (blog, pages, settings, exchange rates, user management)
5. **Super Admin (1-2)** — Everything + manage other admins, cannot be demoted

### Spatie Permission System

- Granular permissions per action: `merge-orders`, `edit-prices`, `update-status`, `export-csv`, `ban-users`, etc.
- Assign permissions to roles by default, override per individual user from Filament
- UI buttons check permissions — if user lacks permission, button doesn't render

## Schema (All Tables — Day One)

```
users, roles, permissions, model_has_roles, model_has_permissions (Spatie tables)
user_addresses, user_activity_logs
orders, order_items, order_timeline, order_files
order_comments, post_comments (separate tables for better data integrity)
order_comment_edits (full edit history per comment: old_body, edited_by, edited_at)
order_comment_reads (read receipts per comment per user)
posts, post_categories, pages
settings (key/value)
activities (inbox feed: new orders, comments, payments, contact forms)
```

Key design constraints:

- `order_items` is a separate table — never JSON blobs
- `order_comments` and `post_comments` are separate tables (better data integrity with foreign keys)
- `order_comment_edits` stores full edit history per comment (`old_body`, `edited_by`, `edited_at`) — confirmed present in new WordPress site using comment meta
- `order_comment_reads` tracks which staff/users have seen each comment ("read by" indicator)
- `activities` table powers the `/inbox` feed
- All tables include proper foreign keys, indexes, and constraints
- **No `translations` table** — bilingual strings live in `lang/ar.json` and `lang/en.json` (Laravel lang files only, single source of truth)

## Build Phases

### Phase 1 — Foundation (Week 1–2)

- Fresh Laravel 12 install + full stack (Breeze, Livewire, Alpine, Tailwind, Filament 4, Spatie Permission)
- **Design system:** White background, clean/minimal, primary color configurable per site (orange for Wasetzon), font family configurable — both set in Filament admin
- **Fonts:** IBM Plex Sans Arabic (Arabic), Inter (English) — auto-switch with language
- **Bilingual setup:** Laravel localization (`lang/ar.json`, `lang/en.json`) as single source of truth — no `translations` table. Filament includes a strings editor resource so team can edit translations from admin without touching files directly. Language toggle in footer.
- RTL Tailwind config (`dir="rtl"`, RTL auto-switches with Arabic)
- **PWA setup:** Service worker, app manifest, offline support, performance optimizations
- MySQL schema: all migrations written upfront
- Redis configuration (sessions, cache, queues)
- Role system: 5 roles (guest/customer/editor/admin/superadmin) via Spatie Permission
- Shared layouts: header (role-aware nav, language toggle, cart icon conditional), footer, base Blade templates
- Files: `resources/views/layouts/`, `app/Models/`, `database/migrations/`, `lang/ar/`, `lang/en/`, `public/sw.js`, `public/manifest.json`

### Phase 2 — Order System (Week 3–4)

Three most complex pages, built first — **rewrite all templates from scratch, no WordPress copy/paste**:

`**/new-order**` — Livewire component, Option 1 only (built). Options 2 and 3 deferred — do not build unless explicitly requested.

**Option 1: Responsive (mobile cards, desktop table)** ✅ Built

- Mobile: card-based, stacked
- Desktop: flex row (all fields in one line, column headers above)
- Admin sets site-wide default layout in Filament settings

**Option 2: Cart system** — deferred

**Option 3: Cards everywhere** — deferred

**Fields are identical regardless of layout** — URL/text, qty, color, size, notes, image upload (1 per product), currency dropdown.

**All options include:**

- Paste product URLs or text (text displays as-is, not converted to links)
- Per-product fields: qty, color, size, notes, image upload (1 per product, 10 max on form), currency dropdown
- Currency persistence: when user changes currency, next item defaults to that currency
- Real-time cost calculations (only shows when ALL items have price + quantity filled)
- Empty rows (no URL/text) deleted on submit
- localStorage draft for guests
- Modal login/register (no redirect)
- Order modification: customers can edit/add items pre-payment within time limit (configurable by admin)
- Product limits: 30 (customer), 200 (admin merge)
- **Mobile-first:** All layouts optimized for mobile (95% users), desktop secondary
- **Performance:** Lazy load images, debounce calculations, minimize re-renders

`**/orders**` — two separate Blade templates, resolved in controller via role check

- `orders/index.blade.php` (customer): their orders only, Kanban grouping (Needs Action / In Progress / Completed)
- `orders/staff.blade.php` (staff): all orders, search by order number, filter by status, bulk actions, Excel export
- **Mobile-first:** Kanban cards stack on mobile, horizontal scroll on desktop

`**/orders/{id}**` — one shared Blade template (`orders/show.blade.php`)

- All users see: visual status timeline, comment thread, file uploads
- Customer-only sections: edit items (if within time limit + pre-payment)
- Staff-only sections (gated with `@can`): edit status/prices, internal notes, merge orders, generate PDF invoice on-demand (auto-posts comment with link)
- **Comment features:**
  - Staff can delete any comment; customers can delete their own within a configurable time window
  - Edited comments show "edited" indicator; full edit history stored and viewable by staff
  - "Read by" tracking per comment — visible to staff (powered by `order_comment_reads` table)
  - Per-comment "Send notification" button (staff only) — email is never automatic on comments, always manual trigger
- **Mobile-first:** Timeline vertical on mobile, comments full-width

### Phase 3 — Supporting Features (Week 5)

**Rewrite all templates from scratch, maintain all business rules:**

- `/account` — profile, saved addresses, activity log (mobile-first)
- `/inbox` — staff only, unified activity feed (all comments, payments, new orders, contact forms) (mobile-first)
- Email notifications: Build templates and queue jobs (ready to send), but **disabled by default** — no emails triggered until admin configures SMTP and enables per-type toggles. Email types: account registration, password reset, welcome, email confirmation (optional), comment notifications (opt-in per user). Each type has its own on/off toggle in Filament settings.
- Rate limiting (configurable via settings: 10 orders/hour customers, 50/hour admins)
- Order merging + duplicate order
- File upload limits: 1 per product, 1 per comment, 10 max per order
- Login flow: guests can fill form, modal login on submit (no redirect)

### Phase 4 — Filament Admin (Week 6)

**Rewrite all admin templates from scratch:**

- Blog posts: rich text editor (Tiptap), categories, featured images, SEO fields
- Blog comments: moderation, guest comments, threaded replies (one level of nesting), email notifications
- Static pages: rich editor, header/footer toggles, menu order, SEO fields
- Settings resource:
  - **Multi-currency system:**
    - All major world currencies with correct symbols (USD $, EUR €, GBP £, SAR ر.س, AED د.إ, etc.)
    - Auto-update exchange rates via API
    - Per-currency markup percentage (configurable per currency)
    - Admin enable/disable which currencies appear in dropdown
    - "Other" option in currency list (for Bitcoin, custom currencies - no auto-conversion, manual entry only)
  - Commission rules
  - Hero text
  - Delivery options
  - Order limits
  - Order modification time limit (minutes, pre-payment only)
  - Default currency for new order form
  - Primary color
  - Font family
  - Default language
  - Logo (text or image upload)
  - Header/footer custom scripts (analytics)
  - Menu items (header/footer, ar/en labels, order, role visibility)
  - Form settings (fields visibility, validation, order, labels ar/en)
  - Quick actions (staff shortcuts, role-based visibility)
  - PDF invoice template (fields: costs, fees, shipping, extras)
  - **SMTP email configuration:**
    - SMTP host, port, username, password, encryption (TLS/SSL)
    - From name and email address
    - Test email button
  - **Per-type email toggles** (each independently on/off):
    - Registration confirmation
    - Welcome email
    - Password reset
    - Email verification (optional)
    - Comment notifications (users opt-in per order)
- User management: search, edit email/password, ban, assign roles, assign individual permissions
- Translations editor: read/write `lang/ar.json` and `lang/en.json` from admin UI (no `translations` table)

### Phase 6 — QA & Parallel Run (Week 8–9)

- Team tests Laravel site at `wasetzonlaraval.test` (or configured domain) against live WordPress
- New orders entered in both systems simultaneously
- Bug fixes, edge case handling
- Performance: eager loading, N+1 query fixes, Redis cache warm-up
- **Mobile QA (95% of users are on mobile)** — test on real devices (iOS Safari, Android Chrome)
- **PWA testing:** Offline support, service worker, app manifest, install prompt
- **Performance testing:** Lighthouse scores (mobile/desktop), page load times, Livewire interactions, real-time calculations

### Phase 7 — Production Deploy (Week 10)

Manual SSH deployment (no Forge/Ploi):

- Set up server: PHP 8.3, MySQL 8, Redis, nginx, Supervisor (queue workers)
- Deploy via GitHub → SSH → `git pull`, `composer install`, `php artisan migrate`, `npm run build`
- Symlink `storage/`, set permissions
- Configure nginx vhost for `wasetzon.com`
- Add nginx 301 rule: `rewrite ^/order/(.*)$ /orders/$1 permanent;` (covers all legacy order URLs)
- SSL via Let's Encrypt (`certbot`)
- Keep WordPress live at a subdomain (`old.wasetzon.com`) for 30 days as fallback
- DNS switch → monitor closely

## Key Architectural Patterns

- **Blade template strategy:**
  - `/orders` list: two separate Blades (`orders/index.blade.php` customer, `orders/staff.blade.php` staff) — resolved in controller via role check
  - `/orders/{id}` detail: one shared Blade (`orders/show.blade.php`) — staff sections gated with `@can`
- **Separate comment tables** — `order_comments` and `post_comments` for better data integrity with proper foreign keys
- **Comment read tracking** — `order_comment_reads` table records which users have seen each comment
- **Settings table** — key/value store with typed values; cached in Redis, invalidated on save
- **Activity/inbox** — every significant event (new order, comment, payment receipt, contact form) writes a row to `activities`; `/inbox` is just a query on this table
- **File storage** — Laravel `Storage` facade with local disk driver. Driver configured in `.env` — switching to S3/R2 later requires no code changes, only config update.
- **Lang files only** — `lang/ar.json` and `lang/en.json` are the single source of truth for all UI strings. No database translations table.

## Design System

- **Base:** White background, clean/minimal aesthetic
- **Primary color:** Configurable per site (e.g., orange for Wasetzon) — set in Filament settings, applied via CSS variables
- **Font family:** Configurable in Filament settings (default: IBM Plex Sans Arabic for Arabic, Inter for English)
- **Mobile-first:** All customer-facing pages designed for mobile (95% of users), desktop is secondary. Filament admin is desktop-first.
- **No-scroll forms:** Key forms must fit on screen without scrolling on mobile — sign-in (email, password, forgot password link, login button all visible), sign-up, and each new-order item row (all fields for one product visible without scrolling). If a form cannot fit, compact the layout — never require vertical scrolling to reach the submit action.
- **Bilingual (100%):** Every string translatable, language toggle in footer, RTL auto-switches with Arabic
- **Templates:** All rewritten from scratch, no WordPress copy/paste — clean Laravel/Livewire components
- **PWA:** Service worker, app manifest, offline support, performance optimizations (lazy loading, code splitting, debouncing)

## URL Structure

- `/orders/` — orders list
- `/orders/{id}` — order detail
- `/blog/{slug}` — blog post
- `/faq/`, `/calculator/`, etc. — static pages

**Legacy redirect:** nginx 301 rule redirects `/order/(.*)` → `/orders/$1` — one rule covers all 66k+ legacy order URLs. No Laravel-level redirect needed.

## File Paths Reference

**Site labels used throughout this document:**
- **Legacy site** — old WordPress (data source, SQL dump)
- **New WordPress site** — `pwa3/app/public/` (features reference, almost-live but not switching to)
- **Laravel site** — `/Users/abdul/Desktop/Wasetzon/wasetzonlaraval/` (the build target)

**Paths:**
- **Legacy site DB dump:** `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/wasetzonjan302026.sql`
- **Legacy site uploads:** `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/old-wordpress/old-wp-content/uploads/`
- **New WordPress site (features reference):** `/Users/abdul/Desktop/Wasetzon/Wordpress/pwa3/app/public/`
