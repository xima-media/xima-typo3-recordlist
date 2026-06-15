import { test, expect } from '@playwright/test';
import type { FrameLocator } from '@playwright/test';
import { loginAsAdmin, openModule } from '../helpers/typo3-backend';
import { resetDatabase, resetUserPreferences } from '../helpers/db-reset';
import { trackConsoleErrors, ConsoleErrorTracker } from '../helpers/console-errors';

interface ActionSpec {
  group?: 'workspace' | 'translation';
  title?: string;
  // Mirrors a button whose Bootstrap tooltip already moved `title` → `data-bs-original-title`.
  originalTitle?: string;
  // A button that renders its own visible text label (e.g. Publish).
  text?: string;
}

// Inject a synthetic action cell mirroring the server markup: a button bar plus the two
// server-rendered dropdown shells (toggle + empty menu). The shipped module watches the
// recordlist via a MutationObserver and relocates the tagged buttons into the matching
// shell menu — keeping the test deterministic regardless of which actions a fixture row
// happens to render.
async function injectActions(contentFrame: FrameLocator, id: string, buttons: ActionSpec[]): Promise<void> {
  await contentFrame.locator('main.recordlist').evaluate((main, args) => {
    const cell = document.createElement('div');
    cell.id = args.id;
    cell.className = 'recordlist-actions';

    const container = document.createElement('div');
    container.className = 'btn-group';
    container.setAttribute('data-recordlist-actions', '');
    args.buttons.forEach(b => {
      const a = document.createElement('a');
      a.className = 'btn btn-default';
      a.href = '#';
      if (b.group) {
        a.setAttribute('data-action-group', b.group);
      }
      if (b.title) {
        a.setAttribute('title', b.title);
        a.setAttribute('data-bs-toggle', 'tooltip');
      }
      if (b.originalTitle) {
        // Mirrors a tooltip Bootstrap already initialised (title moved, toggle present).
        a.setAttribute('data-bs-original-title', b.originalTitle);
        a.setAttribute('data-bs-toggle', 'tooltip');
      }
      a.innerHTML = `<span class="icon"></span>${b.text ?? ''}`;
      container.appendChild(a);
    });
    cell.appendChild(container);

    const shell = (category: string, btnClass: string, chevron: boolean) => {
      const wrapper = document.createElement('div');
      wrapper.className = `btn-group dropdown recordlist-action-group recordlist-action-${category}`;
      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = `btn ${btnClass} dropdown-toggle`;
      toggle.setAttribute('data-bs-toggle', 'dropdown');
      toggle.innerHTML = `<span class="icon"></span>${chevron ? '<span class="recordlist-action-chevron"></span>' : ''}`;
      const menu = document.createElement('ul');
      menu.className = 'dropdown-menu dropdown-menu-end recordlist-action-group-menu';
      wrapper.appendChild(toggle);
      wrapper.appendChild(menu);
      return wrapper;
    };
    cell.appendChild(shell('translation', 'btn-default', false));
    cell.appendChild(shell('workspace', 'btn-primary', true));

    main.appendChild(cell);
  }, { id, buttons });
}

