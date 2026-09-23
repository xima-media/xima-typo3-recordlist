import DocumentService from "@typo3/core/document-service.js";
import "@typo3/backend/input/clearable.js";
import { createRevertButton, restoreDefaultExpr, toggleRevertButton } from "@xima/recordlist/recordlist-filter-revert-button.js";

/**
* A filter with a default value can be emptied like any other filter. While it
* is empty, a revert button in the place of the clear button puts the default
* back into the field; like clearing, this only changes the form, the list
* updates on the next search. Date filters are handled by
* recordlist-filter-daterange.js, which owns their picker.
*/
class RecordlistFilterDefault {

  constructor() {
    DocumentService.ready().then(() => this.init());
  }

  init() {
    document.querySelectorAll('[data-filter-default]').forEach(container => {
      if (container.dataset.filterDefaultInitialized) {
        return;
      }
      container.dataset.filterDefaultInitialized = '1';
      switch (container.dataset.filterDefault) {
        case 'text':
          this.setupText(container);
          break;
        case 'select':
          this.setupSelect(container);
          break;
        case 'category':
          this.setupCategory(container);
          break;
      }
    });
  }

  async setupText(container) {
    const input = container.querySelector('input[name$="[value]"]');
    if (!input || typeof input.clearable !== 'function') {
      return;
    }
    await input.clearable();
    const button = createRevertButton(container.dataset.revertLabel || '');
    input.parentElement.appendChild(button);

    const update = () => toggleRevertButton(button, input.value === '');
    ['input', 'keyup', 'change', 'typo3:internal:clear'].forEach(type => input.addEventListener(type, update));
    update();

    button.addEventListener('click', e => {
      e.preventDefault();
      input.value = container.dataset.defaultValue || '';
      restoreDefaultExpr(container, this.exprSelect(container));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      input.dispatchEvent(new Event('keyup'));
      input.focus();
    });
  }

  setupSelect(container) {
    const select = container.querySelector('select[name$="[value]"]');
    if (!select) {
      return;
    }
    // Reuse core's clearable wrapper so the button lands where a clear button would.
    const wrapper = document.createElement('div');
    wrapper.classList.add('form-control-clearable-wrapper', 'xima-recordlist-filter-select');
    select.parentNode.insertBefore(wrapper, select);
    wrapper.appendChild(select);
    const button = createRevertButton(container.dataset.revertLabel || '');
    wrapper.appendChild(button);

    const update = () => toggleRevertButton(button, select.value === '');
    select.addEventListener('change', update);
    update();

    button.addEventListener('click', e => {
      e.preventDefault();
      select.value = container.dataset.defaultValue || '';
      restoreDefaultExpr(container, this.exprSelect(container));
      select.dispatchEvent(new Event('change', { bubbles: true }));
      select.focus();
    });
  }

  setupCategory(container) {
    const input = container.querySelector('input[name$="[value]"]');
    const label = container.querySelector('.form-label');
    if (!input || !label) {
      return;
    }
    // The tree has no clear button of its own, so the revert sits beside the label.
    const button = createRevertButton(container.dataset.revertLabel || '');
    button.classList.add('xima-recordlist-filter-revert--inline');
    label.after(button);

    const update = () => toggleRevertButton(button, input.value === '');
    input.addEventListener('change', update);
    update();

    button.addEventListener('click', e => {
      e.preventDefault();
      const tree = container.querySelector('typo3-backend-form-selecttree');
      if (!tree || !Array.isArray(tree.nodes)) {
        return;
      }
      const defaults = (container.dataset.defaultValue || '').split(',').filter(id => id !== '');
      // selectNode() toggles; going through it keeps the category element's
      // hidden input and its change event in sync with the tree.
      tree.nodes.forEach(node => {
        if (Boolean(node.checked) !== defaults.includes(String(node.identifier))) {
          tree.selectNode(node);
        }
      });
      restoreDefaultExpr(container, this.exprSelect(container));
    });
  }

  exprSelect(container) {
    return container.querySelector('select[name$="[expr]"]');
  }

}

new RecordlistFilterDefault();
