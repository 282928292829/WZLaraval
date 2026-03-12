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

## Two Systems Running in Parallel
- **Customer-built system:** 4 existing layouts (current code — do not touch)
- **Claude-built system:** 5 new layouts built in parallel

Admin can switch between all layouts. If Claude's system wins, the customer-built layouts are deleted. All 5 Claude layouts are permanent — admin chooses which one is active at any time.

---

## Architecture — Non-Negotiable

### Shared (one place only)
- `NewOrder.php` — all server logic, validation, field config, file uploads, submission, guest draft
- `_item-fields.blade.php` — shared field HTML partial
  - Extract from Layout 1 (Hybrid) only — _order-item-table-row and _order-item-mobile-card. Exact same fields and naming as current site default.
  - Add a new field once in this partial, all 5 layouts inherit it
- Login modal partial — identical across all layouts
- Tips/hints partial — identical across all layouts

### Fully Isolated Per Layout
- One blade file per layout
- One Alpine component per layout (uniquely named e.g. `newOrderFormCards()`)
- Own submit footer HTML per layout
- Own item card/row HTML per layout

**No shared Alpine between layouts. No cross-layout @if branching. No mixing.**

---

## The 5 Layouts

| # | Name | Desktop | Mobile | URL |
|---|------|---------|--------|-----|
| 1 | Hybrid | Table | Cards | `/new-order-hybrid` |
| 2 | Table | Table | Table (horizontal scroll) | `/new-order-table` |
| 3 | Cards | Cards | Cards | `/new-order-cards` |
| 4 | Wizard | Step per item | Step per item | `/new-order-wizard` |
| 5 | Cart | Sidebar cart | Bottom-sheet cart | `/new-order-cart` |

---

## Layout-Specific Rules

### Layout 1 — Hybrid
- Breakpoint: `md` (768px) — table on desktop, cards on mobile
- Mobile: collapsible cards (same as Layout 3 mobile)

### Layout 2 — Table
- Full table on both desktop AND mobile
- No layout switching at any breakpoint — table always
- Mobile: horizontal scroll to see all columns
- Build fresh markup (new table structure, new row HTML) — do NOT copy current desktop table. Keep same fields and logic (via _item-fields, NewOrder, newOrderForm).

### Layout 3 — Cards ← Build First
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
- Do NOT silently restore (current behavior is wrong)
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
- Do NOT copy the current site look; design from scratch
- Keep brand primary (orange) for CTAs only; use neutral grays for cards
- Typography: smaller, lighter labels; bolder values
- Less "form", more "chat-like" — one thing at a time where possible

---

## Colors & Theming
- All layouts inherit site primary color from admin appearance settings via CSS variable
- No hardcoded colors anywhere in blade or Alpine

---

## Admin Control
- Admin switches the active layout from Filament settings panel
- All 5 Claude layouts remain permanently available — admin picks which one customers see
- Old customer-built layouts deleted only when explicitly approved by owner

---

## Build Order
1. Layout 3 — Cards (`/new-order-cards`) — start here
2. Layout 2 — Table (`/new-order-table`)
3. Layout 1 — Hybrid (`/new-order-hybrid`) — table desktop, cards mobile
4. Layout 4 — Wizard (`/new-order-wizard`)
5. Layout 5 — Cart (`/new-order-cart`)

---

## Pre-build (do before Layout 3)
1. Extract `_item-fields.blade.php` from Layout 1 (Hybrid) — use _order-item-table-row and _order-item-mobile-card as source. Same fields and naming exactly as current default layout.
2. Extract `_order-login-modal` and `_order-tips` as shared partials from existing new-order
3. Ensure `NewOrder.php` remains single source for validation, submission, `getOrderFormFields()`

*Note: Previous Claude-built `/new-order-cards` was removed. Rebuild from scratch.*

---

## Starting a New Chat Session
Paste this at the top of a new chat:

```
Read wasetzonlaraval/docs/NEW_ORDER_LAYOUTS_REQUIREMENTS.md in full before anything else.

Task: Build all 5 new order form layouts. Do not touch existing customer-built layouts.

Steps:
1. Pre-build: Extract _item-fields.blade.php, _order-login-modal, _order-tips from current Layout 1.
2. Build in order: Cards → Table → Hybrid → Wizard → Cart.

Each layout: own blade + own Alpine component. Shared: NewOrder.php, _item-fields, login modal, tips.
Logic (fields, validation, file uploads, calcTotals, saveDraft) lives in NewOrder + newOrderForm — keep it.

Start with pre-build, then Layout 3 (Cards).
```
