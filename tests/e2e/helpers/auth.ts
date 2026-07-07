import { expect, type Page } from '@playwright/test';

export const credentials = {
    email: process.env.PLAYWRIGHT_USER_EMAIL ?? 'superadmin@example.com',
    password: process.env.PLAYWRIGHT_USER_PASSWORD ?? 'password',
};

export async function login(page: Page): Promise<void> {
    await page.goto('/login');
    await expect(page.getByLabel('Email')).toBeVisible({ timeout: 20_000 });
    await page.getByLabel('Email').fill(credentials.email);
    await page.getByLabel('Password').fill(credentials.password);
    await page.getByRole('button', { name: /log in/i }).click();
    await page.waitForURL('**/dashboard', { timeout: 20_000 });
    await expect(page.locator('.content-wrapper h4').filter({ hasText: /^Dashboard$/i })).toBeVisible();
}

export async function assertPageReady(page: Page, heading: RegExp | string): Promise<void> {
    await expect(page.locator('#app')).toBeVisible();
    await expect(page.locator('.content-wrapper h4, .content-wrapper h1.h4').filter({ hasText: heading })).toBeVisible({ timeout: 20_000 });
    await expect(page.locator('.layout-wrapper')).toBeVisible();
    await expect(page.locator('#layout-menu')).toBeVisible();
}

export async function visitSidebar(page: Page, linkName: RegExp | string, heading: RegExp | string): Promise<void> {
    await page.locator('#layout-menu').getByRole('link', { name: linkName }).click();
    await assertPageReady(page, heading);
}

export async function openProfile(page: Page): Promise<void> {
    await page.locator('#layout-navbar .dropdown-toggle').click();
    await page.getByRole('link', { name: /^Profile$/i }).click();
    await assertPageReady(page, /^Profile$/i);
}

export async function assertDataTable(page: Page): Promise<void> {
    const table = page.locator('.content-wrapper table');
    const empty = page.locator('.content-wrapper .card-body.text-center.text-muted');

    await expect(table.or(empty)).toBeVisible({ timeout: 10_000 });
}
