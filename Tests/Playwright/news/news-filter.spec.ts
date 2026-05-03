import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, waitForReload, selectLanguage } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

test.describe('News Filter', () => {
  test.describe.configure({ mode: 'serial' });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('filter by English language — only English records shown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await selectLanguage(contentFrame, 'English');

    await expect(contentFrame.locator('tr[data-sys_language_uid="0"][data-uid]').first()).toBeVisible();
    await expect(contentFrame.locator('tr[data-uid][data-sys_language_uid]:not([data-sys_language_uid="0"])')).toHaveCount(0);
  });

  test('filter by German language — only German records shown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await selectLanguage(contentFrame, 'German');

    await expect(contentFrame.locator('tr[data-sys_language_uid="1"][data-uid]').first()).toBeVisible();
    await expect(contentFrame.locator('tr[data-uid][data-sys_language_uid]:not([data-sys_language_uid="1"])')).toHaveCount(0);
  });

  test('filter by author — all four expression types work', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Ensure filter panel is open
    const filterPanel = contentFrame.locator('input[name="filter[author][value]"]');
    if (!await filterPanel.isVisible()) {
      await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
      await filterPanel.waitFor({ state: 'visible', timeout: 5000 });
    }

    const testCases: Array<{ expr: string; value: string; check: (authorText: string) => boolean }> = [
      { expr: 'like', value: 'Ruby', check: (t) => t.includes('Ruby') },
      { expr: 'notLike', value: 'Ruby', check: (t) => !t.includes('Ruby') },
      { expr: 'eq', value: 'Ruby Bennett', check: (t) => t === 'Ruby Bennett' },
      { expr: 'neq', value: 'Ruby Bennett', check: (t) => t !== 'Ruby Bennett' },
    ];

    for (const tc of testCases) {
      // Reset filter state
      if (!await filterPanel.isVisible()) {
        await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
        await filterPanel.waitFor({ state: 'visible', timeout: 5000 });
      }

      await contentFrame.locator('select[name="filter[author][expr]"]').selectOption(tc.expr);
      await filterPanel.fill(tc.value);
      await contentFrame.locator('button[type="submit"][name="search"]').first().click();
      await waitForReload(contentFrame);

      const rows = contentFrame.locator('tr[data-uid]');
      const count = await rows.count();
      expect(count).toBeGreaterThan(0);

      for (let i = 0; i < count; i++) {
        const row = rows.nth(i);
        const uid = await row.getAttribute('data-uid');
        const authorText = (await contentFrame.locator(`span[id="author-${uid}"]`).textContent() ?? '').trim();
        expect(tc.check(authorText), `expr=${tc.expr}, uid=${uid}, author="${authorText}"`).toBe(true);
      }
    }
  });

  test('filter by category — in and notIn expressions work', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    // Ensure filter panel is open — check via a visible element (the expr select), not the hidden tree input
    const categoryExpr = contentFrame.locator('select[name="filter[categories][expr]"]');
    if (!await categoryExpr.isVisible()) {
      await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
      await categoryExpr.waitFor({ state: 'visible', timeout: 10000 });
    }

    for (const expr of ['in', 'notIn']) {
      if (!await categoryExpr.isVisible()) {
        await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
        await categoryExpr.waitFor({ state: 'visible', timeout: 10000 });
      }

      // Set category value via JS (tree component manages hidden input — always hidden)
      await contentFrame.locator('input[name="filter[categories][value]"]').evaluate((el) => { (el as HTMLInputElement).value = '18'; });
      await categoryExpr.selectOption(expr);
      await contentFrame.locator('button[type="submit"][name="search"]').first().click();
      await waitForReload(contentFrame);

      const count = await contentFrame.locator('tr[data-uid]').count();
      expect(count, `expr=${expr} should return records`).toBeGreaterThan(0);
    }
  });
});
