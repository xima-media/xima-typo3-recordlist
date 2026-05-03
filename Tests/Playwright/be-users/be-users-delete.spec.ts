import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, getFirstRecordUid } from '../helpers/typo3-backend';
import { resetDatabase } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('BeUsers Delete', () => {
  test.afterAll(() => { resetDatabase(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('delete record with confirmation', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[data-delete2]`).click();

    // Modal opens in the main frame (outside the iframe)
    await page.locator('.modal').waitFor({ timeout: 5000 });
    await page.click('.modal button.btn-warning');
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });

    await expect(contentFrame.locator(`tr[data-uid="${uid}"]`)).toHaveCount(0);
  });

  test('cancel record deletion', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[data-delete2]`).click();

    // Modal opens in the main frame (outside the iframe)
    await page.locator('.modal').waitFor({ timeout: 5000 });
    await page.click('.modal button.btn-default');
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });

    await expect(contentFrame.locator(`tr[data-uid="${uid}"]`)).toBeVisible();
  });
});
