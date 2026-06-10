import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences, truncateTable } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// Issue #87: when a table has no records at all (fullRecordCount === 0, not a
// filtered-to-zero result), a customizable empty-state info box is rendered
// instead of the empty list with its search bar.
test.describe('Empty state', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); });
  test.afterAll(() => { resetDatabase(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    resetUserPreferences();
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('list and search render when records exist, no empty-state box', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await expect(contentFrame.locator('main.recordlist')).toBeVisible();
    await expect(contentFrame.locator('input[name="search_field"]')).toBeVisible();
    await expect(contentFrame.locator('.module-body')).not.toContainText('No records yet');
  });

  test('shows empty-state box instead of list and search when table is empty', async ({ page }) => {
    // Remove every record so getFullRecordCount() returns 0 for the news table.
    truncateTable('tx_news_domain_model_news');

    // openModule() waits for main.recordlist, which is intentionally absent in the
    // empty state — navigate raw and wait for the empty-state info box instead.
    const contentFrame = page.frameLocator('iframe[name="list_frame"]');
    await page.click('a[data-modulemenu-identifier="example_news"]');
    await expect(contentFrame.locator('.module-body')).toContainText('No records yet', { timeout: 10000 });

    // The list, the search bar, and the filtered-to-zero "no results" box must all be gone.
    await expect(contentFrame.locator('main.recordlist')).toHaveCount(0);
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveCount(0);
    await expect(contentFrame.locator('.module-body')).not.toContainText('No results found');
  });
});
