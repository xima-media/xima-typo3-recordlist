import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, getFirstRecordUid } from '../helpers/typo3-backend';

test.describe('BeUsers Delete', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('delete record with confirmation', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[data-delete2]`).click();

    // Modal opens in the main frame (outside the iframe)
    await page.waitForSelector('.modal', { timeout: 5000 });
    await page.click('.modal button.btn-warning');
    await page.waitForTimeout(1000);

    await expect(contentFrame.locator(`tr[data-uid="${uid}"]`)).toHaveCount(0);
  });

  test('cancel record deletion', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[data-delete2]`).click();

    // Modal opens in the main frame (outside the iframe)
    await page.waitForSelector('.modal', { timeout: 5000 });
    await page.click('.modal button.btn-default');
    await page.waitForTimeout(1000);

    await expect(contentFrame.locator(`tr[data-uid="${uid}"]`)).toBeVisible();
  });
});
