# Wasetzon Refactor — Handoff State File

Update the STATUS section after completing each phase. Never modify the PLAN sections.

---

## Project Context

- Laravel 12, Livewire 3, Alpine.js v3, Tailwind v4, Filament 4, Spatie Permission
- Bilingual: Arabic (default) + English. Every user-facing string must use __()
- DB: SQLite locally. Run `php artisan db:seed` after fresh migrate.
- Project path: `wasetzonlaraval/`
- Live WP site still running — no urgency, but this must be complete and polished before launch
- Codebase will be reused across ~20 sites: must be clean, testable, no shortcuts
- Read `docs/NEW_ORDER_LAYOUTS_REQUIREMENTS.md` before touching any layout files
- Read `wasetzon.mdc` and `LARAVEL_PLAN.md` at the start of every session
- Run `vendor/bin/pint --dirty --format agent` after every change
- Run `php artisan test --compact` after every phase
- Commit atomically after each phase passes tests

---

## How to Use This File

1. At the start of every new Claude session, paste this starter message and attach this file:

```
Read wasetzon.mdc, LARAVEL_PLAN.md, and docs/REFACTOR_HANDOFF.md fully before doing anything.
REFACTOR_HANDOFF.md contains the project state and your current task.
Do not start until you confirm which phase is CURRENT and what is done.
When you finish, update the STATUS section in REFACTOR_HANDOFF.md to reflect what was completed.
```

2. Claude reads the STATUS section to know where you are, does the current phase, updates STATUS when done.

---

## STATUS (AI updates this section after each phase)

**Last updated by:** AI — 2026-03-19
**Current phase:** Phase 7
**Completed phases:** Phase 1, Phase 2, Phase 3, Phase 4, Phase 5, Phase 6

### Phase completion log
- [x] Phase 1 — Paste/Open on all 7 layouts *(Cards ✅, Wizard ✅, Cart Inline ✅, Hybrid ✅, Table ✅, Cart ✅, Cart Next ✅)*
- [x] Phase 2 — GuestLoginModal Livewire component ✅
- [x] Phase 3 — OrderItemFileHelper trait ✅
- [x] Phase 4 — OrderSubmissionService ✅
- [x] Phase 5 — NewOrderCart split ✅
- [x] Phase 6 — OrderController split ✅
- [ ] Phase 7 — Cleanup

### Notes from last session
- Phase 6 complete — OrderController split
- Created `OrderInvoiceController`: generateInvoice, invoiceSettings, buildInvoiceExtra, buildInvoiceLinesForLocale, buildInvoicePdf, resolveInvoiceFilename, resolveInvoiceNumber, sanitizeFilename, sanitizeFilenamePart, resolveInvoiceCommentMessage
- Created `OrderExportController`: exportCsv, exportExcel
- Routes: POST /orders/{order}/invoice → OrderInvoiceController, GET /orders/{order}/export-excel → OrderExportController
- OrderController::allOrders delegates CSV export to OrderExportController when ?export=csv
- invoiceDefaultsForOrder retained in OrderController (used by show())
- 28 Order tests pass; 5 pre-existing failures unchanged

### Notes from previous session
- Branch: `refactor/new-order`
- Phase 2 complete — commit `158e7c5`
- Created `app/Livewire/GuestLoginModal.php` — moves all 9 modal properties and 7 modal methods out of NewOrder
- Created `resources/views/livewire/guest-login-modal.blade.php` — markup moved from `_order-login-modal.blade.php`
- `GuestLoginModal` listens for `open-login-modal` browser event via `#[On('open-login-modal')]`; emits `user-logged-in` with `reason` payload on success
- `NewOrder::submitOrder()` dispatches `open-login-modal` (reason=submit) instead of setting showLoginModal directly
- `NewOrder::openLoginModalForAttach()` dispatches `open-login-modal` (reason=attach)
- `NewOrder` has `#[On('user-logged-in')]` handler — calls `submitOrder()` when reason=submit
- All 7 layout blades: `@include('livewire.partials._order-login-modal')` → `@livewire('guest-login-modal')` (Hybrid had 2 includes; consolidated to 1)
- Tests updated: `NewOrderCartHttpTest` and `NewOrderCartLayoutTest` — `assertSet('showLoginModal', true)` → `assertDispatched('open-login-modal')`; modal method tests moved to `GuestLoginModal::class`
- 29 NewOrder tests pass; 5 pre-existing failures unchanged (HourlyOrderRateLimit, OrderEditFlow ×3, OrderItemFilesUpload)

