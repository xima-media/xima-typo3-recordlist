/**
* RecordlistRowEditMultiple
* Handles multiple record editing from column header buttons
* - Edits specific field of selected rows (if any are selected)
* - Edits specific field of all visible rows (if none are selected)
* Based on TYPO3 core's recordlist.js
*/
export default class RecordlistRowEditMultiple {
  constructor() {
    this.selectors = {
      editMultipleButton: '.t3js-record-edit-multiple',
      tableRow: '[data-uid][data-table]',
      checkbox: '.t3js-multi-record-selection-check',
      checkboxChecked: '.t3js-multi-record-selection-check:checked'
    };

    this.init();
  }

  init() {
    this.registerEditMultipleListener();
  }

  /**
  * Register listener for edit multiple button clicks
  */
  registerEditMultipleListener() {
    document.addEventListener('click', (event) => {
      const button = event.target.closest(this.selectors.editMultipleButton);
      if (button) {
        this.onEditMultiple(event, button);
      }
    });
  }

  /**
  * Handle edit multiple button click
  */
  onEditMultiple(event, button) {
    event.preventDefault();

    // Get the table name from the button itself or find it from tbody rows
    let tableName = button.dataset.table;

    if (!tableName) {
      // Fallback: find table name from the first row in tbody
      const firstRow = document.querySelector(`${this.selectors.tableRow}`);
      if (firstRow && firstRow.dataset.table) {
        tableName = firstRow.dataset.table;
      }
    }

    if (!tableName) {
      console.error('RecordlistRowEditMultiple: No table name found');
      return;
    }

    // Get return URL from button data attribute
    const returnUrl = button.dataset.returnUrl || '';

    // Get columns-only from button data attribute
    let columnsOnly = [];
    try {
      const columnsOnlyData = button.dataset.columnsOnly;
      if (columnsOnlyData) {
        columnsOnly = JSON.parse(columnsOnlyData);
      }
    } catch (e) {
      console.error('RecordlistRowEditMultiple: Failed to parse columnsOnly', e);
    }

    // Collect UIDs of records to edit
    const uids = this.collectUids(tableName);

    if (uids.length === 0) {
      console.warn('RecordlistRowEditMultiple: No records found to edit');
      return;
    }

    // Build the edit URL
    const editUrl = this.buildEditUrl(tableName, uids, columnsOnly, returnUrl);

    // Navigate to edit form
    window.location.href = editUrl;
  }

  /**
  * Collect UIDs of records to edit
  * Returns selected records if any are checked, otherwise all visible records
  */
  collectUids(tableName) {
    const uids = [];

    // First, check if any checkboxes are selected
    const checkedCheckboxes = document.querySelectorAll(
      `${this.selectors.tableRow}[data-table="${tableName}"] ${this.selectors.checkboxChecked}`
    );

    if (checkedCheckboxes.length > 0) {
      // If checkboxes are selected, use only those records
      checkedCheckboxes.forEach(checkbox => {
        const row = checkbox.closest(this.selectors.tableRow);
        if (row && row.dataset.uid) {
          uids.push(row.dataset.uid);
        }
      });
    } else {
      // Otherwise, use all visible records in the table
      const allRows = document.querySelectorAll(
        `${this.selectors.tableRow}[data-table="${tableName}"]`
      );

      allRows.forEach(row => {
        if (row.dataset.uid) {
          uids.push(row.dataset.uid);
        }
      });
    }

    return uids;
  }

  /**
  * Build the FormEngine edit URL
  */
  buildEditUrl(tableName, uids, columnsOnly, returnUrl) {
    // Get current module name
    const currentModule = top.TYPO3?.ModuleMenu?.App?.getCurrentModule?.() || '';

    // Build base URL
    let url = top.TYPO3.settings.FormEngine.moduleUrl;

    // Add edit command with table and UIDs
    url += `&edit[${tableName}][${uids.join(',')}]=edit`;

    // Add current module
    if (currentModule) {
      url += `&module=${encodeURIComponent(currentModule)}`;
    }

    // Add return URL
    url += `&returnUrl=${this.getReturnUrl(returnUrl)}`;

    // Add columns-only parameter if specified
    if (columnsOnly.length > 0) {
      columnsOnly.forEach((columnName, index) => {
        url += `&columnsOnly[${tableName}][${index}]=${encodeURIComponent(columnName)}`;
      });
    }

    return url;
  }

  /**
  * Get properly encoded return URL
  * Falls back to current list frame location if not specified
  */
  getReturnUrl(returnUrl) {
    if (!returnUrl) {
      // Fall back to current location
      if (top.list_frame) {
        returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
      } else {
        returnUrl = window.location.pathname + window.location.search;
      }
    }

    return encodeURIComponent(returnUrl);
  }
}

// Initialize the edit multiple handler
new RecordlistRowEditMultiple();
