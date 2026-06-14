import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// Mirrors AbstractBackendController::DEFAULT_ACTIONS_THRESHOLD — fixed in the controller.
const THRESHOLD = 5;

/**
 * Inject a synthetic action group with `count` icon-only buttons into the live recordlist.
 * The shipped module watches the recordlist via a MutationObserver, so it collapses the
 * injected group exactly as it would a row rendered by the server — this keeps the test
 * deterministic regardless of how many actions a given fixture row happens to render.
 */
async function injectActionGroup(
  contentFrame: FrameLocator,
  id: string,
  count: number,
  // 'title' = pristine markup; 'data-bs-original-title' = how Bootstrap leaves a button
  // once its tooltip has initialised (the `title` attribute is removed).
  labelAttr: 'title' | 'data-bs-original-title' = 'title',
): Promise<void> {
  await contentFrame.locator('main.recordlist').evaluate((main, args) => {
    const group = document.createElement('div');
    group.className = 'btn-group';
    group.id = args.id;
    group.setAttribute('data-recordlist-actions', '');
    for (let i = 1; i <= args.count; i++) {
      const button = document.createElement('a');
      button.className = 'btn btn-default';
      button.href = '#';
      button.setAttribute(args.labelAttr, `Action ${i}`);
      button.innerHTML = '<span class="icon"></span>';
      group.appendChild(button);
    }
    main.appendChild(group);
  }, { id, count, labelAttr });
}

test.describe('Action "More" dropdown (issue #92)', () => {
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

  test('collapses actions beyond the threshold into a More dropdown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActionGroup(contentFrame, 'synthetic-overflow', THRESHOLD + 3);

    const group = contentFrame.locator('#synthetic-overflow');
    const moreToggle = group.locator('.recordlist-action-more > .dropdown-toggle');
    await expect(moreToggle).toBeVisible();

    // The first THRESHOLD units stay inline; the rest move into the dropdown menu.
    await expect(group.locator(':scope > :not(.recordlist-action-more)')).toHaveCount(THRESHOLD);
    await expect(group.locator('.recordlist-action-more-menu > li')).toHaveCount(3);
  });

  test('More toggle is icon-only and relocated actions get text labels', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    // Use data-bs-original-title to mirror buttons whose Bootstrap tooltip already stripped
    // the `title` attribute — the labels must still resolve (regression for issue #92).
    await injectActionGroup(contentFrame, 'synthetic-labels', THRESHOLD + 2, 'data-bs-original-title');

    const group = contentFrame.locator('#synthetic-labels');
    // Toggle carries no visible text — the actions-menu-alternative icon only.
    await expect(group.locator('.recordlist-action-more > .dropdown-toggle')).toHaveText('');

    await group.locator('.recordlist-action-more > .dropdown-toggle').click();
    const menu = group.locator('.recordlist-action-more-menu');
    await expect(menu).toBeVisible();

    // Each relocated action shows its title as a readable label (icon-only in the table).
    const labels = menu.locator('.recordlist-action-more-label');
    await expect(labels.first()).toHaveText('Action 6');
    expect(await labels.count()).toBe(2);
  });

  test('leaves a group within the threshold untouched', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActionGroup(contentFrame, 'synthetic-fits', THRESHOLD);

    const group = contentFrame.locator('#synthetic-fits');
    await expect(group.locator('.recordlist-action-more')).toHaveCount(0);
    await expect(group.locator(':scope > .btn')).toHaveCount(THRESHOLD);
  });
});
