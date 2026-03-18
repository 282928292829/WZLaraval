# E2E Testing (Playwright)

Playwright tests for the new-order flow live in `tests/e2e/`. Run them against a live server.

## Prerequisites

- **Server running:** Laravel Herd (`wasetzonlaraval.test`) or `php artisan serve` (then set `PLAYWRIGHT_BASE_URL=http://localhost:8000`)
- **Database seeded:** `php artisan db:seed` (creates `customer@wasetzon.test` / `password`)

## Commands

| Command | Description |
|---------|-------------|
| `npm run e2e` | Run all E2E tests |
| `npm run e2e:ui` | Run with Playwright UI |
| `npx playwright test tests/e2e/new-order-full-e2e.spec.js` | Full flow only |
| `npx playwright test tests/e2e/new-order-visual.spec.js` | Visual/UX audit only |

## Base URL

Set `PLAYWRIGHT_BASE_URL` to override. Default: `http://wasetzonlaraval.test` (Herd).

## Specs

- **new-order-full-e2e.spec.js** — Full user flows: guest → login modal, login → add item → submit → success, all layouts reachable, guest login-from-modal → submit, Cart Next add-to-cart flow.
- **new-order-visual.spec.js** — Visual/UX audit: layout, overflow, buttons, tips, Paste/Open, card expand, attach/login modal.
