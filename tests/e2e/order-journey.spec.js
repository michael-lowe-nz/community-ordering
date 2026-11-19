import { expect, test } from '@playwright/test';

const restaurantName = process.env.E2E_RESTAURANT || 'K.C Cafe';
const dishName = process.env.E2E_DISH || 'Mapo Tofu';

/**
 * The one flow the app exists for: sign up, pick a restaurant, build what the
 * group is ordering in the menu builder, and land on the saved order page.
 * Each run registers a fresh account, so it needs no seeded user.
 */
test('a new user can build an order and see it saved', async ({ page }, testInfo) => {
    const email = `e2e-${Date.now()}-${testInfo.project.name}@example.com`;

    await test.step('register', async () => {
        await page.goto('/register');
        await page.getByLabel('Name').fill('E2E Tester');
        await page.getByLabel('Email').fill(email);
        await page.getByLabel('Password', { exact: true }).fill('e2e-password-123');
        await page.getByLabel('Confirm Password').fill('e2e-password-123');
        await page.getByRole('button', { name: 'Register' }).click();
        await expect(page.getByRole('heading', { name: 'find your favourite spots' })).toBeVisible();
    });

    await test.step('open the restaurant and start an order', async () => {
        await page.getByPlaceholder('Search for a spot').fill(restaurantName);
        await page.getByRole('link', { name: restaurantName }).first().click();
        await expect(page.getByRole('heading', { name: restaurantName })).toBeVisible();
        await page.getByRole('button', { name: 'Start an order' }).click();
    });

    await test.step('add a dish and a person in the menu builder', async () => {
        await expect(page.getByRole('heading', { name: 'New menu' })).toBeVisible();

        const dishInput = page.getByRole('textbox', { name: 'Add a dish' });
        await dishInput.fill(dishName);
        await dishInput.press('Enter');
        await page.getByRole('button', { name: 'More people' }).click();

        await expect(page.locator('.row').filter({ hasText: dishName })).toBeVisible();
        await expect(page.getByText('5 people')).toBeVisible();
        await expect(page.getByRole('status')).toHaveText('Saved');
    });

    await test.step('finish and see the saved order', async () => {
        await page.getByRole('link', { name: 'Done' }).click();
        await expect(page).toHaveURL(/\/orders\/\d+$/);
        await expect(page.getByText(dishName)).toBeVisible();
        await expect(page.getByText('5 people')).toBeVisible();
    });

    await test.step('the order survives a reload', async () => {
        await page.reload();
        await expect(page.getByText(dishName)).toBeVisible();
    });
});
