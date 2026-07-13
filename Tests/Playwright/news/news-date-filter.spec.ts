import { test, expect, FrameLocator, Page } from '@playwright/test';
import {loginAsAdmin, openModule, toggleColumn, waitForReload} from '../helpers/typo3-backend';

/**
* Tests date-field filtering for all three TCA datetime dbType variants added via
* the xima/extended-news content block:
*
*   content_blocks_timestamp  – integer Unix timestamp (dbType: int → epoch-space comparison)
*   content_blocks_date       – SQL DATE string        (dbType: date → DATE() comparison)
*   content_blocks_datetime   – SQL DATETIME string    (dbType: datetime → DATE() comparison)
*
* Fixture values (set in tx_news_domain_model_news.sql):
*   uid=1  2024-01-15  (timestamp at midnight, date '2024-01-15', datetime '2024-01-15 10:00:00')
*   uid=2  2024-01-15  (timestamp at midnight, date '2024-01-15', datetime '2024-01-15 14:30:00')
*   uid=3  2024-02-01  (timestamp at midnight, date '2024-02-01', datetime '2024-02-01 09:15:00')
*   all others → NULL / 0
*/

const DATE_FIELDS: Array<{ field: string; label: string }> = [
    { field: 'content_blocks_timestamp', label: 'integer Unix timestamp' },
    { field: 'content_blocks_date',      label: 'SQL DATE' },
    { field: 'content_blocks_datetime',  label: 'SQL DATETIME' },
];

