import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule, toggleColumn } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// tt_content is typed via `CType`. The fixtures hold three `text` elements, whose type has `bodytext`
// in its showitem, and one `header` element, whose type has not — all four carry a body text in the
// database, so only the type decides whether the cell is filled.
// The column label of `bodytext` differs between TYPO3 versions, so the column is located by the
// order links of its header, which carry the column name.
async function bodytextCells(contentFrame: FrameLocator): Promise<string[]> {
  const column = await contentFrame.locator('thead th').evaluateAll(
    cells => cells.findIndex(cell => cell.querySelector('a[data-order-field="bodytext"]')) + 1
  );
  expect(column).toBeGreaterThan(0);

  const cells = await contentFrame.locator(`tr[data-uid] td:nth-child(${column})`).allTextContents();
  return cells.map(text => text.trim());
}

test.describe('Columns outside a record type', () => {
  test.beforeAll(() => { resetDatabase(); });
  test.afterAll(() => { resetDatabase(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    consoleErrors = trackConsoleErrors(page);
    resetUserPreferences();
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('a column not configured for the record type stays empty', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');
    await toggleColumn(page, contentFrame, 'bodytext');

    expect(await bodytextCells(contentFrame)).toEqual([
      'First body text',
      'Second body text',
      'Third body text',
      '',
    ]);
  });
});
