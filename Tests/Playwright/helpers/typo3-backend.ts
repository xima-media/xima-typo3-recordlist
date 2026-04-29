import { Page, FrameLocator } from '@playwright/test';

export async function loginAsAdmin(page: Page): Promise<void> {
  await page.goto('/typo3');
  await page.waitForSelector('#t3-username', { timeout: 10000 });
  await page.fill('#t3-username', 'admin');
  await page.fill('#t3-password', 'Passw0rd!');
  await page.click('#t3-login-submit-section > button');
  await page.waitForSelector('.scaffold-header', { timeout: 20000 });
}

export async function openModule(page: Page, moduleIdentifier: string): Promise<FrameLocator> {
  const contentFrame = page.frameLocator('iframe[name="list_frame"]');
  await page.click(`a[data-modulemenu-identifier="${moduleIdentifier}"]`);
  await contentFrame.locator('main.recordlist').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {});
  await contentFrame.locator('main.recordlist').waitFor({ state: 'visible', timeout: 10000 });
  return contentFrame;
}

export async function waitForReload(contentFrame: FrameLocator): Promise<void> {
  await contentFrame.locator('main.recordlist').waitFor({ state: 'detached', timeout: 3000 }).catch(() => {});
  await contentFrame.locator('main.recordlist').waitFor({ state: 'visible', timeout: 10000 });
}

export async function waitForSave(contentFrame: FrameLocator): Promise<void> {
  // _savedok submits the form — iframe reloads the edit page after save
  await contentFrame.locator('button[name="_savedok"]').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {});
  await contentFrame.locator('button[name="_savedok"]').waitFor({ state: 'visible', timeout: 10000 });
}

export async function getFirstRecordUid(contentFrame: FrameLocator): Promise<string> {
  const firstRow = contentFrame.locator('tr[data-uid]').first();
  return (await firstRow.getAttribute('data-uid')) as string;
}

export async function switchTable(contentFrame: FrameLocator, tableName: string): Promise<void> {
  const link = contentFrame.locator(`a.dropdown-item.dropdown-item-spaced[title="${tableName}"]`);
  const href = await link.getAttribute('href');
  // Link lives inside a CSS popover with no layout box — navigate the iframe's window directly
  await contentFrame.locator('html').evaluate((_, url) => window.location.assign(url), href as string);
  // TYPO3 reloads the iframe; wait for stale recordlist to detach, then reappear with new content
  await waitForReload(contentFrame);
}

export async function sortBy(contentFrame: FrameLocator, columnName: string, direction: 'ASC' | 'DESC' = 'ASC'): Promise<void> {
  // Sort links use href="#" with JS handlers — evaluate bypasses visibility (menu hidden behind custom dropdown-static)
  await contentFrame.locator(`thead th:has-text("${columnName}") a[data-order-direction="${direction}"]`).evaluate((el) => (el as HTMLElement).click());
  // Wait for page to reload with new sort order
  await waitForReload(contentFrame);
}

export async function searchFor(contentFrame: FrameLocator, searchTerm: string): Promise<void> {
  const searchField = contentFrame.locator('input[name="search_field"]');
  if (!await searchField.isVisible()) {
    await contentFrame.locator('.toggleFiltersButton:not(.hidden)').click();
    await searchField.waitFor({ state: 'visible', timeout: 5000 });
  }
  await searchField.fill(searchTerm);
  await searchField.press('Enter');
  await contentFrame.locator('main.recordlist').waitFor({ timeout: 5000 });
}

export async function openColumnsModal(page: Page, contentFrame: FrameLocator): Promise<void> {
  // "View" button is a CSS popover toggle — click it to show the dropdown
  await contentFrame.locator('button.dropdown-toggle').filter({ hasText: 'View' }).click();
  await page.waitForTimeout(300);
  // showColumnsButton is inside the popover (may have zero layout box in headless) — click via JS
  await contentFrame.locator('[data-doc-button="showColumnsButton"]').evaluate((el) => (el as HTMLElement).click());
  // Modal opens in the MAIN frame (TYPO3 Modal.advanced() uses top-level document)
  await page.locator('.modal').waitFor({ timeout: 5000 });
}

export async function toggleColumn(page: Page, contentFrame: FrameLocator, columnName: string): Promise<void> {
  await openColumnsModal(page, contentFrame);
  // Checkbox is in the modal (main frame) — use JS click to avoid styled-element interception
  await page.locator(`input#select-column-${columnName}`).evaluate((el) => (el as HTMLElement).click());
  await page.locator('.modal button.btn-primary').click();
  // Wait for modal to close before checking recordlist state
  await page.locator('.modal').waitFor({ state: 'detached', timeout: 5000 });
  // Modal save reloads the iframe — wait for recordlist to reappear
  await waitForReload(contentFrame);
}
