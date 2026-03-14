# New Order Layouts — Requirements & Handoff

## How to Use This Doc
Read this before starting any new chat session on this feature.
Also read: `LARAVEL_PLAN.md`, `.cursor/rules/wasetzon.mdc`, `docs/ORDER_LAYOUTS.md`

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

## Current State

The old customer-built layouts (numbered 1–4) have been deleted. Only `new-order.blade.php` (the original responsive/hybrid layout) is kept temporarily as a **logic reference only** — do NOT copy its UI or look. It will be deleted once all 5 new layouts are complete.

### 5 New Claude-Built Layouts

Layout numbering is for reference only — build order is what matters.

| # | Name | Desktop | Mobile | URL | Status |
|---|------|---------|--------|-----|--------|
| 1 | Hybrid | Table | Cards | `/new-order-hybrid` | ❌ Not built |
| 2 | Table | Table | Table (horizontal scroll) | `/new-order-table` | ❌ Not built |
| 3 | Cards | Cards | Cards | `/new-order-cards` | ✅ Done |
| 4 | Wizard | Step per item | Step per item | `/new-order-wizard` | ❌ Rebuild from scratch |
| 5 | Cart | Sidebar cart | Bottom-sheet cart | `/new-order-cart` | ❌ Rebuild from scratch |

> **Note:** `new-order-wizard.blade.php` and `new-order-cart.blade.php` exist on disk but are old code — delete them and rebuild from scratch. Do not use them as a base.

---

## Architecture — Non-Negotiable

### Shared (one place only)
- `NewOrder.php` — all server logic, validation, field config, file uploads, submission, guest draft
- `_item-fields.blade.php` — shared field HTML partial (already extracted, lives in `livewire/partials/`)
  - Add a new field once in this partial, all 5 layouts inherit it automatically
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
- Logic reference: `new-order.blade.php` — for field/logic understanding only, not UI

### Layout 3 — Cards ✅ Done
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

### Layout 5 — Cart
- Desktop: sidebar panel (drawer from side) showing cart items, main form on left
- Mobile: bottom-sheet cart (drawer from bottom), thumb-reachable
- User adds items in main form, cart updates live

---

## Guest Draft Behavior
- Do NOT silently restore
- Show a prompt: "Last time you added some items — restore or start fresh?"
- Two clear buttons: **Restore** / **Start Fresh**

---

## Fields
- Same fields as current system for all 5 layouts
- Field config (optional/required/show/hide) driven by `NewOrder.php` via `getOrderFormFields()` — never hardcoded in blade
- Blades must use `orderFormFields` passed from `NewOrder::getOrderFormFields()`
- New fields added to `_item-fields.blade.php` once — all 5 layouts inherit automatically

---

## Shared Partials (use @include)
- `livewire.partials._order-login-modal` — identical login modal across all layouts
- `livewire.partials._order-tips` — identical tips/hints box across all layouts
- Do NOT copy; include. Single source of truth.

---

## Design Direction
- Fresh design — cleaner, neutral grays, more modern
- Do NOT copy the old `new-order.blade.php` look — design from scratch
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
- All 5 Claude layouts remain permanently available — admin picks which one customers see
- `new-order.blade.php` is the current `default` fallback — will be replaced by Cards once all 5 are done

---

## Build Order
1. ✅ Cards (`/new-order-cards`) — done
2. Table (`/new-order-table`) — **build next**
3. Hybrid (`/new-order-hybrid`) — table desktop, cards mobile
4. Wizard (`/new-order-wizard`) — rebuild from scratch
5. Cart (`/new-order-cart`) — rebuild from scratch

### Before building each layout
- Delete the old blade file if it exists (Wizard, Cart)
- Build fresh — own blade, own Alpine component, shared partials via @include

---

## Starting a New Chat Session
Paste this at the top of a new chat:

```
Read wasetzonlaraval/docs/NEW_ORDER_LAYOUTS_REQUIREMENTS.md in full before anything else.

Task: Build the remaining new order form layouts. Do not touch new-order.blade.php (old reference) or new-order-cards.blade.php (done).

Current status:
- Cards ✅ done (new-order-cards.blade.php)
- Table ❌ not built → build new-order-table.blade.php
- Hybrid ❌ not built → build new-order-hybrid.blade.php
- Wizard ❌ rebuild → delete old new-order-wizard.blade.php, build fresh
- Cart ❌ rebuild → delete old new-order-cart.blade.php, build fresh

Build order: Table → Hybrid → Wizard → Cart

Each layout: own blade + own Alpine component (uniquely named). Shared: NewOrder.php, _item-fields, _order-login-modal, _order-tips.
Logic lives in NewOrder.php — do not duplicate it in blade.
UI reference: new-order-cards.blade.php. Do NOT copy old new-order.blade.php UI.
```
