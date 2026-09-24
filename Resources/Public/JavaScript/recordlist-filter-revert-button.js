import "@typo3/backend/element/icon-element.js";

/**
* Button that puts a filter's default value back. It carries core's `close`
* class so that inside a clearable wrapper it sits exactly where the clear
* button does; both toggle via `visibility`, and only one of them is ever shown.
* @param {string} label
* @returns {HTMLButtonElement}
*/
export function createRevertButton(label) {
  const button = document.createElement('button');
  button.type = 'button';
  button.title = label;
  button.ariaLabel = label;
  button.classList.add('close', 'xima-recordlist-filter-revert');
  button.innerHTML = '<typo3-backend-icon identifier="actions-undo" size="small"></typo3-backend-icon>';
  button.style.visibility = 'hidden';
  return button;
}

/**
* @param {HTMLButtonElement} button
* @param {boolean} visible
*/
export function toggleRevertButton(button, visible) {
  button.style.visibility = visible ? 'visible' : 'hidden';
}

/**
* Restores the default operator of a filter, if it has one.
* @param {HTMLElement} container element carrying `data-default-expr`
* @param {HTMLSelectElement|null} exprSelect
*/
export function restoreDefaultExpr(container, exprSelect) {
  const expr = container.dataset.defaultExpr || '';
  if (!exprSelect || expr === '' || exprSelect.value === expr) {
    return;
  }
  exprSelect.value = expr;
  exprSelect.dispatchEvent(new Event('change', { bubbles: true }));
}
