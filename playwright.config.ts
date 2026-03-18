// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * Base URL for E2E tests.
 * - Herd (macOS): http://wasetzonlaraval.test
 * - php artisan serve: http://localhost:8000
 * Set PLAYWRIGHT_BASE_URL to override.
 */
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://wasetzonlaraval.test';

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: process.env.CI ? 'github' : 'html',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'on-first-retry',
        actionTimeout: 15000,
        navigationTimeout: 30000,
    },
    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
        { name: 'mobile', use: { ...devices['Pixel 5'] } },
    ],
    outputDir: 'test-results/playwright-artifacts',
});
