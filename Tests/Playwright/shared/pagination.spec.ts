import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, searchFor, waitForReload } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';

test.describe('Pagination', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); });
  test.afterAll(() => { resetDatabase(); });

  test.beforeEach(async ({ page }) => {
    resetUserPreferences();
    await loginAsAdmin(page);
  });

  test('navigate to next and previous page', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Two pagination bars (top and bottom of table)
    await expect(contentFrame.locator('.pagination')).toHaveCount(2);

    // Navigate to next page
    await contentFrame.locator('.pagination a[title="Next"]').first().click();
    await waitForReload(contentFrame);
    await expect(contentFrame.locator('.pagination input[name="current_page"]').first()).toHaveValue('2');

    // Navigate back to previous page — evaluate bypasses interception issues
    await contentFrame.locator('.pagination a[title="Previous"]').first().evaluate((el) => (el as HTMLElement).click());
    await waitForReload(contentFrame);
    await expect(contentFrame.locator('.pagination input[name="current_page"]').first()).toHaveValue('1');
  });

  test('jump to specific page', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await contentFrame.locator('.pagination input[name="current_page"]').first().fill('3');
    await contentFrame.locator('.pagination a[data-action="pagination-jump"]').first().click();
    await waitForReload(contentFrame);

    await expect(contentFrame.locator('.pagination input[name="current_page"]').first()).toHaveValue('3');
  });

  test('pagination persists filters', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await searchFor(contentFrame, 'E');

    await contentFrame.locator('.pagination a[title="Next"]').first().evaluate((el) => (el as HTMLElement).click());
    await waitForReload(contentFrame);

    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('E');
  });

  test('change items per page', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Selecting items_per_page triggers JS change event which auto-submits the form
    await contentFrame.locator('select[name="items_per_page"]').first().selectOption('50');
    await waitForReload(contentFrame);

    await expect(contentFrame.locator('select[name="items_per_page"]').first()).toHaveValue('50');
  });

  test('items per page persists across page reload', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await contentFrame.locator('select[name="items_per_page"]').first().selectOption('50');
    await waitForReload(contentFrame);

    await page.reload();
    await contentFrame.locator('main.recordlist').waitFor({ timeout: 10000 });

    await expect(contentFrame.locator('select[name="items_per_page"]').first()).toHaveValue('50');
  });

  test('items per page resets with reset button', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await contentFrame.locator('select[name="items_per_page"]').first().selectOption('200');
    await waitForReload(contentFrame);

    // Open filter panel and click the reset submit button via JS (it's a styled input)
    const filterPanel = contentFrame.locator('input[name="search_field"]');
    if (!await filterPanel.isVisible()) {
      await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
      await filterPanel.waitFor({ state: 'visible', timeout: 5000 });
    }
    await contentFrame.locator('input[name="reset"]').evaluate((el) => (el as HTMLElement).click());
    await waitForReload(contentFrame);

    await expect(contentFrame.locator('select[name="items_per_page"]').first()).toHaveValue('25');
  });
});
