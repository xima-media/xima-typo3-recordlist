import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('News History', () => {
  let consoleErrors: ConsoleErrorTracker;

  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });

  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('history button is visible in action bar', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const historyBtn = contentFrame.locator('a[data-action="history"]').first();
    await expect(historyBtn).toBeVisible({ timeout: 10000 });
  });

  test('history modal opens on button click', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    await contentFrame.locator('a[data-action="history"]').first().click();

    const modal = page.locator('.modal');
    await modal.waitFor({ timeout: 5000 });
    await expect(modal).toBeVisible();
    await expect(modal.locator('.modal-title')).toContainText('Record history');
  });

  test('history modal shows user avatar and date', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    await contentFrame.locator('a[data-action="history"]').first().click();

    const modal = page.locator('.modal');
    await modal.waitFor({ timeout: 5000 });

    // Either there are history entries (with avatar + date) or the empty-state message
    const hasEntries = await modal.locator('.xima-history-entry').count();
    if (hasEntries > 0) {
      await expect(modal.locator('.xima-history-avatar').first()).toBeVisible();
      await expect(modal.locator('.xima-history-date').first()).toBeVisible();
    } else {
      await expect(modal.locator('.xima-history-empty')).toBeVisible();
    }
  });

  test('hide system fields toggle is present and on by default', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    await contentFrame.locator('a[data-action="history"]').first().click();

    const modal = page.locator('.modal');
    await modal.waitFor({ timeout: 5000 });

    const toggle = modal.locator('input[type="checkbox"]');
    await expect(toggle).toBeVisible();
    await expect(toggle).toBeChecked();
  });

  test('hide system fields toggle filters system columns', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Use the 3rd record which is known to have history entries
    await contentFrame.locator('a[data-action="history"]').nth(2).click();

    const modal = page.locator('.modal');
    await modal.waitFor({ timeout: 5000 });

    const toggle = modal.locator('input[type="checkbox"]');
    const wrap = modal.locator('.xima-history-wrap');

    // Default: system fields hidden — no [data-system] field should be visible
    await expect(toggle).toBeChecked();
    const systemFieldsVisible = await modal.locator('.xima-history-field[data-system]').evaluateAll(
      els => els.some(el => getComputedStyle(el).display !== 'none')
    );
    expect(systemFieldsVisible).toBe(false);

    // Uncheck — system fields become visible
    await toggle.click();
    await expect(toggle).not.toBeChecked();
    await expect(wrap).not.toHaveClass(/xima-history--hide-system/);

    const systemFieldsNowVisible = await modal.locator('.xima-history-field[data-system]').evaluateAll(
      els => els.some(el => getComputedStyle(el).display !== 'none')
    );
    expect(systemFieldsNowVisible).toBe(true);
  });

  test('history modal close button dismisses modal', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    await contentFrame.locator('a[data-action="history"]').first().click();

    const modal = page.locator('.modal');
    await modal.waitFor({ timeout: 5000 });

    await page.locator('.modal .modal-footer button').filter({ hasText: 'Close' }).click();
    await modal.waitFor({ state: 'detached', timeout: 5000 });
    await expect(modal).not.toBeVisible();
  });
});
