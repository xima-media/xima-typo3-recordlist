import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';

test.describe('BeUsers Export', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('download modal opens', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    await contentFrame.locator('.recordlist-download-button').click();

    // Modal opens in the main frame (outside the iframe)
    await page.waitForSelector('.modal', { timeout: 5000 });
    await page.waitForTimeout(2000);

    await expect(page.locator('.modal select[name="format"]')).toHaveValue('csv');

    // Close the modal
    await page.click('.modal button.btn-primary');
    await expect(page.locator('.modal')).toHaveCount(0);
  });
});
