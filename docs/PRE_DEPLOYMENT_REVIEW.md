# Pre-Deployment Review — Wasetzon Laravel

**Date:** March 8, 2025  
**Scope:** Full codebase review — architecture, security, performance, UX/UI, business logic  
**Project:** Wasetzon Laravel rebuild (Laravel 12, TALL stack, bilingual Arabic/English)

---

## Executive Summary

This is a comprehensive pre-deployment review of the Wasetzon Laravel codebase. The rebuild is well-structured, follows Laravel conventions, and has solid authorization, validation, and XSS protection. Several issues require attention before production launch, ranging from **Critical** (exposed dev/demo routes, missing duplicate button) to **Low** (bilingual gaps, UX refinements).

**Recommendation:** Address all Critical and High items before go-live. Medium and Low items can be scheduled post-launch if time is constrained.

---

## Critical Priority

### Security

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| C1 | **Dev/demo/test routes exposed in production** | `routes/web.php` | Layout demos (`/layout-demo/*`), homepage demos (`/test-homepage-demo1`–`4`) are registered unconditionally. They expose internal UI variants and may confuse users or leak design intent. **Fix:** Wrap in `if (app()->environment('local'))` or remove before deploy. |
| C2 | **CSRF exception for Apple callback** | `bootstrap/app.php` | `auth/apple/callback` is excluded from CSRF validation. This is standard for OAuth callbacks (Apple sends POST without Laravel token). **Verify:** Apple Sign-In is configured and the callback URL is correct. If Apple auth is unused, consider removing the exception. |

### Business Logic / Feature Completeness

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| C3 | **Duplicate button missing on order show page** | `orders/show.blade.php` | Per `wasetzon.mdc` Current Task: "Duplicate button on order show page (`/orders/{id}`) — visible to customers and staff, links to `/new-order?duplicate_from={id}`" — **not implemented**. The duplicate flow (`?duplicate_from={id}`) works on NewOrder, but there is no button to trigger it from the order detail page. |

---

## High Priority

### Security

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| H1 | **ActivityFile download authorization** | `ActivityFileController::download` | Route is gated by `can:view-all-orders`. No explicit check that the `ActivityFile` belongs to an activity the user is allowed to access. For staff with `view-all-orders`, this is fine. **Verify:** No other roles can reach this route. |
| H2 | **Page slug in view path** | `PageController::show` | `view('pages.'.str_replace('-', '_', $slug))` — if an admin creates a page with slug containing `..`, path traversal is possible. **Fix:** Validate slug: `preg_match('/^[a-zA-Z0-9_-]+$/', $slug)` before using in view name. |

### UX/UI

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| H3 | **Dev toolbar blocks submit button (local)** | `resources/views/components/dev-toolbar.blade.php`, `resources/css/app.css` | Per `docs/NEW_ORDER_PAGE_ISSUES_REPORT.md`: Dev toolbar `z-[9999]` overlays order-summary-card; users cannot submit when toolbar is visible. **Fix:** Reposition toolbar, lower z-index, or hide in E2E/local when testing forms. |
| H4 | **"Add product" button below 44px touch target** | `resources/views/livewire/new-order.blade.php` | Apple HIG / WCAG recommend 44×44px minimum. **Fix:** Add `min-h-[44px]` or equivalent. |
| H5 | **CSV export hardcoded English headers** | `OrderController::exportCsv` | Headers: `['Order #', 'Customer', 'Email', 'Date', 'Items', 'Status', 'Subtotal', 'Total', 'Currency', 'Paid']` and `'Yes'`/`'No'` for paid — not wrapped in `__()`. Violates bilingual rule. **Fix:** Use translation keys for all user-facing strings. |

### Performance

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| H6 | **Invoice PDF generation synchronous** | `OrderController::generateInvoice` | Comment in code: "PDF is built synchronously. For heavy invoices... consider queueing." Large orders may cause request timeouts. **Fix:** Queue PDF generation for Items Cost / General with many lines, or when `invoice_language === 'both'`. |

---

## Medium Priority

### Security

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| M1 | **Order search LIKE injection** | `OrderController::customerIndexData`, `staffIndex`, `exportCsv` | `$query->where('order_number', 'like', "%{$search}%")` — user input is interpolated. Laravel's query builder parameterizes, but `%` and `_` in `$search` act as SQL wildcards. A malicious user could use `%` to broaden matches. **Fix:** Escape `%` and `_` in `$search` before LIKE, or use `whereRaw` with binding. |

### Bilingual / UX

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| M2 | **PDF/XLS/DOC labels in file preview** | `_order-item-card-option3.blade.php`, `_order-item-table-row.blade.php`, `_wizard-item-form.blade.php` | Labels "PDF", "XLS", "DOC" are hardcoded. Per wasetzon.mdc, technical acronyms stay in English — acceptable, but ensure consistency. |
| M3 | **Timeline body not translated** | `orders/show.blade.php` | `$entry->body` is displayed as-is. Timeline entries are created with `__()` in controllers (e.g. `__('orders.timeline_prices_updated')`), so stored text is already localized. **Verify:** All timeline creation paths use `__()`. |
| M4 | **Toast positioning on notched/RTL devices** | `resources/css/app.css` | `#toast-container` may overlap system UI on notched devices. Add `padding-top: env(safe-area-inset-top)`. |
| M5 | **File remove button (×) below 44px touch target** | `_order-item-card-option3.blade.php` | `w-4 h-4` (16×16px). **Fix:** Increase hit area with `p-2`, `min-w-[44px] min-h-[44px]`. |
| M6 | **Remove button missing aria-label on desktop** | `_order-item-card-option3.blade.php` | Mobile has `aria-label`, desktop does not. |

