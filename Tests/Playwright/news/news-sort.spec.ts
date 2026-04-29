import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, sortBy } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';

test.describe('News Sort', () => {
  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });

  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page);
  });

  test('sort by author ascending — first author is Albert Kelly', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await sortBy(contentFrame, 'Author');

    const firstAuthor = await contentFrame.locator('tr[data-uid]').first().locator('span[id^="author-"]').textContent();
    expect(firstAuthor?.trim()).toBe('Albert Kelly');
  });

  test('sort by author descending — first author is Zoe Bennett', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await sortBy(contentFrame, 'Author', 'DESC');

    const firstAuthor = await contentFrame.locator('tr[data-uid]').first().locator('span[id^="author-"]').textContent();
    expect(firstAuthor?.trim()).toBe('Zoe Bennett');
  });
});
