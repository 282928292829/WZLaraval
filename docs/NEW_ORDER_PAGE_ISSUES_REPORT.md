# New Order Page — UI/UX/Technical Issues Report

**Page:** http://wasetzonlaraval.test/new-order  
**Viewports tested:** Desktop (1280×720), Mobile (375×667)  
**Layout tested:** Option 3 (Cards) — active layout  
**Date:** March 2025

---

## Executive Summary

The new-order page was tested on desktop and mobile viewports. Several issues were identified that affect usability, accessibility, and technical behavior. The most critical is the **dev toolbar blocking the submit button** in local environment.

---

## Critical Issues

### 1. Dev toolbar blocks submit button (Desktop, Local only)

| Aspect | Details |
|--------|---------|
| **Severity** | Critical |
| **Viewport** | Desktop |
| **Environment** | Local (`app()->environment('local')`) |
| **Description** | The dev toolbar (`x-dev-toolbar`) is fixed at `bottom:8px; left:8px` with `z-[9999]`. The order-summary-card (submit button) uses `z-[100]`. The toolbar overlays the fixed footer and intercepts clicks on the submit button. Users cannot submit orders when the toolbar is visible. |
| **Root cause** | `resources/views/components/dev-toolbar.blade.php` — `z-[9999]`; `resources/css/app.css` — `.order-summary-card` uses `z-[100]` |
| **Fix** | Hide dev toolbar in E2E/Playwright runs, or move it to a corner that doesn't overlap the submit button. Alternatively, give order-summary-card higher z-index when toolbar is present, or position toolbar above the footer (e.g. `bottom: 5rem`). |

---

## High Severity

### 2. "Add product" button below touch target size

| Aspect | Details |
|--------|---------|
| **Severity** | High |
| **Viewport** | Desktop, Mobile |
| **Description** | The "+ Add product" button has `py-3` (~12px padding) but the visual height can appear ~40px. Apple HIG and WCAG recommend at least 44×44px for touch targets. Risk of mis-taps, especially on mobile. |
| **Location** | Option 3: `resources/views/livewire/new-order.blade.php` line ~135 |
| **Fix** | Add `min-h-[44px]` or increase padding to ensure 44px minimum touch target. |

### 3. Dev toolbar z-index conflicts with login modal

| Aspect | Details |
|--------|---------|
| **Severity** | High |
| **Description** | Dev toolbar uses `z-[9999]`, same as login modal (`z-[9999]`). Can cause stacking/overlay conflicts when both are visible. |
| **Fix** | Use distinct z-index layers: modals `z-[9999]`, dev toolbar `z-[9998]` or lower. |

### 4. Fixed footer may cover content on short mobile viewports

| Aspect | Details |
|--------|---------|
| **Severity** | High |
| **Viewport** | Mobile (e.g. 667px height) |
| **Description** | Fixed footer with submit button may cover form content on short viewports. `#order-form` has `padding-bottom: 6rem` (96px) to compensate, but long forms or small screens (e.g. iPhone SE 375×667) can still have content obscured. |
| **Location** | `resources/css/app.css` — `.order-summary-card`, `#order-form` |
| **Fix** | Verify padding is sufficient; consider `min-height` on viewport or scroll-to-bottom behavior when adding products. |

---

## Medium Severity

### 5. Toast positioning on mobile / RTL / notched devices

| Aspect | Details |
|--------|---------|
| **Severity** | Medium |
| **Viewport** | Mobile, RTL |
| **Description** | Toasts use `fixed top-5 left-1/2 -translate-x-1/2`. On devices with notches or in RTL, they may overlap system UI or feel off-center. No `safe-area-inset-top` applied. |
| **Location** | `resources/css/app.css` — `#toast-container` |
| **Fix** | Add `padding-top: env(safe-area-inset-top)` and verify RTL centering. |

### 6. Table horizontal scroll on narrow desktop (Table layout)

| Aspect | Details |
|--------|---------|
| **Severity** | Medium |
| **Viewport** | Desktop 720–1024px width |
| **Description** | Table layout has `min-w-[720px]`. On viewports between 720–1024px, horizontal scroll is required. |
| **Location** | `resources/views/livewire/new-order.blade.php` — Table layout |
| **Fix** | Consider responsive column hiding or card layout for 768–1024px breakpoint. |

### 7. Option 3 card header density on mobile

| Aspect | Details |
|--------|---------|
| **Severity** | Medium |
| **Viewport** | Mobile (375px) |
| **Description** | Product card header has "Show/Edit" button, remove button, and product info. On 375px width this can feel cramped. |
| **Location** | `resources/views/livewire/partials/_order-item-card-option3.blade.php` |
| **Fix** | Consider wrapping or reducing button size on very narrow screens. |

