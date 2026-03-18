// @ts-check
/**
 * Full E2E tests for the new-order flow.
 * Covers: guest → login modal, login → add item → submit → success, all layouts reachable.
 *
 * Prerequisites:
 * - Server running (Herd: wasetzonlaraval.test, or `php artisan serve` for localhost:8000)
 * - Database seeded: `php artisan db:seed` (creates customer@wasetzon.test / password)
 * - Run: npx playwright test tests/e2e/new-order-full-e2e.spec.js
 */
import { test, expect } from '@playwright/test';

const TEST_USER = { email: 'customer@wasetzon.test', password: 'password' };

test.describe('New Order — Full E2E', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await page.context().clearCookies();
    });

    test('all 7 layout URLs load without error', async ({ page }) => {
        const routes = [
            { path: '/new-order-cards', expectText: /create new order|إنشاء طلب جديد|طلب جديد|add.*product/i },
            { path: '/new-order-table', expectText: /create new order|إنشاء طلب جديد|طلب جديد|table/i },
            { path: '/new-order-hybrid', expectText: /create new order|إنشاء طلب جديد|طلب جديد/i },
            { path: '/new-order-wizard', expectText: /create new order|إنشاء طلب جديد|طلب جديد|wizard/i },
            { path: '/new-order-cart', expectText: /add to cart|cart|سلة|إضافة/i },
            { path: '/new-order-cart-inline', expectText: /create new order|إنشاء طلب جديد|طلب جديد|cart|سلة/i },
            { path: '/new-order-cart-next', expectText: /add to cart|checkout|سلة|إضافة|إتمام/i },
        ];

        for (const { path, expectText } of routes) {
            const response = await page.goto(path, { waitUntil: 'networkidle' });
            expect(response?.status()).toBe(200);
            await expect(page.locator('body')).toContainText(expectText, { timeout: 5000 });
        }
    });

    test('guest adding item and submitting opens login modal (Cards layout)', async ({ page }) => {
        await page.goto('/new-order-cards', { waitUntil: 'networkidle' });
        await page.locator('#items-container').waitFor({ state: 'visible', timeout: 10000 });

        // First card is expanded by default; fill URL
        const urlInput = page.locator('#items-container [data-field="url"] textarea').first();
        await urlInput.fill('https://example.com/product-e2e');
        await page.waitForTimeout(300);

        const submitBtn = page.locator('#submit-order');
        await submitBtn.click();

        await page.waitForTimeout(2000);
        const loginModal = page.locator('.order-login-modal-overlay');
        await expect(loginModal).toBeVisible({ timeout: 5000 });
    });

    test('logged-in user: login → Cards → add item → submit → success page', async ({ page }) => {
        await page.goto('/login', { waitUntil: 'networkidle' });
        await page.fill('#email', TEST_USER.email);
        await page.fill('#password', TEST_USER.password);
        await page.getByRole('button', { name: /log in|تسجيل الدخول|sign in/i }).click();

        await expect(page).not.toHaveURL(/\/login/, { timeout: 10000 });

        await page.goto('/new-order-cards', { waitUntil: 'networkidle' });
        await page.locator('#items-container').waitFor({ state: 'visible', timeout: 10000 });
        await page.waitForSelector('#items-container [data-field="url"] textarea', { state: 'visible', timeout: 10000 });

        const urlInput = page.locator('#items-container [data-field="url"] textarea').first();
        await urlInput.fill('https://example.com/e2e-full-flow');
        await page.waitForTimeout(200);

        const priceInput = page.locator('#items-container [data-field="price"] input').first();
        if (await priceInput.isVisible()) {
            await priceInput.fill('25');
        }

        const submitBtn = page.locator('#submit-order');
        await submitBtn.click();

        await page.waitForURL(/\/orders\/\d+(\/success)?/, { timeout: 15000 });
        const url = page.url();
        const onSuccessOrOrder = url.includes('/success') || /\/(orders\/\d+)$/.test(url);
        expect(onSuccessOrOrder).toBe(true);

        const body = page.locator('body');
        await expect(body).toContainText(/success|received|تم استلام|نجح/i, { timeout: 5000 });
    });

    test.skip('logged-in user: Cart Next layout → add to cart → submit → success', async ({ page }) => {
        await page.goto('/login', { waitUntil: 'networkidle' });
        await page.fill('#email', TEST_USER.email);
        await page.fill('#password', TEST_USER.password);
        await page.getByRole('button', { name: /log in|تسجيل الدخول|sign in/i }).click();

        await expect(page).not.toHaveURL(/\/login/, { timeout: 10000 });

        await page.goto('/new-order-cart-next', { waitUntil: 'networkidle' });
        await page.waitForTimeout(500);

        // Cart Next: URL, qty, price inputs in add-to-cart form
        const urlInput = page.getByPlaceholder(/paste product link|الصق رابط|رابط المنتج/i).first();
        await urlInput.fill('https://example.com/cart-next-e2e');
        await page.locator('input[placeholder="1"]').first().fill('2');
        await page.locator('input[inputmode="decimal"]').first().fill('19.99');
        await page.waitForTimeout(300);

        await page.getByRole('button', { name: /add to cart|إضافة إلى السلة|إضافة/i }).first().click();
        await page.waitForTimeout(2000);

        // Open cart drawer (Review Cart or Cart button)
        await page.getByRole('button', { name: /review cart|cart|سلة/i }).click();
        await page.waitForTimeout(500);

        const checkoutBtn = page.getByRole('button', { name: /checkout|إتمام الطلب|confirm order|تأكيد/i });
        await checkoutBtn.click();

        await page.waitForURL(/\/orders\/\d+(\/success)?/, { timeout: 15000 });
        const body = page.locator('body');
        await expect(body).toContainText(/success|received|تم استلام|نجح/i, { timeout: 5000 });
    });

    test('guest submit → login from modal → submit succeeds (Cards)', async ({ page }) => {
        await page.goto('/new-order-cards', { waitUntil: 'networkidle' });
        await page.locator('#items-container').waitFor({ state: 'visible', timeout: 10000 });

        const urlInput = page.locator('#items-container [data-field="url"] textarea').first();
        await urlInput.fill('https://example.com/guest-login-flow');
        await page.waitForTimeout(300);

        const submitBtn = page.locator('#submit-order');
        await submitBtn.click();

        await page.waitForTimeout(2000);
        const loginModal = page.locator('.order-login-modal-overlay');
        await expect(loginModal).toBeVisible({ timeout: 5000 });

        // Modal step 1: enter email, click Continue (modal has multiple forms with email — use visible one via order-modal-form.active)
        const emailInput = loginModal.locator('form.order-modal-form.active input[type="email"], form.order-modal-form input[type="email"]').first();
        await emailInput.fill(TEST_USER.email);
        await loginModal.getByRole('button', { name: /continue|متابعة/i }).first().click();
        await page.waitForTimeout(2000);

        // Modal step 2: password form appears (existing user), fill and log in (login form has current-password, register has new-password)
        await loginModal.locator('input[autocomplete="current-password"]').fill(TEST_USER.password);
        await loginModal.getByRole('button', { name: /log in|تسجيل الدخول|sign in/i }).first().click();

        await page.waitForTimeout(3000);
        await expect(loginModal).toBeHidden({ timeout: 5000 });

        // loginFromModal auto-calls submitOrder when reason is 'submit' — just wait for redirect
        await page.waitForURL(/\/orders\/\d+(\/success)?/, { timeout: 15000 });
        const body = page.locator('body');
        await expect(body).toContainText(/success|received|تم استلام|نجح/i, { timeout: 5000 });
    });
});
