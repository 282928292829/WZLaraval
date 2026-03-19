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
3. **Staff (5-10 per site)** — View all orders, reply to comments, update status, add internal notes, merge orders
4. **Admin (2-4 per site)** — Everything staff can do + Filament access (blog, pages, settings, exchange rates, user management)
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

## Current State

**Built and complete.** Orders as hub, all 7 new-order layouts (Cards, Table, Hybrid, Wizard, Cart, Cart Inline, Cart Next), Filament admin, /account, /inbox, blog, pages, settings. Table layout is site default. See `wasetzon.mdc` for session handoff.
