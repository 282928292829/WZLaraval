# Old New Order — Reference Layout for Building the 5 New Layouts

**When creating the 5 new layouts (Hybrid, Table, Cards, Wizard, Cart), use this page as the visual and UX reference.**

---

## URL

**http://wasetzonlaraval.test/old-new-order**

---

## What It Is

A standalone copy of the **old responsive layout** (formerly Option 1) — the layout that was used one week before the 5 new layouts were introduced. It is fully functional (submit, edit, duplicate, file uploads, validation, etc.) but fixed to this single layout for AI and human reference.

---

## How It Was Built

1. **`OldNewOrder` Livewire component** (`app/Livewire/OldNewOrder.php`) — extends `NewOrder`, overrides:
   - `mount()` — forces `activeLayout = '1'`
   - Add Product = gradient button (default, matches original)
   - Uses `#[Layout('layouts.order')]` (with footer) — not `layouts.order-focused` (no footer)

2. **`/old-new-order` route** (`routes/web.php`) — maps to `OldNewOrder` with `role.throttle:new-order` middleware.

3. **Add Product control** — Old layout uses the default **gradient button** (full-width) for Add Product on both desktop and mobile — matches original Mar 7 UX.

---

## Layout Characteristics (What the 5 New Layouts Should Match or Improve On)

| Aspect | Old Layout (Reference) |
|--------|------------------------|
| **Desktop** | HTML table with columns: #, Product Link, Color, Size, Qty, Price, Currency, Notes, Attachments. Sticky header. Horizontal scroll on small desktops. |
| **Mobile** | Collapsible cards per item. Same fields, different arrangement. |
| **Add product** | Full-width gradient button on both desktop and mobile. |
| **General notes** | Below add-product. Textarea, optional. "Reset all" link alongside. |
| **Wrapper layout** | `layouts.order` (header + nav + footer). Footer shows minimal copyright + language link. |
| **Fixed footer** | Sticky bar: product count, total value, Submit Order button. |

---

## What to Reference When Building the 5 New Layouts

1. **Visit** `http://wasetzonlaraval.test/old-new-order` to see the exact structure, fields, and UX.
2. **Fields** — All item fields (URL, color, size, qty, price, currency, notes, attachments) are defined in `_item-fields.blade.php` and used via `getOrderFormFields()`. No hardcoding.
3. **Logic** — All logic lives in `NewOrder.php`. The 5 new layouts should use the same `NewOrder` component or a thin wrapper; layout-specific blades only change the visual presentation.
4. **Shared partials** — `_order-login-modal`, `_order-tips`, `_item-fields` — reuse these, do not duplicate.
5. **Add product style** — The old layout uses the gradient button. Reference shows the original choice.

---

## When to Delete

Once the 5 new layouts are complete and no longer need this reference:

- Delete `app/Livewire/OldNewOrder.php`
- Delete the `/old-new-order` route from `routes/web.php`
- Remove the `addProductAsLink` conditionals from `new-order.blade.php` if no other layout uses link-style (or keep for flexibility)

---

## Related Docs

- `docs/NEW_ORDER_LAYOUTS_REQUIREMENTS.md` — Requirements for the 5 layouts
- `docs/ORDER_LAYOUTS.md` — Current layout options and config
