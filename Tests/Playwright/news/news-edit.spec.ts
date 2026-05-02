import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, getFirstRecordUid, waitForReload, waitForSave } from '../helpers/typo3-backend';
import { resetDatabase } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('News Edit', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); });
  test.afterAll(() => { resetDatabase(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('edit button opens form', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[aria-label="Edit"]`).click();
    await contentFrame.locator('.module-docheader').first().waitFor({ timeout: 5000 });

    await expect(page).toHaveURL(/\/typo3\/record\/edit/);
    await expect(contentFrame.locator('body')).toContainText('Edit');
  });

  test('workspace version is created on save', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[aria-label="Edit"]`).click();
    await contentFrame.locator('.module-docheader').first().waitFor({ timeout: 5000 });

    const titleInput = contentFrame.locator(`input[data-formengine-input-name="data[tx_news_domain_model_news][${uid}][title]"]`);
    await titleInput.click({ clickCount: 3 });
    await titleInput.fill('Workspace Modified Title');
    await titleInput.press('Tab');
    await page.waitForTimeout(500);
    await contentFrame.locator('button[name="_savedok"]').click();
    await waitForSave(contentFrame);

    await contentFrame.locator('a.t3js-editform-close').click();
    await waitForReload(contentFrame);
    await contentFrame.locator('tr[data-uid]').first().waitFor({ timeout: 10000 });

    await expect(contentFrame.locator('body')).toContainText('Workspace Modified Title', { timeout: 10000 });
  });

  test('edited record is marked as modified', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uid = await getFirstRecordUid(contentFrame);

    await contentFrame.locator(`tr[data-uid="${uid}"] a[aria-label="Edit"]`).click();
    await contentFrame.locator('.module-docheader').first().waitFor({ timeout: 5000 });

    const titleInput = contentFrame.locator(`input[data-formengine-input-name="data[tx_news_domain_model_news][${uid}][title]"]`);
    await titleInput.click({ clickCount: 3 });
    await titleInput.fill('Modified Record State Test');
    await titleInput.press('Tab');
    await page.waitForTimeout(500);
    await contentFrame.locator('button[name="_savedok"]').click();
    await waitForSave(contentFrame);

    // Reopen module to see workspace state
    const freshFrame = await openModule(page, 'example_news');

    await expect(freshFrame.locator('tr[data-state="modified"]').first()).toBeVisible({ timeout: 10000 });
    await expect(freshFrame.locator('span.workspace-state-modified').first()).toBeVisible();
    await expect(freshFrame.locator('.badge-warning').first()).toContainText('Copy');
  });

  test('deleted record is marked in workspace', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Click the first delete button — only appears on live records (workspace versions have Revert instead)
    await contentFrame.locator('a[data-delete2]').first().click();
    await page.locator('.modal').waitFor({ timeout: 5000 });
    await page.click('.modal button.btn-warning');
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });

    await expect(contentFrame.locator('tr[data-state="deleted"]').first()).toBeVisible();
    await expect(contentFrame.locator('span.workspace-state-deleted').first()).toBeVisible();
  });

  test('inline edit author creates workspace version', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uid = await getFirstRecordUid(contentFrame);

    // Click inline-editable author span
    await contentFrame.locator(`tr[data-uid="${uid}"] span[id="author-${uid}"]`).click();
    await page.waitForTimeout(500);

    // Clear and type new value via JS + keyboard
    await contentFrame.locator(`span[id="author-${uid}"]`).evaluate(el => { el.textContent = ''; });
    await page.keyboard.type('Workspace Author Edit');

    // Save inline edit
    await contentFrame.locator(`tr[data-uid="${uid}"] button[data-action="save"]`).click();
    await page.waitForTimeout(1000);

    // Reload module to see changes
    const freshFrame2 = await openModule(page, 'example_news');

    await expect(freshFrame2.locator('tr[data-state="modified"]').first()).toBeVisible({ timeout: 10000 });
    await expect(freshFrame2.locator('span.workspace-state-modified').first()).toBeVisible();
    await expect(freshFrame2.locator('body')).toContainText('Workspace Author Edit');
  });
});
