# New Order Layouts — Reference

## How to Use This Doc
This doc describes **what exists today** — layouts, architecture, rules, and where things live. Use it when working on the new-order feature.
Also read: `LARAVEL_PLAN.md`, `.cursor/rules/wasetzon.mdc`

---

## Current State (Overview)
All 7 layouts exist. The admin chooses which one customers see at `/new-order` via Filament settings. Direct routes (`/new-order-cards`, `/new-order-cart-next`, etc.) always show that specific layout. Unknown layout keys fall back to Hybrid.

---

## Project Context

### Tech Stack (use exactly these — no substitutions)
- **PHP** 8.4
- **Laravel** 12
- **Livewire** 3
- **Alpine.js** v3
- **Tailwind CSS** v4
- **Filament** v4
- **MySQL** 8
- **Redis**
- **Spatie Permission**
- **Laravel Breeze**

### Project Rules
- Production rebuild — correctness and long-term architecture over speed
- 90% mobile users — mobile-first always
- Bilingual Arabic/English — every string through `__()`
- Admin configures colors, fields, and active layout via Filament settings

---

### Layouts (7)

| Layout | URL | Notes |
|--------|-----|-------|
| Cards | `/new-order-cards` | Each item = one card |
| Table | `/new-order-table` | Table on desktop and mobile |
| Hybrid | `/new-order-hybrid` | Table on desktop, cards on mobile |
| Wizard | `/new-order-wizard` | Step per item |
| Cart | `/new-order-cart` | Sidebar/bottom-sheet cart |
| Cart Inline | `/new-order-cart-inline` | Inline cart variant |
| Cart Next | `/new-order-cart-next` | Drawer, inline edit, image thumbnails (primary cart) |

---

## Architecture — Non-Negotiable

### Shared (one place only)
- `NewOrder.php` — all server logic, validation, field config, file uploads, submission, guest draft
- `_item-fields.blade.php` — shared field HTML partial (already extracted, lives in `livewire/partials/`)
  - Add a new field once in this partial, layouts that use it inherit automatically
- `livewire.partials._order-login-modal` — identical login modal across all layouts (already extracted)
- `livewire.partials._order-tips` — identical tips/hints box across all layouts (already extracted)

### Fully Isolated Per Layout
- One blade file per layout
- One Alpine component per layout (uniquely named e.g. `newOrderFormTable()`, `newOrderFormHybrid()`)
- Own submit footer HTML per layout — design, look, and feel must be independent per layout, not a copy of a shared bar
- Own item card/row HTML per layout

**No shared Alpine between layouts. No cross-layout @if branching. No mixing.**
**Submit footer is NOT a shared partial — each layout owns its footer design.**

---

## Layout-Specific Rules

### Layout 1 — Table
- Full table on both desktop AND mobile
- No layout switching at any breakpoint — table always
- Mobile: horizontal scroll to see all columns
- Fresh markup — do NOT copy old desktop table. Use `_item-fields`, `NewOrder`, `newOrderFormTable()`.

### Layout 2 — Hybrid
- Breakpoint: `md` (768px) — table on desktop, cards on mobile
- Mobile: collapsible cards (same behavior as Cards layout)
- Logic: `NewOrder.php` and `_item-fields.blade.php` for field config and behavior

### Layout 3 — Cards
- Each item = one card
- Collapsed card must fit without scroll; expanded card may scroll on small viewports if needed
- Adding a new item collapses the previous card
- Collapsed cards stay visible so customer always sees their item count
- New card expands and fits on screen without scrolling when possible
- Mobile-first design priority

### Layout 4 — Wizard
- One step = one item
- Persistent mini-summary always visible (e.g. "3 items added") — user never feels lost
- Final steps: Order Notes → Review all items → Submit
- Back navigation must work — user can go back and edit any item

### Layout — Cart
- Desktop: sidebar panel (drawer from side) showing cart items, main form on left
- Mobile: bottom-sheet cart (drawer from bottom), thumb-reachable
- User adds items in main form, cart updates live

### Layout — Cart Inline
- Inline cart variant (no drawer)

### Layout — Cart Next (primary cart)
- Drawer from left (EN) or right (AR/RTL)
- Inline edit in drawer: `syncItemEdits`, `wire:model.blur`
- Image thumbnails in cart item cards; click to zoom
- Draft restore: Restore / Start Fresh prompt

---

## Guest Draft Behavior
- Do NOT silently restore
- Show a prompt: "Last time you added some items — restore or start fresh?"
- Two clear buttons: **Restore** / **Start Fresh**

---

## Fields
- Same fields as current system for all layouts
- Field config (optional/required/show/hide) driven by `NewOrder.php` via `getOrderFormFields()` — never hardcoded in blade
- Blades must use `orderFormFields` passed from `NewOrder::getOrderFormFields()`
- New fields added to `_item-fields.blade.php` once — layouts that use it inherit automatically

---

## Paste & Open Buttons

**Goal:** All layouts should show Paste/Open where it fits the UI. Use Cards layout as the placement reference.

**Behavior:**
- **URL field:** Paste | Open. Paste fills from clipboard. Open opens URL in new tab, or Google search if not a URL. Empty URL + Open → toast: `order_form.no_link_to_open`.
- **Other fields (Color, Size, Qty, Price, Notes):** Paste only.
- **Currency:** No Paste (layout spacer for alignment only).
- **Translations:** `order_form.paste`, `order_form.pasted`, `order_form.open`, `order_form.opened_search`, `order_form.no_link_to_open`.

**Placement (same as Cards):** Inline after label and optional text — `label (اختياري) لصق | فتح` for URL, `label (اختياري) لصق` for others. Use `text-[11px] text-slate-400` for secondary styling. No `justify-between`; inline flow only.

**Per layout:**

| Layout | Uses `_item-fields`? | Paste/Open |
|--------|---------------------|------------|
| Cards | Yes | ✅ Pass `showUrlPasteOpen => true` |
| Wizard | Yes | Pass `showUrlPasteOpen => true` |
| Cart Inline | Yes | Pass `showUrlPasteOpen => true` |
| Hybrid (mobile) | Yes | Pass `showUrlPasteOpen => true` |
| Table | No (custom cells) | Deferred — refactor to `_item-fields` first, or add compact variant later |
| Cart | No (custom form) | Deferred — different data model (`currentItem`); add when refactored |
| Cart Next | No (custom form) | Deferred — same as Cart |

---

## Shared Partials (use @include)
- `livewire.partials._order-login-modal` — identical login modal across all layouts
- `livewire.partials._order-tips` — identical tips/hints box across all layouts
- Do NOT copy; include. Single source of truth.

---

## Design Direction
- Fresh design — cleaner, neutral grays, more modern
- Design from scratch — use Cards layout as the visual reference
- Keep brand primary (orange) for CTAs only; use neutral grays for cards
- Typography: smaller, lighter labels; bolder values
- Less "form", more "chat-like" — one thing at a time where possible
- Use Cards layout (`new-order-cards.blade.php`) as the visual/UX reference for new builds

---

## Colors & Theming
- All layouts inherit site primary color from admin appearance settings via CSS variable
- No hardcoded colors anywhere in blade or Alpine

---

## Admin Control

- Admin switches the active layout from Filament settings panel
- All 7 layouts remain available — admin picks which one customers see at `/new-order`

For E2E testing instructions, see `docs/E2E_TESTING.md`.
