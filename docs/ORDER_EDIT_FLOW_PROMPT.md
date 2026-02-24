# Order Edit Flow — Implementation Prompt

**Status: IMPLEMENTED** (as of this session)

## Current State (Post-Implementation)
- Order show page has "Edit items" link → `/new-order?edit={id}` when within click window
- NewOrder component accepts `?edit={id}`, pre-fills items, sets `can_edit_until` on landing
- Submit updates existing order when `editingOrderId` is set
- Admin can enable/disable edit and set two time windows (Filament Settings → Order Rules)

## Requirements

### 1. Two Successive 10-Minute Windows
- **Window 1 (click):** Customer must click "Edit" within N minutes of order submission. After that, edit link disappears.
- **Window 2 (resubmit):** After clicking Edit, customer has N minutes to change items and resubmit.
- Both N values are admin-configurable (`order_edit_click_window_minutes`, `order_edit_resubmit_window_minutes`).

### 2. Admin Settings (already added)
- `order_edit_enabled` — Toggle to enable/disable order editing. When false, edit UI is hidden.
- `order_edit_click_window_minutes` — Minutes after submission to click Edit (default: 10).
- `order_edit_resubmit_window_minutes` — Minutes after clicking Edit to resubmit (default: 10).

### 3. NewOrder Component Changes

**mount():**
- Accept `?edit={id}` query param.
- If `edit` present and user can edit that order:
  - Load order + items.
  - Pre-fill `$this->items` (same structure as duplicate: url, qty, color, size, notes, currency, price).
  - Set `$this->orderNotes`.
  - Set `$this->editingOrderId = $order->id`.
  - Check: order must be unpaid, user is owner, within click window (`order->created_at + click_window_minutes > now()`).
- If outside click window or order paid → redirect back with error.

**submitOrder():**
- If `$this->editingOrderId` is set:
  - Validate resubmit window: `can_edit_until` (set when they clicked Edit) must be in future.
  - Update existing order: delete old items, create new from form, recalc subtotal/commission/total.
  - Set `can_edit_until = null` (edit consumed).
  - Add timeline entry + system comment.
  - Redirect to `/orders/{id}` with success flash.
- Else: create new order (current behavior).

### 4. initEditWindow() Changes
- **Current:** Sets `can_edit_until` on first order view.
- **New:** Do NOT set `can_edit_until` on first view. Set it only when user clicks "Edit" and lands on NewOrder with `?edit=`.
- NewOrder `mount()` when `edit` param present: set `can_edit_until = now() + resubmit_window_minutes` on the order (starts the second window).

### 5. OrderController show() Changes
- `canEditItems` logic:
  - Must have `order_edit_enabled` (Setting::get).
  - Order unpaid.
  - User is owner.
  - Within click window: `order->created_at + click_window_minutes > now()`.
  - No `can_edit_until` needed for showing the link (that's for the second window).
- Pass `orderEditEnabled` to view so edit UI is hidden when disabled.

### 6. UI
- When `order_edit_enabled` is false: hide the entire edit banner and link.
- When editing: show "Edit order #{number}" instead of "New order", button "Save changes".
- Translation keys for new strings.

### 7. Edge Cases
- Edit window expired → block submit, show error.
- Order paid → no edit.
- User doesn't own order → 403.
- Empty items on resubmit → reject or allow (recommend: reject with validation).

## Files to Modify
- `app/Livewire/NewOrder.php` — mount, submitOrder, initEditWindow usage
- `app/Http/Controllers/OrderController.php` — canEditItems, orderEditEnabled, initEditWindow call
- `resources/views/orders/show.blade.php` — wrap edit UI in `@if ($orderEditEnabled && $canEditItems)`
- `resources/views/livewire/new-order.blade.php` — edit mode title/button
- `lang/ar.json`, `lang/en.json` — new keys