test.describe('News Date Filter', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
    });

    async function applyDateFilter(
        page: Page,
        contentFrame: FrameLocator,
        field: string,
        expr: string,
        date: string,
        endDate: string = '',
    ): Promise<void> {
        // Use the expr <select> for visibility checks — the value inputs are hidden
        // fields driven by the flatpickr range picker, so isVisible() is always false.
        const exprSelect = contentFrame.locator(`select[name="filter[${field}][expr]"]`);

        // Column might not be active yet — enable it via the columns modal
        if (!await exprSelect.isVisible()) {
            await toggleColumn(page, contentFrame, field);
        }

        // Filter panel may be collapsed (independently of column state)
        if (!await exprSelect.isVisible()) {
            await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
            await exprSelect.waitFor({ state: 'visible', timeout: 5000 });
        }

        await exprSelect.selectOption(expr);

        // Clear all other date-range hidden inputs so stale saved filters from previous
        // tests don't get submitted alongside and reduce the result set to 0.
        const targetInputName = `filter[${field}][value]`;
        await contentFrame.locator('input[data-daterange-start], input[data-daterange-end]').evaluateAll(
            (els: HTMLInputElement[], targetName: string) =>
                els.forEach(el => { if (el.name !== targetName) el.value = ''; }),
            targetInputName,
        );
        await contentFrame.locator(`input[name="${targetInputName}"]`).evaluate(
            (el: HTMLInputElement, val: string) => { el.value = val; },
            date,
        );
        // Set (or clear) the range upper bound.
        await contentFrame.locator(`input[name="filter[${field}][valueEnd]"]`).evaluate(
            (el: HTMLInputElement, val: string) => { el.value = val; },
            endDate,
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

    for (const { field, label } of DATE_FIELDS) {
        test(`${field} (${label}): eq returns only records matching that date`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'eq', '2024-01-15');

            const uids = await visibleUids(contentFrame);
            expect(uids, `eq 2024-01-15 must include uid=1`).toContain('1');
            expect(uids, `eq 2024-01-15 must include uid=2`).toContain('2');
            expect(uids, `eq 2024-01-15 must not include uid=3`).not.toContain('3');
            expect(uids.length, `eq 2024-01-15 must return exactly 2 records`).toBe(2);
        });

        test(`${field} (${label}): gt excludes boundary date and earlier`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'gt', '2024-01-15');

            const uids = await visibleUids(contentFrame);
            expect(uids, `gt 2024-01-15 must include uid=3`).toContain('3');
            expect(uids, `gt 2024-01-15 must not include uid=1`).not.toContain('1');
            expect(uids, `gt 2024-01-15 must not include uid=2`).not.toContain('2');
        });

        test(`${field} (${label}): lt excludes boundary date and later`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'lt', '2024-02-01');

            const uids = await visibleUids(contentFrame);
            expect(uids, `lt 2024-02-01 must include uid=1`).toContain('1');
            expect(uids, `lt 2024-02-01 must include uid=2`).toContain('2');
            // uid=3 (2024-02-01) must NOT appear — the boundary date itself is not strictly less than
            expect(uids, `lt 2024-02-01 must not include uid=3`).not.toContain('3');
        });

        test(`${field} (${label}): between returns only records within the range`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'between', '2024-01-01', '2024-01-31');

            const uids = await visibleUids(contentFrame);
            expect(uids, `between Jan must include uid=1`).toContain('1');
            expect(uids, `between Jan must include uid=2`).toContain('2');
            expect(uids, `between Jan must not include uid=3 (2024-02-01)`).not.toContain('3');
            expect(uids.length, `between Jan must return exactly 2 records`).toBe(2);
        });

        test(`${field} (${label}): between includes both inclusive boundary dates`, async ({ page }) => {
            const contentFrame = await openModule(page, 'example_news');
            await applyDateFilter(page, contentFrame, field, 'between', '2024-01-15', '2024-02-01');

            const uids = await visibleUids(contentFrame);
            expect(uids, `between must include lower-boundary uid=1`).toContain('1');
            expect(uids, `between must include lower-boundary uid=2`).toContain('2');
            expect(uids, `between must include upper-boundary uid=3`).toContain('3');
            expect(uids.length, `between 15 Jan–1 Feb must return exactly 3 records`).toBe(3);
        });
    }

    // Exercises the actual flatpickr wiring (init + range mode + onChange → Y-m-d
    // hidden inputs), which the applyDateFilter helper bypasses by setting the
    // hidden fields directly. Driving the live instance verifies the JS path.
    test('range picker drives the hidden value/valueEnd inputs and filters records', async ({ page }) => {
        const field = 'content_blocks_date';
        const contentFrame = await openModule(page, 'example_news');

        const exprSelect = contentFrame.locator(`select[name="filter[${field}][expr]"]`);
        if (!await exprSelect.isVisible()) {
            await toggleColumn(page, contentFrame, field);
        }
        if (!await exprSelect.isVisible()) {
            await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
            await exprSelect.waitFor({ state: 'visible', timeout: 5000 });
        }
        await exprSelect.selectOption('between');

        // Clear other date-range inputs so stale saved filters don't leak in.
        await contentFrame.locator('input[data-daterange-start], input[data-daterange-end]').evaluateAll(
            (els: HTMLInputElement[], keep: string) =>
                els.forEach(el => { if (el.name !== keep) el.value = ''; }),
            `filter[${field}][value]`,
        );

        // Drive the live flatpickr instance the module attached to the display input.
        await contentFrame.locator(`#daterange-${field}`).evaluate((el: HTMLInputElement) => {
            (el as any)._flatpickr.setDate(['2024-01-01', '2024-01-31'], true);
        });

        // The onChange handler must have written both bounds as Y-m-d.
        await expect(contentFrame.locator(`input[name="filter[${field}][value]"]`)).toHaveValue('2024-01-01');
        await expect(contentFrame.locator(`input[name="filter[${field}][valueEnd]"]`)).toHaveValue('2024-01-31');

        await contentFrame.locator('button[type="submit"][name="search"]').first().click();
        await waitForReload(contentFrame);

        const uids = await visibleUids(contentFrame);
        expect(uids, `picker range Jan must include uid=1`).toContain('1');
        expect(uids, `picker range Jan must include uid=2`).toContain('2');
        expect(uids, `picker range Jan must not include uid=3`).not.toContain('3');
        expect(uids.length, `picker range Jan must return exactly 2 records`).toBe(2);
    });
});
