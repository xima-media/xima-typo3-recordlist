import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule, waitForReload, clickResetView } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// PagesController defaults doktype to "Standard"; uid 17 is the "Categories" folder.
const FOLDER_ROW = 'tr[data-uid="17"]';
const DOKTYPE = 'select[name="filter[doktype][value]"]';
const REVERT = '[data-filter-default="select"] .xima-recordlist-filter-revert';

async function openFilters(contentFrame: FrameLocator): Promise<void> {
  // the panel remembers being open, so only toggle it when it is closed
  if (!await contentFrame.locator(DOKTYPE).isVisible()) {
    await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
  }
  await expect(contentFrame.locator(DOKTYPE)).toBeVisible();
}

async function submitFilters(contentFrame: FrameLocator): Promise<void> {
  await contentFrame.locator('#filterInputs button[name="search"]').first().click();
  await waitForReload(contentFrame);
}

test.describe('Default filter values', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });
  test.afterAll(() => { resetUserPreferences(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('the default applies on first open and is visible in its filter', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_pages');
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(0);

    await openFilters(contentFrame);
    await expect(contentFrame.locator(DOKTYPE)).toHaveValue('1');
    await expect(contentFrame.locator(REVERT)).toBeHidden();
  });

  test('a removed default stays removed across reloads', async ({ page }) => {
    let contentFrame = await openModule(page, 'example_pages');
    await openFilters(contentFrame);

    await contentFrame.locator(DOKTYPE).selectOption('');
    await expect(contentFrame.locator(REVERT)).toBeVisible();
    await submitFilters(contentFrame);
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(1);

    contentFrame = await openModule(page, 'example_pages');
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(1);
    await openFilters(contentFrame);
    await expect(contentFrame.locator(DOKTYPE)).toHaveValue('');
    await expect(contentFrame.locator(REVERT)).toBeVisible();
  });

  test('the revert icon restores the default', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_pages');
    await openFilters(contentFrame);

    await contentFrame.locator(REVERT).click();
    await expect(contentFrame.locator(DOKTYPE)).toHaveValue('1');
    await expect(contentFrame.locator(REVERT)).toBeHidden();
    // restoring only changes the form, like clearing does
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(1);

    await submitFilters(contentFrame);
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(0);
  });

  test('reset view brings a removed default back', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_pages');
    await openFilters(contentFrame);
    await contentFrame.locator(DOKTYPE).selectOption('');
    await submitFilters(contentFrame);
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(1);

    await clickResetView(page, contentFrame);
    await expect(contentFrame.locator(FOLDER_ROW)).toHaveCount(0);
  });
});