---

## PLAN

### Phase 1 — Paste/Open on All 7 Layouts
**Risk: Zero. Purely additive. No logic changes.**

Key files:
- `resources/views/livewire/partials/_item-fields.blade.php` — Cards/Wizard/Cart Inline/Hybrid already work, do not touch
- `resources/views/livewire/new-order-table.blade.php`
- `resources/views/livewire/new-order-cart.blade.php`
- `resources/views/livewire/new-order-cart-next.blade.php`
- `resources/views/livewire/partials/_new-order-form-js.blade.php` — paste/open functions already here

Tasks:

1. **Create** `resources/views/livewire/partials/_url-paste-open.blade.php`
   - Accepts `$mode` (`'items'` | `'current'`) and `$idx` (items mode only)
   - `'items'` mode: buttons call `doPasteForItem(idx, $event)` and `doOpenForItem(idx)`
   - `'current'` mode: buttons call `doPasteCurrentItem($event)` and `doOpenCurrentItem()`
   - Match Cards layout placement: inline after label — `label (اختياري) لصق | فتح`
   - Style: `text-[11px] text-slate-400`, inline flow, no `justify-between`
   - All strings via `__()`: `order_form.paste`, `order_form.pasted`, `order_form.open`, `order_form.opened_search`, `order_form.no_link_to_open`
   - Feedback state: `pasteFeedbackIdx`/`pasteFeedbackField` (items mode), `currentItemPasteFeedback` (current mode)

2. **Table layout** (`new-order-table.blade.php`):
   - Add `:data-item-idx="idx"` `data-field="url"` to the URL `<td>`
   - Add `data-field` attributes to other field `<td>`s (color, size, qty, price, notes)
   - `@include` partial with `mode='items'` above the URL textarea inside the `<td>`
   - No JS changes needed — `newOrderFormTable()` already spreads `newOrderForm()` which has all paste functions

3. **Cart layout** (`new-order-cart.blade.php`):
   - Add to `newOrderFormCart()` Alpine object:
     ```js
     currentItemPasteFeedback: null,
     noLinkToOpenMsg: @js(__('order_form.no_link_to_open')),
     pasteLabel: @js(__('order_form.paste')),
     pastedLabel: @js(__('order_form.pasted')),
     openLabel: @js(__('order_form.open')),
     openedLabel: @js(__('order_form.opened')),
     doPasteCurrentItem(ev) {
         if (!navigator.clipboard?.readText) return;
         navigator.clipboard.readText().then(t => {
             const text = t.trim();
             if (!text) return;
             this.$wire.set('currentItem.url', text.slice(0, 2000));
             this.currentItemPasteFeedback = 'pasted';
             setTimeout(() => { this.currentItemPasteFeedback = null; }, 1500);
         }).catch(() => {});
     },
     doOpenCurrentItem() {
         // same URL-vs-Google logic as doOpenForItem in _new-order-form-js.blade.php
         // read currentItem.url via this.$wire.get('currentItem.url')
     },
     ```
   - `@include` partial with `mode='current'` in URL field label area

4. **Cart Next layout** (`new-order-cart-next.blade.php`):
   - Same Alpine additions to `newOrderFormCartNext()`
   - `@include` partial with `mode='current'` in URL field label area

**Success criteria:**
- All 7 layouts show Paste | Open on URL field
- Paste fills the field and shows feedback (`__('order_form.pasted')`)
- Open opens URL or falls back to Google search
- No regressions on existing layouts (Cards, Wizard, Cart Inline, Hybrid)

---

### Phase 2 — GuestLoginModal Livewire Component
**Risk: Low. Isolated extraction.**

