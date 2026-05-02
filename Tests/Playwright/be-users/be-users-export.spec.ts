import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('BeUsers Export', () => {
  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('download modal opens', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    await contentFrame.locator('.recordlist-download-button').click();

    // Modal opens in the main frame (outside the iframe)
    await page.locator('.modal select[name="format"]').waitFor({ state: 'visible', timeout: 5000 });

    await expect(page.locator('.modal select[name="format"]')).toHaveValue('csv');

    // Close the modal
    await page.click('.modal button.btn-primary');
    await expect(page.locator('.modal')).toHaveCount(0);
  });
});
