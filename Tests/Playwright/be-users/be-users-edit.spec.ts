import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, getFirstRecordUid } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('BeUsers Edit', () => {
  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('edit button opens form', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a:is([title="Edit"],[aria-label="Edit"])`).click();
    await contentFrame.locator('.module-docheader').first().waitFor({ timeout: 5000 });

    await expect(contentFrame.locator('body')).toContainText('Edit');
  });
});
