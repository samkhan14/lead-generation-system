import { test, expect } from '@playwright/test';
import { assertDataTable, assertPageReady, login, openProfile, visitSidebar } from './helpers/auth';

test.describe.serial('Materio UI — module smoke tests', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('01 Dashboard renders', async ({ page }) => {
        await assertPageReady(page, /^Dashboard$/i);
        await expect(page.locator('.content-wrapper .card')).toHaveCount(4, { timeout: 10_000 });
    });

    test('02 Leads index renders', async ({ page }) => {
        await visitSidebar(page, /^Leads$/i, /^Leads$/i);
        await assertDataTable(page);
    });

    test('03 Scraper index renders', async ({ page }) => {
        await visitSidebar(page, /^Scraper$/i, /Lead Scraper/i);
        await expect(page.locator('.content-wrapper .card').first()).toBeVisible();
    });

    test('04 Services index renders', async ({ page }) => {
        await visitSidebar(page, /^Services$/i, /^Services$/i);
        await assertDataTable(page);
    });

    test('05 AI Providers index renders', async ({ page }) => {
        await visitSidebar(page, /^Providers$/i, /AI Providers/i);
        await assertDataTable(page);
    });

    test('06 AI Models index renders', async ({ page }) => {
        await visitSidebar(page, /^Models$/i, /AI Models/i);
        await assertDataTable(page);
    });

    test('07 AI Employees index renders', async ({ page }) => {
        await visitSidebar(page, /^Employees$/i, /AI Employees/i);
        await assertDataTable(page);
    });

    test('08 AI Prompts index renders', async ({ page }) => {
        await visitSidebar(page, /^Prompts$/i, /Prompt Templates/i);
        await assertDataTable(page);
    });

    test('09 AI Knowledge index renders', async ({ page }) => {
        await visitSidebar(page, /^Knowledge$/i, /Knowledge Base/i);
        await assertDataTable(page);
    });

    test('10 AI Logs index renders', async ({ page }) => {
        await visitSidebar(page, /AI Logs/i, /AI Logs/i);
        await expect(page.getByText('Interaction log')).toBeVisible();
    });

    test('11 Voice Providers index renders', async ({ page }) => {
        await visitSidebar(page, /Voice Providers/i, /Voice Providers/i);
        await assertDataTable(page);
    });

    test('12 Voice Calls index renders', async ({ page }) => {
        await visitSidebar(page, /Voice Calls/i, /Voice Calls/i);
        await assertDataTable(page);
    });

    test('13 Email Providers index renders', async ({ page }) => {
        await visitSidebar(page, /Email Providers/i, /Email Providers/i);
        await assertDataTable(page);
    });

    test('14 Email Campaigns index renders', async ({ page }) => {
        await visitSidebar(page, /^Campaigns$/i, /Email Campaigns/i);
        await assertDataTable(page);
    });

    test('15 Email Sends index renders', async ({ page }) => {
        await visitSidebar(page, /Email Sends/i, /Email Sends/i);
        await assertDataTable(page);
    });

    test('16 Profile page renders', async ({ page }) => {
        await openProfile(page);
        await expect(page.locator('.content-wrapper .card')).toHaveCount(3, { timeout: 10_000 });
    });
});

test.describe('Auth pages (guest)', () => {
    test('Login page loads Materio shell', async ({ page }) => {
        await page.goto('/login');
        await expect(page.getByLabel('Email')).toBeVisible({ timeout: 20_000 });
        await expect(page.getByLabel('Password')).toBeVisible();
        await expect(page.getByRole('button', { name: /log in/i })).toBeVisible();
        await expect(page.locator('.authentication-wrapper')).toBeVisible();
    });

    test('Login form submits to dashboard', async ({ page }) => {
        await login(page);
        await expect(page).toHaveURL(/\/dashboard$/);
    });

    test('Forgot password page loads', async ({ page }) => {
        await page.goto('/forgot-password');
        await expect(page.getByLabel('Email')).toBeVisible({ timeout: 20_000 });
        await expect(page.locator('.authentication-wrapper')).toBeVisible();
    });
});
