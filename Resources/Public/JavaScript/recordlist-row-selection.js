/**
* RecordlistRowSelection
* Handles row selection in recordlist tables via checkbox clicks, row clicks, and multi-selection controls
*/
export default class RecordlistRowSelection {
  constructor() {
    this.selectors = {
      table: '.recordlist table',
      row: '.recordlist table tbody tr',
      checkbox: '.t3js-multi-record-selection-check',
      checkboxCell: '.col-checkbox',
      multiSelectToggle: '.t3js-multi-record-selection-check-actions-toggle',
      multiSelectActions: '.t3js-multi-record-selection-check-actions',
      checkAllButton: '[data-multi-record-selection-check-action="check-all"]',
      checkNoneButton: '[data-multi-record-selection-check-action="check-none"]',
      toggleButton: '[data-multi-record-selection-check-action="toggle"]'
    };

    this.activeClass = 'active';
    this.lastChecked = null;

    this.init();
  }

  init() {
    this.registerCheckboxListeners();
    this.registerRowClickListeners();
    this.registerMultiSelectListeners();
    this.updateMultiSelectButtons();
    this.restoreSelectionState();
  }

  /**
  * Register listeners for checkbox changes
  */
  registerCheckboxListeners() {
    document.querySelectorAll(this.selectors.checkbox).forEach(checkbox => {
      checkbox.addEventListener('change', this.onCheckboxChange.bind(this));
      checkbox.addEventListener('click', this.onCheckboxClick.bind(this));
    });
  }

  /**
  * Register listeners for row clicks
  */
  registerRowClickListeners() {
    document.querySelectorAll(this.selectors.row).forEach(row => {
      row.addEventListener('click', this.onRowClick.bind(this));
    });
  }

  /**
  * Register listeners for multi-select buttons (check all, uncheck all, toggle)
  */
  registerMultiSelectListeners() {
    // Check All button
    const checkAllButton = document.querySelector(this.selectors.checkAllButton);
    if (checkAllButton) {
      checkAllButton.addEventListener('click', this.onCheckAll.bind(this));
    }

    // Check None button
    const checkNoneButton = document.querySelector(this.selectors.checkNoneButton);
    if (checkNoneButton) {
      checkNoneButton.addEventListener('click', this.onCheckNone.bind(this));
    }

    // Toggle button
    const toggleButton = document.querySelector(this.selectors.toggleButton);
    if (toggleButton) {
      toggleButton.addEventListener('click', this.onToggleSelection.bind(this));
    }

    // Update button states when dropdown opens
    const multiSelectToggle = document.querySelector(this.selectors.multiSelectToggle);
    if (multiSelectToggle) {
      multiSelectToggle.addEventListener('click', this.updateMultiSelectButtons.bind(this));
    }
  }

  /**
  * Handle checkbox change event
  */
  onCheckboxChange(event) {
    const checkbox = event.target;
    const row = checkbox.closest(this.selectors.row);

    if (row) {
      this.updateRowState(row, checkbox.checked);
    }

    this.updateMultiSelectButtons();
  }

  /**
  * Handle checkbox click with keyboard modifiers (Shift, Ctrl, Alt)
  */
  onCheckboxClick(event) {
    const checkbox = event.target;

    // Handle Shift+Click for range selection
    if (event.shiftKey && this.lastChecked && this.lastChecked !== checkbox) {
      this.selectRange(this.lastChecked, checkbox);
    }

    // Handle Ctrl+Click or Alt+Click to toggle all others
    if (event.ctrlKey || event.altKey) {
      this.toggleAllExcept(checkbox);
    }

    this.lastChecked = checkbox;
  }

