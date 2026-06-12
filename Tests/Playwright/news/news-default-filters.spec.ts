import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, toggleColumn } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('Default filters and independent filter visibility', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('filterVisible — categories filter remains visible after hiding the categories column', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Verify categories column is visible by default
    await expect(contentFrame.locator('th', { hasText: 'Categories' }).first()).toBeVisible();

    // Hide the categories column via column management
    await toggleColumn(page, contentFrame, 'categories');

    // Column header should be gone
    await expect(contentFrame.locator('th', { hasText: 'Categories' })).toHaveCount(0);

    // Open filter panel
    const filterPanel = contentFrame.locator('.toggleFiltersButton:not(.hidden)');
    await filterPanel.click();

    // Categories filter must still be rendered (filterVisible = true in NewsController)
    await expect(contentFrame.locator('select[name="filter[categories][expr]"]')).toBeVisible();
  });
});