### Architecture

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| M7 | **Order policy `update` allows customer to update any field** | `OrderPolicy::update` | For owner: `return true` — customer can call `update` on their order. Most update actions (prices, status, etc.) use specific permissions (`edit-prices`, `update-order-status`). `updateShippingAddress` uses `authorize('update', $order)`. **Verify:** No other update endpoints inadvertently allow customers to change restricted fields. |
| M8 | **Known test failure** | `OrderControllerAuthTest > editor can export orders` | Per wasetzon.mdc and AGENTS.md: content-type assertion mismatch. Pre-existing; not caused by recent changes. **Fix before deploy:** Resolve or document as known issue. |

---

## Low Priority

### Bilingual

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| L1 | **Testimonial name/quote from DB** | `pages/testimonials.blade.php` | `$t->getName()`, `$t->getQuote()` — if testimonials are admin-entered, they may be in one language only. **Verify:** Testimonial model supports bilingual content if required. |
| L2 | **Contact submission display** | `contact-submissions/show.blade.php` | Labels "Name", "Email", "Phone", "Subject", "Message" — verify these use `__()` or are from a shared component. |

### UX / Accessibility

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| L3 | **Collapsible cards missing aria-expanded** | `_order-item-card-option3.blade.php` | Add `aria-expanded` and `aria-controls` for screen readers. |
| L4 | **Alpine x-for key uses index** | `new-order.blade.php` | `:key="idx"` — can cause incorrect DOM reuse when items reordered. Use stable `item._id` or similar. |
| L5 | **Tips toggle uses Unicode arrows (▲/▼)** | `new-order.blade.php` | May not render consistently; consider SVG icons. |
| L6 | **Focus visible on some buttons** | Various | Ensure `focus:ring` for keyboard users. |

### Code Quality

| ID | Issue | Location | Details |
|----|-------|----------|---------|
| L7 | **Duplicate orderNewLayout checks** | `new-order.blade.php` | Multiple `orderNewLayout === '3'` — consider computed property. |
| L8 | **Inline JavaScript in Blade** | `new-order.blade.php` | Long inline handlers (textarea auto-resize, etc.) — consider extracting to Alpine methods. |

---

## What Works Well

- **Authorization:** OrderPolicy, Form Requests, and `@can` gates are used consistently. Staff-only actions properly check `view-all-orders`, `edit-prices`, `merge-orders`, etc.
- **XSS protection:** `comment_body_safe()` uses `e()` before `linkify_whatsapp()` and `nl2br()`. Comment bodies are safely escaped.
- **Validation:** Form Request classes used for order actions; validation rules are comprehensive.
- **Bilingual:** Most user-facing strings use `__()`. Lang files (`ar.json`, `en.json`) are populated.
- **Rate limiting:** `RoleBasedThrottle` for new-order; `throttle:5,15` for comments/contact; `throttle:10,1` for order comments.
- **Dev routes:** `/_dev/login-as` is correctly gated by `app()->environment('local')` and `EnsureAppIsLocal` middleware.
- **Config:** `env()` used only in config files; `config()` used in application code.
- **Eager loading:** Order show page loads `user`, `items`, `files`, `timeline`, `comments` with relationships to avoid N+1.
- **URL safety:** `safe_item_url()` rejects `javascript:`, `data:`, etc. for product URLs.
- **File storage:** Laravel `Storage` facade; driver configurable via `.env` for future S3/R2.

---

## Recommendations Summary

| Priority | Action |
|----------|--------|
| **P0** | Remove or gate design/layout/homepage test routes for production |
| **P0** | Implement duplicate button on order show page |
| **P1** | Fix dev toolbar blocking submit (reposition or hide when form is active) |
| **P1** | Translate CSV export headers and Yes/No |
| **P1** | Add slug validation in PageController to prevent path traversal |
| **P2** | Queue heavy invoice PDF generation |
| **P2** | Fix touch targets (Add product, file remove) |
| **P2** | Add aria-label to desktop remove button; safe-area for toasts |
| **P2** | Resolve or document OrderControllerAuthTest CSV export failure |
| **P3** | RTL/accessibility pass; extract inline JS; Alpine key stability |

---

## Appendix: Files Reviewed

- `routes/web.php`, `bootstrap/app.php`
- `app/Http/Controllers/OrderController.php`, `OrderMergeController`, `PageController`, `ContactController`, `DevController`, `ActivityFileController`, `CommentTemplateExportController`
- `app/Policies/OrderPolicy.php`
- `app/Livewire/NewOrder.php`
- `app/Support/helpers.php` (comment_body_safe, comment_text_direction, safe_item_url)
- `app/Http/Middleware/RoleBasedThrottle.php`, `EnsureAppIsLocal.php`
- `resources/views/orders/show.blade.php`, `livewire/new-order.blade.php`, `livewire/partials/_order-item-card-option3.blade.php`
- `lang/ar.json`, `lang/en.json` (sample)
- `docs/NEW_ORDER_PAGE_ISSUES_REPORT.md`
- `LARAVEL_PLAN.md`, `.cursor/rules/wasetzon.mdc`