  /**
  * Handle row click (excluding clicks on checkboxes, links, and buttons)
  */
  onRowClick(event) {
    const row = event.currentTarget;
    const target = event.target;

    // Ignore clicks on checkboxes, their parent cells, links, buttons, and inputs
    if (
      target.matches(this.selectors.checkbox) ||
      target.closest(this.selectors.checkboxCell) ||
      target.tagName === 'A' ||
      target.tagName === 'BUTTON' ||
      target.tagName === 'INPUT' ||
      target.closest('a, button, input')
    ) {
      return;
    }

    // Toggle the checkbox for this row
    const checkbox = row.querySelector(this.selectors.checkbox);
    if (checkbox) {
      checkbox.checked = !checkbox.checked;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  /**
  * Update row visual state based on checkbox state
  */
  updateRowState(row, isSelected) {
    if (isSelected) {
      row.classList.add(this.activeClass);
    } else {
      row.classList.remove(this.activeClass);
    }
  }

  /**
  * Select a range of checkboxes between two checkboxes
  */
  selectRange(startCheckbox, endCheckbox) {
    const allCheckboxes = Array.from(document.querySelectorAll(this.selectors.checkbox));
    const startIndex = allCheckboxes.indexOf(startCheckbox);
    const endIndex = allCheckboxes.indexOf(endCheckbox);

    const start = Math.min(startIndex, endIndex);
    const end = Math.max(startIndex, endIndex);

    const targetState = endCheckbox.checked;

    for (let i = start; i <= end; i++) {
      if (allCheckboxes[i] !== endCheckbox) {
        allCheckboxes[i].checked = targetState;
        allCheckboxes[i].dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  /**
  * Toggle all checkboxes except the specified one
  */
  toggleAllExcept(exceptCheckbox) {
    document.querySelectorAll(this.selectors.checkbox).forEach(checkbox => {
      if (checkbox !== exceptCheckbox) {
        checkbox.checked = !checkbox.checked;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  /**
  * Check all checkboxes
  */
  onCheckAll(event) {
    event.preventDefault();
    document.querySelectorAll(this.selectors.checkbox).forEach(checkbox => {
      if (!checkbox.checked) {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  /**
  * Uncheck all checkboxes
  */
  onCheckNone(event) {
    event.preventDefault();
    document.querySelectorAll(this.selectors.checkbox).forEach(checkbox => {
      if (checkbox.checked) {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change', { bubbles: true }));
      }
    });
  }

  /**
  * Toggle selection state of all checkboxes
  */
  onToggleSelection(event) {
    event.preventDefault();
    document.querySelectorAll(this.selectors.checkbox).forEach(checkbox => {
      checkbox.checked = !checkbox.checked;
      checkbox.dispatchEvent(new Event('change', { bubbles: true }));
    });
  }

  /**
  * Update the state of multi-select buttons (enable/disable)
  */
  updateMultiSelectButtons() {
    const allCheckboxes = document.querySelectorAll(this.selectors.checkbox);
    const checkedCheckboxes = document.querySelectorAll(this.selectors.checkbox + ':checked');
    const uncheckedCheckboxes = document.querySelectorAll(this.selectors.checkbox + ':not(:checked)');

    // Update Check All button
    const checkAllButton = document.querySelector(this.selectors.checkAllButton);
    if (checkAllButton) {
      checkAllButton.disabled = uncheckedCheckboxes.length === 0;
    }

    // Update Check None button
    const checkNoneButton = document.querySelector(this.selectors.checkNoneButton);
    if (checkNoneButton) {
      checkNoneButton.disabled = checkedCheckboxes.length === 0;
    }

    // Update Toggle button
    const toggleButton = document.querySelector(this.selectors.toggleButton);
    if (toggleButton) {
      toggleButton.disabled = allCheckboxes.length === 0;
    }
  }

  /**
  * Restore selection state on page load
  */
  restoreSelectionState() {
    document.querySelectorAll(this.selectors.checkbox + ':checked').forEach(checkbox => {
      const row = checkbox.closest(this.selectors.row);
      if (row) {
        this.updateRowState(row, true);
      }
    });
  }
}

// Initialize the row selection handler
new RecordlistRowSelection();