Key files:
- `app/Livewire/NewOrder.php` lines 71–88 (properties), 1134–1255 (methods)
- `resources/views/livewire/partials/_order-login-modal.blade.php`

Tasks:

1. **Create** `app/Livewire/GuestLoginModal.php`
   - Properties to move: `showLoginModal`, `loginModalReason`, `modalStep`, `modalEmail`, `modalPassword`, `modalPasswordConfirm`, `modalError`, `modalSuccess`
   - Methods to move: `checkModalEmail`, `loginFromModal`, `registerFromModal`, `sendModalResetLink`, `setModalStep`, `openLoginModalForAttach`, `closeModal`
   - Events emitted: `'user-logged-in'`, `'modal-closed'`
   - Listens for: `'open-login-modal'` dispatched by parent

2. **Create** `resources/views/livewire/guest-login-modal.blade.php`
   - Move markup from `_order-login-modal.blade.php`

3. **In all 7 layout blade files:**
   - Replace `@include('livewire.partials._order-login-modal')` with `@livewire('guest-login-modal')`

4. **In `NewOrder.php`:**
   - Remove all moved properties and methods
   - `openLoginModalForAttach()` becomes: `$this->dispatch('open-login-modal', reason: 'attach')`
   - Add `#[On('user-logged-in')]` listener that handles post-login action

**Success criteria:** Login modal works on all layouts, tests pass.

---

### Phase 3 — OrderItemFileHelper Trait
**Risk: Very low. Pure PHP move.**

Key files:
- `app/Livewire/NewOrder.php` lines 515–635

Tasks:

1. **Create** `app/Livewire/Concerns/HandlesOrderItemFiles.php`
   - Methods to move:
     - `removeItemFile()` (line 515)
     - `shiftFileIndex()` (line 526)
     - `normalizeItemFiles()` (line 542)
     - `getItemFilePreviews()` (line 563)
     - `checkFileLimits()` (line 613)

2. **`NewOrder.php`:** add `use HandlesOrderItemFiles;` and remove moved methods.

**Success criteria:** All file operations work, tests pass.

---

### Phase 4 — OrderSubmissionService
**Risk: High. Prerequisite for Phase 5. Take your time.**

Key files:
- `app/Livewire/NewOrder.php` lines 636–1061

Tasks:

1. **Create** `app/DTOs/OrderSubmissionData.php`
   ```php
   // Properties: userId, items, orderNotes, normalizedFiles, exchangeRates,
   // maxImagesPerItem, maxImagesPerOrder, editingOrderId, duplicateFrom,
   // productUrl, activeLayout
   ```

2. **Create** `app/DTOs/OrderSubmissionResult.php`
   ```php
   // Properties: success (bool), orderId (?int), redirectUrl (?string),
   // errorMessage (?string), errorType (?string: 'notify'|'redirect')
   ```

3. **Create** `app/Services/OrderSubmissionService.php`
   - Move from `submitOrder()` / `submitOrderEdit()`: rate limit checks, DB transaction, Order/OrderItem creation, file moves, OrderFile records, timeline entry, `insertSystemComment()`, `insertDevComments()`
   - **CRITICAL RULES:**
     - Service NEVER calls `dispatch()`, `redirect()`, or `$this->anything`
     - Service only returns `OrderSubmissionResult`
     - `insertDevComments` must be gated: `config('app.env') === 'local'` only

4. **`NewOrder::submitOrder()`** becomes:
   ```
   validate() → normalizeItemFiles() → build OrderSubmissionData
   → OrderSubmissionService::submit() → handle result (dispatch or redirect)
   ```

5. **Write** `tests/Unit/OrderSubmissionServiceTest.php`
   - Test: new order creation, edit flow, rate limit enforcement

**Success criteria:** All submission paths work, unit tests pass, no behavioral change.

---

### Phase 5 — NewOrderCart Split
**Risk: Medium. Phase 4 must be complete first.**

Key files:
- `app/Livewire/NewOrder.php` (full file)
- `routes/web.php`

Tasks:

