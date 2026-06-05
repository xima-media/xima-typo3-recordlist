import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, toggleColumn, clickResetView, searchFor } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('Reset view', () => {
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

  test('reset view button is visible in View dropdown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await contentFrame.locator('button.dropdown-toggle').filter({ hasText: 'View' }).click();
    await page.waitForTimeout(300);
    await expect(contentFrame.locator('[data-doc-button="resetViewButton"]')).toBeAttached();
  });

  test('reset view restores default columns', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    // Add a non-default column
    await toggleColumn(page, contentFrame, 'lastlogin');
    await expect(contentFrame.locator('thead')).toContainText('Last login');

    // Reset view removes it
    await clickResetView(page, contentFrame);
    await expect(contentFrame.locator('thead')).not.toContainText('Last login');
    await expect(contentFrame.locator('thead')).toContainText('Username');
  });

  test('reset view clears active search', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    // Apply a search that returns no results — main.recordlist should not be rendered
    await searchFor(contentFrame, 'nonexistentuser12345');
    await expect(contentFrame.locator('main.recordlist')).not.toBeVisible();

    // Reset view clears the search and the admin user reappears
    await clickResetView(page, contentFrame);
    await expect(contentFrame.locator('main.recordlist')).toBeVisible();
    await expect(contentFrame.locator('main.recordlist')).toContainText('admin');
  });
});
