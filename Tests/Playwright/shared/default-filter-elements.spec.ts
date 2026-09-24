import { test, expect } from '@playwright/test';
import type { FrameLocator, Locator } from '@playwright/test';
import { loginAsAdmin, openModule, waitForReload, clickResetView } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

/**
* DefaultFiltersController sets one default per filter element. Together they
* leave news 11, 15, 19 and 21; each element is paired with the record that only
* its own default keeps out of the list.
*/

const DEFAULT_ROWS = ['11', '15', '19', '21'];

interface FilterElement {
  name: string;
  field: string;
  excludedUid: string;
  revert: (frame: FrameLocator) => Locator;
  remove: (frame: FrameLocator) => Promise<void>;
  expectDefault: (frame: FrameLocator) => Promise<void>;
}

const valueInput = (frame: FrameLocator, field: string): Locator => frame.locator(`[name="filter[${field}][value]"]`);
const revertIn = (frame: FrameLocator, field: string): Locator =>
  frame.locator(`[data-filter-default]:has([name="filter[${field}][value]"]) .xima-recordlist-filter-revert`);

const ELEMENTS: FilterElement[] = [
  {
    name: 'text',
    field: 'title',
    excludedUid: '23',
    revert: frame => revertIn(frame, 'title'),
    remove: async frame => {
      await valueInput(frame, 'title').fill('');
      // the revert restores the operator along with the value
      await frame.locator('select[name="filter[title][expr]"]').selectOption('like');
    },
    expectDefault: async frame => {
      await expect(valueInput(frame, 'title')).toHaveValue('Launch');
      await expect(frame.locator('select[name="filter[title][expr]"]')).toHaveValue('notLike');
    },
  },
  {
    name: 'select',
    field: 'sys_language_uid',
    excludedUid: '71',
    revert: frame => revertIn(frame, 'sys_language_uid'),
    remove: async frame => { await valueInput(frame, 'sys_language_uid').selectOption(''); },
    expectDefault: async frame => { await expect(valueInput(frame, 'sys_language_uid')).toHaveValue('0'); },
  },
  {
    name: 'group',
    field: 'related',
    excludedUid: '26',
    revert: frame => revertIn(frame, 'related'),
    remove: async frame => { await valueInput(frame, 'related').selectOption(''); },
    expectDefault: async frame => { await expect(valueInput(frame, 'related')).toHaveValue('tx_news_domain_model_news_60'); },
  },
  {
    name: 'inline',
    field: 'related_links',
    excludedUid: '33',
    revert: frame => revertIn(frame, 'related_links'),
    remove: async frame => { await valueInput(frame, 'related_links').fill(''); },
    expectDefault: async frame => { await expect(valueInput(frame, 'related_links')).toHaveValue('Press kit'); },
  },
  {
    name: 'category',
    field: 'categories',
    excludedUid: '13',
    revert: frame => frame.locator('[data-filter-default="category"] .xima-recordlist-filter-revert'),
    remove: async frame => {
      // the tree renders its nodes virtually, so untick through its API as a click would
      await frame.locator('[data-filter-default="category"] typo3-backend-form-selecttree').evaluate((tree: any) => {
        tree.nodes.filter((node: any) => node.checked).forEach((node: any) => tree.selectNode(node));
      });
    },
    expectDefault: async frame => { await expect(valueInput(frame, 'categories')).toHaveValue('18'); },
  },
  {
    name: 'date',
    field: 'datetime',
    excludedUid: '58',
    revert: frame => frame.locator('[data-recordlist-daterange] .xima-recordlist-filter-revert'),
    remove: async frame => {
      await frame.locator('[data-recordlist-daterange] button.close:not(.xima-recordlist-filter-revert)').click();
    },
    expectDefault: async frame => {
      await expect(valueInput(frame, 'datetime')).toHaveValue('2024-01-01');
      await expect(frame.locator('[name="filter[datetime][valueEnd]"]')).toHaveValue('2024-06-30');
    },
  },
];

async function openFilters(frame: FrameLocator): Promise<void> {
  // the panel remembers being open, so only toggle it when it is closed
  if (!await valueInput(frame, 'title').isVisible()) {
    await frame.locator('.toggleFiltersButton:not(.hidden)').click();
  }
  await expect(valueInput(frame, 'title')).toBeVisible();
}

async function submitFilters(frame: FrameLocator): Promise<void> {
  await frame.locator('#filterInputs button[name="search"]').first().click();
  await waitForReload(frame);
}

async function rowUids(frame: FrameLocator): Promise<string[]> {
  const uids = await frame.locator('tr[data-uid]').evaluateAll(rows => rows.map(row => (row as HTMLElement).dataset.uid ?? ''));
  return uids.sort((a, b) => Number(a) - Number(b));
}

test.describe('Default value per filter element', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });
  test.afterAll(() => { resetUserPreferences(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('every default applies on first open', async ({ page }) => {
    const frame = await openModule(page, 'example_default_filters');
    expect(await rowUids(frame)).toEqual(DEFAULT_ROWS);
    await expect(frame.locator('.toggleFiltersButton.show')).toHaveAttribute('data-filter-count', String(ELEMENTS.length));

    await openFilters(frame);
    for (const element of ELEMENTS) {
      await element.expectDefault(frame);
      await expect(element.revert(frame), `${element.name} revert`).toBeHidden();
    }
  });

  for (const element of ELEMENTS) {
    test(`${element.name}: the default can be removed and restored`, async ({ page }) => {
      const frame = await openModule(page, 'example_default_filters');
      await clickResetView(page, frame);
      await openFilters(frame);

      await element.remove(frame);
      await expect(element.revert(frame)).toBeVisible();
      await submitFilters(frame);
      expect(await rowUids(frame)).toEqual([...DEFAULT_ROWS, element.excludedUid].sort((a, b) => Number(a) - Number(b)));

      await openFilters(frame);
      await element.revert(frame).click();
      await element.expectDefault(frame);
      await expect(element.revert(frame)).toBeHidden();
      await submitFilters(frame);
      expect(await rowUids(frame)).toEqual(DEFAULT_ROWS);
    });
  }
});
