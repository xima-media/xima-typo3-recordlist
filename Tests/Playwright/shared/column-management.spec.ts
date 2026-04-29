import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, openColumnsModal, toggleColumn, waitForReload } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';

test.describe('Column Management', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); });
  test.afterAll(() => { resetDatabase(); });

  test.beforeEach(async ({ page }) => {
    resetUserPreferences();
    await loginAsAdmin(page);
  });

  test('columns modal opens', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await openColumnsModal(page, contentFrame);
    await expect(page.locator('.modal')).toBeVisible();
    await page.locator('.modal button.btn-default').filter({ hasText: 'Abort' }).click();
  });

  test('add new column', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    // 'Last login' should NOT be in the table header by default
    await expect(contentFrame.locator('thead')).not.toContainText('Last login');

    await toggleColumn(page, contentFrame, 'lastlogin');

    // 'Last login' should now be visible
    await expect(contentFrame.locator('thead')).toContainText('Last login');
  });

  test('column settings persist across page reload', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    await toggleColumn(page, contentFrame, 'lastlogin');

    await page.reload();
    await contentFrame.locator('main.recordlist').waitFor({ timeout: 10000 });

    await expect(contentFrame.locator('thead')).toContainText('Last login');
  });

  test('unchecking all columns restores defaults', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    await toggleColumn(page, contentFrame, 'lastlogin');
    await toggleColumn(page, contentFrame, 'description');
    await expect(contentFrame.locator('thead')).toContainText('Last login');
    await expect(contentFrame.locator('thead')).toContainText('Description');

    // Open modal and select none
    await openColumnsModal(page, contentFrame);
    await page.locator('.modal button[data-action="select-none"]').click();
    await page.locator('.modal button.btn-primary').click();
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });
    await waitForReload(contentFrame);

    // Default columns are restored
    await expect(contentFrame.locator('thead')).toContainText('Username');
    await expect(contentFrame.locator('thead')).toContainText('Name');
    // Custom columns are gone
    await expect(contentFrame.locator('thead')).not.toContainText('Last login');
    await expect(contentFrame.locator('thead')).not.toContainText('Description');
  });

  test('toggle selection button inverts column selection', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    // Verify default state
    await expect(contentFrame.locator('thead')).toContainText('Username');
    await expect(contentFrame.locator('thead')).toContainText('Name');
    await expect(contentFrame.locator('thead')).not.toContainText('Admin');
    await expect(contentFrame.locator('thead')).not.toContainText('Limit to languages');

    // First toggle — invert selection
    await openColumnsModal(page, contentFrame);
    await page.locator('.modal button[data-action="select-toggle"]').click();
    await page.locator('.modal button.btn-primary').click();
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });
    await waitForReload(contentFrame);

    // Previously hidden columns now visible
    await expect(contentFrame.locator('thead')).toContainText('Admin');
    await expect(contentFrame.locator('thead')).toContainText('Limit to languages');
    // Previously visible columns now hidden
    await expect(contentFrame.locator('thead')).not.toContainText('Username');
    await expect(contentFrame.locator('thead')).not.toContainText('Name');

    // Second toggle — invert back
    await openColumnsModal(page, contentFrame);
    await page.locator('.modal button[data-action="select-toggle"]').click();
    await page.locator('.modal button.btn-primary').click();
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });
    await waitForReload(contentFrame);

    // Back to original state
    await expect(contentFrame.locator('thead')).toContainText('Username');
    await expect(contentFrame.locator('thead')).toContainText('Name');
    await expect(contentFrame.locator('thead')).not.toContainText('Admin');
    await expect(contentFrame.locator('thead')).not.toContainText('Limit to languages');
  });

  test('search filter in columns modal filters available options', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await openColumnsModal(page, contentFrame);

    const modal = page.locator('.modal');

    // All labels visible initially (using data-column-name suffix match to avoid ambiguous :has-text)
    await expect(modal.locator('li[data-column-name$="username"]')).toBeVisible();
    await expect(modal.locator('li[data-column-name$="realName"]')).toBeVisible();
    await expect(modal.locator('li[data-column-name$="lastlogin"]')).toBeVisible();
    await expect(modal.locator('li[data-column-name$="description"]')).toBeVisible();

    // Filter 'user' — only Username visible
    const filterInput = modal.locator('input[name="columns-filter"]');
    await filterInput.fill('user');
    await filterInput.dispatchEvent('input');
    await page.waitForTimeout(300);
    await expect(modal.locator('li[data-column-name$="username"]')).not.toHaveClass(/d-none/);
    // "Name [realName]" should be hidden — its data-column-name ends with "realName"
    await expect(modal.locator('li[data-column-name$="realName"]')).toHaveClass(/d-none/);

    // Filter 'login' — only Last login visible
    await filterInput.fill('login');
    await filterInput.dispatchEvent('input');
    await page.waitForTimeout(300);
    await expect(modal.locator('li[data-column-name*="lastlogin"]')).not.toHaveClass(/d-none/);
    await expect(modal.locator('li[data-column-name*="Username"]')).toHaveClass(/d-none/);

    // Clear filter — all visible again
    await filterInput.fill('');
    await filterInput.dispatchEvent('input');
    await page.waitForTimeout(300);
    await expect(modal.locator('li[data-column-name$="username"]')).not.toHaveClass(/d-none/);
    await expect(modal.locator('li[data-column-name$="lastlogin"]')).not.toHaveClass(/d-none/);

    // Close modal
    await modal.locator('button.btn-primary').click();
    await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });
    await waitForReload(contentFrame);
  });

  test('content block label are translated in column selection', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await openColumnsModal(page, contentFrame);
    await expect(page.locator('.modal')).toContainText('Content block field');
  });

  test('content block label are translated in table column', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await toggleColumn(page, contentFrame, 'xima_extendednews_content_blocks_field');
    await expect(contentFrame.locator('thead')).toContainText('Content block');
  });
});
