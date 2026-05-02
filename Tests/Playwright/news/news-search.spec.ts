import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, searchFor } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('News Search', () => {
  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('search by title finds English results', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await searchFor(contentFrame, 'Employee');

    await expect(contentFrame.locator('body')).toContainText('Employee');
    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(3);
  });

  test('search by German title finds result', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await searchFor(contentFrame, 'Kundenzufriedenheit');

    await expect(contentFrame.locator('body')).toContainText('Kundenzufriedenheit');
    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(1);
  });

  test('search with no results shows empty list', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await searchFor(contentFrame, 'NonexistentNewsTitle12345');

    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(0);
  });
});