1. **Create** `app/Livewire/Concerns/HasNewOrderBase.php` trait
   - Move: `resolveLayout()`, `prefillFromEdit()`, `prefillFromDuplicate()`, `emptyItem()`, `generateOrderNumber()`, `validationRules()`, `buildExchangeRates()`, `getOrderFormFields()`, `renderTermsTemplate()`
   - Shared mount setup logic (pre-layout init)

2. **Create** `app/Livewire/NewOrderCart.php`
   - Uses: `WithFileUploads`, `HandlesOrderItemFiles`, `HasNewOrderBase`
   - Move: `currentItem`, `currentItemFiles` properties, `addToCart()`, `editCartItem()`, `clearAllItems()`, `loadGuestDraftFromStorage()`, `getCartSummary()`, `renderTermsTemplate()`
   - `#[Layout('layouts.order-focused')]`
   - `submitOrder()` calls `OrderSubmissionService` with cart-specific data

3. **Create** `app/Http/Controllers/NewOrderController.php`
   - Single `__invoke`: reads `activeLayout` from settings
   - If layout is `cart` or `cart-next`: render `NewOrderCart` component view
   - Otherwise: render `NewOrder` component view

4. **Update** `routes/web.php`:
   - `/new-order` → `NewOrderController`
   - `/new-order-cart` → direct `NewOrderCart` livewire view
   - `/new-order-cart-next` → direct `NewOrderCart` livewire view

5. **`NewOrder.php` after split target: 400–550 lines**

**Success criteria:**
- All 7 layout routes work: `/new-order`, `/new-order-cards`, `/new-order-table`, `/new-order-hybrid`, `/new-order-wizard`, `/new-order-cart-inline`, `/new-order-cart`, `/new-order-cart-next`
- Submit works on all layouts
- Tests pass

---

### Phase 6 — OrderController Split
**Risk: Low. Independent of Phases 1–5.**

Key files:
- `app/Http/Controllers/OrderController.php` (~1,041 lines)
- `docs/ORDER_CONTROLLER_REFACTOR_PROMPT.md` — **read this file in full, it has the complete task description**

Tasks:
- Extract `OrderInvoiceController` (`generateInvoice`, `invoiceSettings`, `buildInvoicePdf`, `resolveInvoiceCommentMessage`) — ~302 lines
- Extract `OrderExportController` (`exportCsv`, `exportExcel`) — ~72 lines
- Update `routes/web.php` accordingly

Follow exactly what `ORDER_CONTROLLER_REFACTOR_PROMPT.md` says.

**Success criteria:** All order routes still work, tests pass (`php artisan test --compact --filter=Order`).

---

### Phase 7 — Cleanup
**Risk: Very low. Do last.**

Tasks:

1. **`insertDevComments`**: move to a seeder or factory (`database/factories/`), remove from production `NewOrder.php` entirely.

2. **Hardcoded strings not through `__()`** — fix all of these:
   - `'Create new order'` in `new-order-table.blade.php`, `new-order-cart.blade.php`, `new-order-cart-next.blade.php`
   - Add key `order_form.create_new_order` to `lang/ar.json` and `lang/en.json`

3. **Duplicate `showNotify()`** in Cart and Cart Next Alpine components:
   - Both define their own `showNotify()` instead of using the shared `newOrderForm()` base
   - Extract to `Alpine.data('orderNotify', ...)` in `resources/js/app.js`
   - Both components spread it

4. **Duplicate `peekDraft` / `restoreDraft`** across Table, Cart, Cart Next Alpine:
   - Extract to `Alpine.data('orderDraftMixin', ...)` in `resources/js/app.js`
   - All three components spread it

5. **Final:** run full test suite `php artisan test --compact`, fix any failures.

---

## Key Architecture Decisions (do not reverse these)

- `_item-fields.blade.php` is for items-based layouts (Alpine `items[]` array model) only — never force cart layouts to use it
- Cart and Cart Next use `wire:model="currentItem.*"` — this is intentional and load-bearing (Livewire file upload streaming)
- `OrderSubmissionService` must never call `dispatch()` or `redirect()` — Livewire calls stay in the component
- One phase per AI session — never combine phases
- All strings through `__()`, keys in both `lang/ar.json` and `lang/en.json`
- No new base folders without approval
