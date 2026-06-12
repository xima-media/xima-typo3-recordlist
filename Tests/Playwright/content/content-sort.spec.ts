import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule, sortBy, moveRecord } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// The example "Content" module lists tt_content, which uses a `sortby` field and no
// `default_sortby` — so the list is shown in manual sort order and the move up/down actions apply.
// Fixture order (by `sorting`): First, Second, Third, Fourth.
const EXPECTED_DEFAULT_ORDER = ['First Element', 'Second Element', 'Third Element', 'Fourth Element'];

// Header is the first active column; checkbox + icon columns precede it, so it is the 3rd cell.
async function headers(contentFrame: FrameLocator): Promise<string[]> {
  const cells = await contentFrame.locator('tr[data-uid] td:nth-child(3)').allTextContents();
  return cells.map(text => text.trim());
}

test.describe('Content manual sorting', () => {
  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    // each test mutates the sort order (records and persisted module data), so fully reset first
    resetDatabase();
    resetUserPreferences();
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('records are listed in their manual sort order', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');

    expect(await headers(contentFrame)).toEqual(EXPECTED_DEFAULT_ORDER);
  });

  test('sorting buttons are available when ordered by the sorting field', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');

    await expect(contentFrame.locator('tr[data-uid]').first().locator('a[data-sorting-move="up"]')).toHaveCount(1);
    await expect(contentFrame.locator('tr[data-uid]').first().locator('a[data-sorting-move="down"]')).toHaveCount(1);
  });

  test('moving a record down swaps it with the following record', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');

    await moveRecord(contentFrame, contentFrame.locator('tr[data-uid]').first(), 'down');

    expect(await headers(contentFrame)).toEqual([
      'Second Element', 'First Element', 'Third Element', 'Fourth Element',
    ]);
  });

  test('moving a record up swaps it with the previous record', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');

    await moveRecord(contentFrame, contentFrame.locator('tr[data-uid]').nth(2), 'up');

    expect(await headers(contentFrame)).toEqual([
      'First Element', 'Third Element', 'Second Element', 'Fourth Element',
    ]);
  });

  test('sorting buttons disappear when the list is ordered by another column', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');

    await sortBy(contentFrame, 'Header', 'DESC');

    await expect(contentFrame.locator('a[data-sorting-move]')).toHaveCount(0);
  });
});
