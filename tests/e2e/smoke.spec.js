import { expect, test } from '@playwright/test';
import { waitForAlpine } from './helpers.js';

const restaurantName = process.env.E2E_RESTAURANT || 'K.C Cafe';

test('the app reports healthy, including its database', async ({ request }) => {
    const response = await request.get('/up');

    expect(response.status()).toBe(200);
});

test('the home page loads with its styles and lists restaurants', async ({ page }) => {
    const failedAssets = [];
    page.on('response', (response) => {
        if (response.url().includes('/build/assets/') && !response.ok()) {
            failedAssets.push(`${response.status()} ${response.url()}`);
        }
    });

    await page.goto('/');
    await waitForAlpine(page);

    await expect(page.getByRole('heading', { name: 'find your favourite spots' })).toBeVisible();
    await page.getByPlaceholder('Search for a spot').fill(restaurantName);
    await expect(page.getByRole('link', { name: restaurantName })).toBeVisible();
    expect(failedAssets).toEqual([]);
});
