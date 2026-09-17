import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule, toggleColumn } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// tt_content is typed via `CType`. The fixtures hold three `text` elements, whose type has `bodytext`
// in its showitem, and one `header` element, whose type has not — all four carry a body text in the
// database, so only the type decides whether the cell is filled.
// Column labels differ between TYPO3 versions, so a column is located by the order links of its
// header, which carry the column name.
async function columnCells(contentFrame: FrameLocator, columnName: string): Promise<string[]> {
  const column = await contentFrame.locator('thead th').evaluateAll(
    (cells, name) => cells.findIndex(cell => cell.querySelector(`a[data-order-field="${name}"]`)) + 1,
    columnName
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

    expect(await columnCells(contentFrame, 'bodytext')).toEqual([
      'First body text',
      'Second body text',
      'Third body text',
      '',
    ]);
  });

  // `crdate` is a column of the table that no type lists in its showitem. It belongs to every record
  // regardless of type and must keep its value for all of them.
  test('a column no record type configures keeps its value', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_content');
    await toggleColumn(page, contentFrame, 'crdate');

    const cells = await columnCells(contentFrame, 'crdate');
    expect(cells).toHaveLength(4);
    for (const cell of cells) {
      expect(cell).not.toBe('');
    }
  });
});
