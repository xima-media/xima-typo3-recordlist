import { test, expect, FrameLocator, Page } from '@playwright/test';
import { loginAsAdmin, openModule, toggleColumn, waitForReload } from '../helpers/typo3-backend';

/**
* Tests that ctrl-configured timestamp fields (crdate, tstamp) are available
* as columns and filters even when they have no TCA column definition.
*
* Fixture values (set in tx_news_domain_model_news.sql):
*   uid=1  crdate/tstamp 2024-01-15
*   uid=2  crdate/tstamp 2024-01-15
*   uid=3  crdate/tstamp 2024-02-01
*   all others → 0 (default)
*/

const CTRL_FIELDS: Array<{ field: string; label: string }> = [
    { field: 'crdate', label: 'Creation date' },
    { field: 'tstamp', label: 'Last modified' },
];

test.describe('News crdate/tstamp columns', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    for (const { field, label } of CTRL_FIELDS) {
        test(`${field}: column is available in the column picker`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');

            // Open columns modal and check the checkbox exists
            await contentFrame.locator('button.dropdown-toggle').filter({ hasText: 'View' }).click();
            await page.waitForTimeout(300);
            await contentFrame.locator('[data-doc-button="showColumnsButton"]').evaluate((el) => (el as HTMLElement).click());
            await page.locator('.modal').waitFor({ timeout: 5000 });

            const checkbox = page.locator(`input#select-column-${field}`);
            await expect(checkbox).toBeVisible();

            // Close modal without saving
            await page.locator('.modal button.btn-default, .modal button[data-dismiss="modal"], .modal button.close').first().click();
            await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {});
        });

        test(`${field}: date filter eq returns only records matching that date`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'eq', '2024-01-15');

            const uids = await visibleUids(contentFrame);
            expect(uids, `eq 2024-01-15 must include uid=1`).toContain('1');
            expect(uids, `eq 2024-01-15 must include uid=2`).toContain('2');
            expect(uids, `eq 2024-01-15 must not include uid=3`).not.toContain('3');
        });

        test(`${field}: date filter gt excludes boundary date and earlier`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'gt', '2024-01-15');

            const uids = await visibleUids(contentFrame);
            expect(uids, `gt 2024-01-15 must include uid=3`).toContain('3');
            expect(uids, `gt 2024-01-15 must not include uid=1`).not.toContain('1');
            expect(uids, `gt 2024-01-15 must not include uid=2`).not.toContain('2');
        });

        test(`${field}: date filter lt excludes boundary date and later`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'lt', '2024-02-01');

            const uids = await visibleUids(contentFrame);
            expect(uids, `lt 2024-02-01 must include uid=1`).toContain('1');
            expect(uids, `lt 2024-02-01 must include uid=2`).toContain('2');
            expect(uids, `lt 2024-02-01 must not include uid=3`).not.toContain('3');
        });
    }
});

async function applyDateFilter(
    page: Page,
    contentFrame: FrameLocator,
    field: string,
    expr: string,
    date: string,
): Promise<void> {
    const exprSelect = contentFrame.locator(`select[name="filter[${field}][expr]"]`);

    if (!await exprSelect.isVisible()) {
        await toggleColumn(page, contentFrame, field);
    }

    if (!await exprSelect.isVisible()) {
        await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
        await exprSelect.waitFor({ state: 'visible', timeout: 5000 });
    }

    await exprSelect.selectOption(expr);

    const targetInputName = `filter[${field}][value]`;
    await contentFrame.locator('input[data-date-type="date"]').evaluateAll(
        (els: HTMLInputElement[], targetName: string) =>
            els.forEach(el => { if (el.name !== targetName) el.value = ''; }),
        targetInputName,
    );
    await contentFrame.locator(`input[name="${targetInputName}"]`).evaluate(
        (el: HTMLInputElement, val: string) => { el.value = val; },
        date,
    );

    await contentFrame.locator('button[type="submit"][name="search"]').first().click();
    await waitForReload(contentFrame);
}

async function visibleUids(contentFrame: FrameLocator): Promise<string[]> {
    const rows = contentFrame.locator('tr[data-uid]');
    const n = await rows.count();
    const uids: string[] = [];
    for (let i = 0; i < n; i++) {
        const uid = await rows.nth(i).getAttribute('data-uid');
        if (uid) uids.push(uid);
    }
    return uids;
}
