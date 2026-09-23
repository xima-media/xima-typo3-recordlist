import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, searchFor, toggleColumn } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// The fixtures relate news 1 to news 2 (type internal), 3 (type external) and 4 (type default)
test.describe('Relation badge icons', () => {
  test.beforeAll(() => { resetDatabase(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    resetUserPreferences();
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('group badges show the type icon of each related record', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    await toggleColumn(page, contentFrame, 'related');
    await searchFor(contentFrame, 'New Product Launch Revolutionizes Industry');

    const badges = contentFrame.locator('tr[data-uid="1"] .group-badge');
    await expect(badges).toHaveCount(3);

    const icons = await badges.evaluateAll(links => links.map(link => ({
      uid: link.getAttribute('data-uid'),
      icon: link.querySelector('[data-identifier]')?.getAttribute('data-identifier'),
    })));
    expect(icons).toEqual([
      { uid: '2', icon: 'ext-news-type-internal' },
      { uid: '3', icon: 'ext-news-type-external' },
      { uid: '4', icon: 'ext-news-type-default' },
    ]);
  });
});