### 8. File remove button (×) too small for touch

| Aspect | Details |
|--------|---------|
| **Severity** | Medium |
| **Viewport** | Mobile |
| **Description** | The × button on attached files is `w-4 h-4` (16×16px) — below 44px touch target. |
| **Location** | `_order-item-card-option3.blade.php` line 42 |
| **Fix** | Increase hit area with `p-2` and `min-w-[44px] min-h-[44px]` while keeping icon small. |

---

## Low Severity / Informational

### 9. Tips section checkbox tap area

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Description** | "Don't show for 30 days" checkbox may be small on mobile. Consider larger tap area. |

### 10. RTL form fields consistency

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Description** | Qty field uses `dir="rtl"`; other fields inherit from `html`. Worth a focused RTL/Arabic pass for alignment and flow. |

### 11. File attach label tap target

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Viewport** | Mobile |
| **Description** | The "Attach" label for file upload (`py-2 px-3`) could be larger for easier tapping. |

### 12. Tips toggle uses Unicode arrows (▲/▼)

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Description** | Tips section uses `x-text="tipsOpen ? '▲' : '▼'"` — may not render consistently across fonts. Consider SVG icons. |

### 13. No focus visible on some interactive elements

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Description** | Some buttons (e.g. remove, Show/Edit) may not have clear `focus:ring` for keyboard users. |

---

## Technical / Code Issues

### 14. Alpine `x-for` key uses index

| Aspect | Details |
|--------|---------|
| **Severity** | Low (Technical) |
| **Description** | `x-for="(item, idx) in items" :key="idx"` — using array index as key can cause incorrect DOM reuse when items are reordered or removed. |
| **Fix** | Use a stable identifier (e.g. `item.id` or generate `_id` on add) if items can be reordered. |

### 15. Duplicate `orderNewLayout` checks

| Aspect | Details |
|--------|---------|
| **Severity** | Informational |
| **Description** | Multiple `this.orderNewLayout === '3'` checks in Alpine — could be a computed property or constant for maintainability. |

### 16. Inline JavaScript in Blade (x-init, @input handlers)

| Aspect | Details |
|--------|---------|
| **Severity** | Informational |
| **Description** | Long inline handlers (e.g. textarea auto-resize, character limit) make the template hard to maintain. Consider extracting to Alpine methods. |

---

## Accessibility (A11y)

### 17. Remove button missing aria-label on desktop

| Aspect | Details |
|--------|---------|
| **Severity** | Medium |
| **Description** | Mobile remove button has `aria-label="{{ __('order_form.remove') }}"`; desktop version does not. |
| **Location** | `_order-item-card-option3.blade.php` line 17 |

### 18. Collapsible cards — no aria-expanded

| Aspect | Details |
|--------|---------|
| **Severity** | Low |
| **Description** | Card headers that expand/collapse could use `aria-expanded` and `aria-controls` for screen readers. |

---

## What Works Well

- No horizontal overflow on desktop or mobile
- Submit button visible and meets minimum size (120–180px width, ~48px height)
- Tips section expand/collapse works
- Product card expand/collapse (Option 3) works
- Color + size on same line (mobile grid)
- Safe area insets applied to order-summary-card (`env(safe-area-inset-bottom)`)
- RTL support via `dir` on html
- Validation errors displayed in red banner
- Login modal appears for guests on submit

---

## Recommendations Summary

| Priority | Action |
|----------|--------|
| P0 | Fix dev toolbar blocking submit — reposition or hide in local, or raise order-summary-card z-index |
| P1 | Increase "Add product" button to min 44px touch target |
| P1 | Resolve z-index: dev toolbar < login modal |
| P2 | Add safe-area-inset-top to toasts |
| P2 | Increase file remove button touch target |
| P2 | Add aria-label to desktop remove button |
| P3 | RTL/accessibility pass; extract inline JS to methods |

---

## Screenshots (from Playwright run)

- `test-results/new-order-desktop-initial.png`
- `test-results/new-order-desktop-tips-open.png`
- `test-results/new-order-desktop-card-expanded.png`
- `test-results/new-order-mobile-initial.png`
- `test-results/new-order-mobile-tips-open.png`
- `test-results/new-order-mobile-card-expanded.png`
صور (JPG, PNG, GIF, WebP)، PDF، Word، Excel — حتى 3 لكل منتج، 2 ميجابايت للملف