test.describe('Action group dropdowns (issue #92)', () => {
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

  test('workspace actions move into a primary, icon-only dropdown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActions(contentFrame, 'ws', [
      { group: 'workspace', originalTitle: 'Revert' },
      { group: 'workspace', text: 'Publish' },
    ]);

    const toggle = contentFrame.locator('#ws .recordlist-action-workspace > .dropdown-toggle');
    await expect(toggle).toBeVisible();
    await expect(toggle).toHaveClass(/btn-primary/);
    await expect(toggle).toHaveText('');
    // The workspace toggle carries an explicit chevron next to its icon.
    await expect(toggle.locator('.recordlist-action-chevron')).toHaveCount(1);
    await expect(contentFrame.locator('#ws .recordlist-action-workspace .recordlist-action-group-menu > li')).toHaveCount(2);
    // The dropdown is standalone — a sibling of the button bar, not inside it.
    await expect(contentFrame.locator('#ws > .recordlist-action-workspace')).toHaveCount(1);
    await expect(contentFrame.locator('#ws [data-recordlist-actions] .recordlist-action-workspace')).toHaveCount(0);
  });

  test('workspace dropdown is hidden when no workspace actions exist', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActions(contentFrame, 'ws0', [{ title: 'Edit' }, { title: 'View' }]);

    // The shell is rendered server-side but hidden via CSS when the row has no such action.
    await expect(contentFrame.locator('#ws0 .recordlist-action-workspace')).toBeHidden();
    await expect(contentFrame.locator('#ws0 .recordlist-action-translation')).toBeHidden();
  });

  test('multiple translation actions move into a default dropdown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActions(contentFrame, 'tr2', [
      { group: 'translation', originalTitle: 'Translate record' },
      { group: 'translation', originalTitle: 'Translate with DeepL' },
    ]);

    const toggle = contentFrame.locator('#tr2 .recordlist-action-translation > .dropdown-toggle');
    await expect(toggle).toBeVisible();
    await expect(toggle).toHaveClass(/btn-default/);
    // The translation toggle stays icon-only — no chevron.
    await expect(toggle.locator('.recordlist-action-chevron')).toHaveCount(0);
    await expect(contentFrame.locator('#tr2 .recordlist-action-translation .recordlist-action-group-menu > li')).toHaveCount(2);
  });

  test('a single translation action also moves into the dropdown', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActions(contentFrame, 'tr1', [{ group: 'translation', title: 'Translate record' }]);

    const toggle = contentFrame.locator('#tr1 .recordlist-action-translation > .dropdown-toggle');
    await expect(toggle).toBeVisible();
    await expect(contentFrame.locator('#tr1 .recordlist-action-translation .recordlist-action-group-menu > li')).toHaveCount(1);
    // Nothing of that category is left behind in the button bar.
    await expect(contentFrame.locator('#tr1 [data-recordlist-actions] [data-action-group="translation"]')).toHaveCount(0);
  });

  test('icon-only actions get text labels, text actions are left untouched', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    await injectActions(contentFrame, 'lbl', [
      { group: 'workspace', originalTitle: 'Revert' }, // icon-only → labelled
      { group: 'workspace', text: 'Publish' },         // already has text → not labelled
    ]);

    await contentFrame.locator('#lbl .recordlist-action-workspace > .dropdown-toggle').click();
    const menu = contentFrame.locator('#lbl .recordlist-action-workspace .recordlist-action-group-menu');
    const labels = menu.locator('.recordlist-action-label');
    await expect(labels).toHaveCount(1);
    await expect(labels).toHaveText('Revert');
    // Tooltips are stripped from the relocated actions (label replaces them).
    await expect(menu.locator('[data-bs-toggle="tooltip"]')).toHaveCount(0);
    await expect(menu.locator('[title]')).toHaveCount(0);
  });

  test('groupable actions are hidden until the bar is processed (no flash on load)', async ({ page }) => {
    const contentFrame = await openModule(page, 'example_beusers');
    // Inject an UNprocessed bar outside main.recordlist so the MutationObserver never
    // processes it — the CSS rule must hide its grouped-bound buttons regardless.
    await contentFrame.locator('body').evaluate(body => {
      const bar = document.createElement('div');
      bar.id = 'unprocessed-bar';
      bar.className = 'btn-group';
      bar.setAttribute('data-recordlist-actions', '');
      const action = document.createElement('a');
      action.className = 'btn btn-default';
      action.setAttribute('data-action-group', 'workspace');
      action.textContent = 'Publish';
      bar.appendChild(action);
      body.appendChild(bar);
    });

    await expect(contentFrame.locator('#unprocessed-bar [data-action-group]')).toBeHidden();
  });
});
