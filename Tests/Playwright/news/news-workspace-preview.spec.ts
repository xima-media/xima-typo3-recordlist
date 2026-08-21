import { test, expect, FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

// Live uids the fixtures ship a workspace 1 draft version for, in the order the list renders them
const DRAFT_ORIGINAL_UIDS = ['59', '58'];

async function previewUri(contentFrame: FrameLocator, originalUid: string): Promise<string> {
  const row = contentFrame.locator(`tr[data-t3ver_oid="${originalUid}"]`);
  await expect(row).toHaveCount(1);
  return (await row.locator('a[target="_blank"]').first().getAttribute('href')) as string;
}

test.describe('News Workspace Preview', () => {
  test.beforeAll(() => { resetDatabase(); resetUserPreferences(); });

  let consoleErrors: ConsoleErrorTracker;
  test.beforeEach(async ({ page }) => {
    // core's workspace-state.js logs this when its in-flight request is aborted by the test teardown
    consoleErrors = trackConsoleErrors(page, [/Failed to fetch workspace info/]);
    await loginAsAdmin(page);
  });
  test.afterEach(() => { consoleErrors.assertNoErrors(); });

  test('every workspace draft is previewed in the frontend', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');

    for (const originalUid of DRAFT_ORIGINAL_UIDS) {
      const uri = await previewUri(contentFrame, originalUid);

      expect(uri, `preview URI of draft for record ${originalUid}`).not.toContain('/typo3/workspace/preview-control');
      expect(uri, `preview URI of draft for record ${originalUid}`).toContain('ADMCMD_prev=IGNORE');
      expect(uri, `preview URI of draft for record ${originalUid}`).toContain('workspaceId=1');
    }
  });

  test('live records keep a plain frontend preview', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_news');
    const uri = await previewUri(contentFrame, '60');

    expect(uri).not.toContain('ADMCMD_prev');
    expect(uri).not.toContain('workspaceId');
  });
});
