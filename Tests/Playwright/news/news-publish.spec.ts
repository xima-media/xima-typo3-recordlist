import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, getFirstRecordUid } from '../helpers/typo3-backend';
import { resetDatabase } from '../helpers/db-reset';

test.describe('News Publish', () => {
  test.afterAll(() => { resetDatabase(); });

  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('mark record as ready to publish', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uid = await getFirstRecordUid(contentFrame);

    // Open edit form and modify record to create a workspace version
    await contentFrame.locator(`tr[data-uid="${uid}"] a[title="Edit"]`).click();
    await contentFrame.locator('.module-docheader').first().waitFor({ timeout: 5000 });

    const titleInput = contentFrame.locator(`input[data-formengine-input-name="data[tx_news_domain_model_news][${uid}][title]"]`);
    await titleInput.click({ clickCount: 3 });
    await titleInput.fill('Modified Title');
    await titleInput.press('Tab');
    await page.waitForTimeout(500);
    await contentFrame.locator('button[name="_savedok"]').click();
    await page.waitForTimeout(2000);

    await contentFrame.locator('a.t3js-editform-close').click();
    await contentFrame.locator('main.recordlist').waitFor({ timeout: 10000 });

    // Click the "Send to stage" workspace action on the version row
    await contentFrame.locator(`tr[data-t3ver_oid="${uid}"] a[data-workspace-action="sendToSpecificStageExecute"][data-workspace-stage="-10"]`).click();

    // Modal opens in main frame
    await page.waitForSelector('.modal', { timeout: 5000 });
    await expect(page.locator('.modal')).toContainText('Send to stage');
    await page.locator('.modal textarea[name="comments"]').fill('Ready to publish this news item.');
    await page.locator('.modal button.btn-primary').click();
    await page.waitForTimeout(2000);

    // Verify record is now marked as "Awaiting review"
    await expect(contentFrame.locator(`tr[data-t3ver_oid="${uid}"]`)).toContainText('Awaiting review');
  });
});
