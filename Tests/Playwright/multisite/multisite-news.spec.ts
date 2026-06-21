import { test, expect } from '@playwright/test';
import { loginAsAdmin, openModule, searchFor } from '../helpers/typo3-backend';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// Records that live exclusively in one site's news folder.
const MAIN_NEWS = 'New Product Launch Revolutionizes Industry'; // pid 15 (main site)
const SECOND_NEWS = 'Second Site Exclusive Announcement';        // pid 22 (second site)

test.describe('Multi-Site News (record sources across sites)', () => {
  // Serial: tests share the same TYPO3 admin session — persisted search state bleeds otherwise.
  test.describe.configure({ mode: 'serial' });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('aggregates news records from folders in two different sites', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_multisite_news');

    // Records that only exist in the second site's folder are reachable here...
    await searchFor(contentFrame, 'Second Site');
    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(2);
    await expect(contentFrame.locator('body')).toContainText(SECOND_NEWS);

    // ...and so are records that only exist in the main site's folder.
    await searchFor(contentFrame, 'New Product Launch');
    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(1);
  });

  test('the second site folder is not visible in the single-site news module', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    await searchFor(contentFrame, 'Second Site');
    await expect(contentFrame.locator('tr[data-uid]')).toHaveCount(0);
  });

  test('directory dropdown prefixes folders with their site title', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_multisite_news');

    // Both folders are named "News" — disambiguated by the site title prefix.
    const items = contentFrame.locator('a.dropdown-item.dropdown-item-spaced');
    await expect(items.filter({ hasText: 'Main Site › News' })).toHaveCount(1);
    await expect(items.filter({ hasText: 'Second Site › News' })).toHaveCount(1);
  });

  test('new-record options offer every folder, site-prefixed', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_multisite_news');

    // Both folders (including the first/root) are selectable when creating a record.
    const options = contentFrame.locator('a.new-record-in-page');
    await expect(options.filter({ hasText: 'Main Site › News' })).toHaveCount(1);
    await expect(options.filter({ hasText: 'Second Site › News' })).toHaveCount(1);
  });

  test('selecting the second site directory filters to its records only', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_multisite_news');
    // Clear any search persisted by a previous test so the count reflects the directory only.
    await searchFor(contentFrame, '');

    const link = contentFrame.locator('a.dropdown-item.dropdown-item-spaced[title="Second Site › News"]');
    const href = await link.getAttribute('href');
    await contentFrame.locator('html').evaluate((_, url) => window.location.assign(url), href as string);

    // The second-site folder holds exactly its two records. Poll through the iframe
    // reload (which briefly detaches the frame) until the new list has settled.
    await expect.poll(async () => {
      try {
        return await contentFrame.locator('tr[data-uid]').count();
      } catch {
        return -1;
      }
    }).toBe(2);
    await expect(contentFrame.locator('body')).toContainText(SECOND_NEWS);
    await expect(contentFrame.locator('body')).not.toContainText(MAIN_NEWS);
  });
});
