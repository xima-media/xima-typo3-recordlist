import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, searchFor, switchTable } from '../helpers/typo3-backend';

test.describe('BeUsers Module', () => {
  // Serial: tests share the same TYPO3 admin session — parallel execution causes search state bleed
  test.describe.configure({ mode: 'serial' });

  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('all tables are accessible', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    await expect(contentFrame.locator('body')).toContainText('Backend user');
    await expect(contentFrame.locator('tr[data-uid]').first()).toBeVisible();

    await switchTable(contentFrame, 'Backend usergroup');
    await expect(contentFrame.locator('main.recordlist')).toBeVisible();
    await expect(contentFrame.locator('h1:not(.h3)')).toContainText('4 Records');

    await switchTable(contentFrame, 'File mount');
    await expect(contentFrame.locator('main.recordlist')).toBeVisible();
    await expect(contentFrame.locator('h1:not(.h3)')).toContainText('3 Records');
  });

  test('search inputs are persisted per table', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');

    // Ensure we start on Backend user table (previous test may have left session on a different table)
    await switchTable(contentFrame, 'Backend user');

    // Search in Backend user table
    await searchFor(contentFrame, 'admin');
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('admin');
    await expect(contentFrame.locator('main.recordlist')).toContainText('admin');

    // Switch to Backend usergroup — search field should be empty
    await switchTable(contentFrame, 'Backend usergroup');
    await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
    await contentFrame.locator('input[name="search_field"]').waitFor({ state: 'visible', timeout: 5000 });
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('');

    // Search in Backend usergroup
    await contentFrame.locator('input[name="search_field"]').fill('Editors');
    await contentFrame.locator('input[name="search_field"]').press('Enter');
    await contentFrame.locator('main.recordlist').waitFor({ timeout: 5000 });
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('Editors');

    // Switch to File mount — search field should be empty
    await switchTable(contentFrame, 'File mount');
    await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
    await contentFrame.locator('input[name="search_field"]').waitFor({ state: 'visible', timeout: 5000 });
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('');

    // Return to Backend user — original search should still be there
    await switchTable(contentFrame, 'Backend user');
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('admin');
    await expect(contentFrame.locator('main.recordlist')).toContainText('admin');

    // Return to Backend usergroup — its search should still be there
    await switchTable(contentFrame, 'Backend usergroup');
    await expect(contentFrame.locator('input[name="search_field"]')).toHaveValue('Editors');
  });
});
