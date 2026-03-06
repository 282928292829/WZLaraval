# Prompt: Build Cart System (Option 2) for New Order Form

---

## COPY THIS INTO A NEW CHAT

```
Build the cart system (Option 2) for the /new-order page in this Laravel app.

Project: /Users/abdul/Desktop/Wasetzon/wasetzonlaraval
Read full spec: wasetzonlaraval/CART_SYSTEM_PROMPT.md

Summary: Add products one-by-one to cart instead of multi-row table. Add addToCart() to NewOrder, create livewire/new-order-cart.blade.php, branch render() when order_new_layout is '2'. Reuse submitOrder(), buildExchangeRates(), emptyItem(), login modal from NewOrder. UI: copy from /Users/abdul/Desktop/Chrome Download Folder/Bersonal (NewOrder.tsx, CartDrawer.tsx, CartContext.tsx). Use layouts.order. RTL + Arabic. Mobile-first.
```

---

## Full Spec (for reference)

---

## What to Build

**Option 2: Cart system** — A different UX for the new-order form where users add products one-by-one to a cart instead of filling a multi-row table.

- **Flow:** Fill single add-product form → click "Add to Cart" → item goes into cart. Repeat. Cart icon in header shows count. Review cart → Submit order.
- **Same fields per product:** URL, qty, color, size, price, currency, notes, file upload (1 per product).
- **Same business logic:** Validation, commission calculation, exchange rates, localStorage draft, login modal, limits (30 products for customers).

---

## Project Context

- **Laravel 11 + Livewire 3** app at `wasetzonlaraval/`
- **Route:** `/new-order` — single Livewire component `App\Livewire\NewOrder`
- **Layout:** `layouts.order` — uses site theme, RTL for Arabic, IBM Plex Arabic font
- **Admin setting:** Filament Settings → Order Rules → "New-Order Form Layout" — value `2` = Cart system (exists but does nothing today)

---

## UI/UX Reference — Bersonal Source

**Copy cart layout from:** `/Users/abdul/Desktop/Chrome Download Folder/Bersonal`

Key files:
- `client/src/pages/NewOrder.tsx` — new-order page with add-to-cart form
- `client/src/components/CartDrawer.tsx` — cart drawer UI
- `client/src/contexts/CartContext.tsx` — cart state logic
- `new-order-standalone.html` — standalone HTML version

1. **Header:** Title "New Order" + "Add products one by one" subtitle on left; **Cart button** on right with icon + badge count `(0)`
2. **Add-product form (single card):**
   - Row 1: Product URL (text input)
   - Row 2: Qty, Color, Size (3 columns)
   - Row 3: Price, Currency (2 columns)
   - Row 4: Notes (compact textarea)
   - Row 5: File upload (optional)
   - **Add to Cart** button (full width)
3. **Cart section:** List of added items — each showing product summary, edit/remove — and "Submit order" when cart has items

**Translations:** Use `order_form.*` keys; `bersonal.*` exist in `lang/ar.json` and `lang/en.json` (e.g. `bersonal.cart`, `bersonal.add_to_cart`) if needed. Map to `order_form.*` for consistency.

**Styling:** Use existing `layouts.order` and app CSS — Tailwind, primary colors, RTL.

---

## Backend Logic — Where to Copy From

**Source:** `app/Livewire/NewOrder.php`

**Reuse (do NOT duplicate):**

| What | Where | Notes |
|------|-------|-------|
| Submit & validation | `submitOrder()` | Creates Order, OrderItems, OrderTimeline, handles commission |
| Exchange rates | `buildExchangeRates()` | Private method |
| Currencies | `order_form_currencies()` | Helper in `app/Support/helpers.php` |
| Empty item shape | `emptyItem()` | Returns `['url','qty','color','size','price','currency','notes']` |
| Guest login modal | `showLoginModal`, `loginModalReason`, modal steps | Same flow — prompt login before submit |
| localStorage draft | `x-data="newOrderForm(...)"` in current view | Guest draft — adapt for cart items |
| Image upload | `WithFileUploads`, `itemFiles` | Per-item images; cart adds one item at a time |
| Max products | `$maxProducts` | 30 for customer |
| Commission calculation | `CommissionCalculator::calculate()` | `app/Services/CommissionCalculator.php` |

**New method to add:**

- `addToCart()` — Validate current form row has at least URL or price. Push to `$items`. Clear the single "current item" form. Reset to `emptyItem()` with last currency. Do NOT submit.

---

## Layout Toggle

**In `NewOrder::render()` (around line 997):**

```php
$layout = Setting::get('order_new_layout', '1');
$viewName = match ($layout) {
    '2' => 'livewire.new-order-cart',   // ADD THIS
    '4' => 'livewire.new-order-wizard',
    default => 'livewire.new-order',
};
```

When admin sets "Option 2 — Cart system", render `livewire.new-order-cart` instead of `livewire.new-order`.

---

## Key File Paths

| Purpose | Path |
|---------|------|
| Livewire component | `app/Livewire/NewOrder.php` |
| Current form view (Option 1) | `resources/views/livewire/new-order.blade.php` |
| **New cart view to create** | `resources/views/livewire/new-order-cart.blade.php` |
| Bersonal cart source | `/Users/abdul/Desktop/Chrome Download Folder/Bersonal` — NewOrder.tsx, CartDrawer.tsx, CartContext.tsx |
| Layout | `resources/views/layouts/order.blade.php` |
| Admin Settings (order_new_layout) | `app/Filament/Pages/OrderSettingsPage.php` |
| Helpers | `app/Support/helpers.php` |

---

## What to Expect (Deliverables)

1. **New view** `livewire/new-order-cart.blade.php` — Cart layout with:
   - Single add-product form + Add to Cart button
   - Cart icon with count in header (or top bar)
   - Cart list/drawer with items (remove, optional edit)
   - Order notes field
   - Submit order button (calls existing `submitOrder`)
   - Real-time subtotal/commission when all items have price+qty
   - Same login modal for guests

2. **Updated `NewOrder::render()`** — Branch to cart view when `order_new_layout === '2'`

3. **New `addToCart()` method** in NewOrder — Adds current form data to `$items`, clears form

4. **Wire bindings** — One "current item" form (url, qty, color, size, price, currency, notes) bound to temp state or a single slot; `addToCart` validates, pushes to `$items`, resets

5. **Testable flow:** Admin → Settings → set layout to Option 2 → visit `/new-order` → see cart UX → add products → submit → order created

---

## Constraints

- **Same `$items` structure** as Option 1 — array of `['url','qty','color','size','price','currency','notes']` so `submitOrder()` works unchanged
- **RTL + Arabic** — Layout must work for `dir="rtl"`
- **Mobile-first** — Cart drawer/list responsive
- **No new routes** — All in existing `/new-order` route
- **Reuse `order_form.*` or `bersonal.*`** translations — add keys if missing

---

## To Start New Chat

1. Open this file (CART_SYSTEM_PROMPT.md)
2. Copy the block under "COPY THIS INTO A NEW CHAT" (the ```...``` block)
3. Paste into a new chat — AI will read this file for full spec
