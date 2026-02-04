import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";

class RecordlistInlineEdit {

    saveClick = false

    constructor() {
        this.selectors = {
            checkbox: '.t3js-multi-record-selection-check',
            checkboxChecked: '.t3js-multi-record-selection-check:checked',
            tableRow: '[data-uid][data-table]'
        };
        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('.text-inline-edit span').forEach(span => {
            span.addEventListener('input', this.onInputKeydown.bind(this))
            span.addEventListener('keydown', this.onInputKeyup.bind(this))
            span.addEventListener('blur', this.onInputBlur.bind(this))
        })

        document.querySelectorAll('.text-inline-edit button[data-action="close"]').forEach(button => {
            button.addEventListener('click', this.onAbortClick.bind(this));
        })

        document.querySelectorAll('.text-inline-edit button[data-action="save"]').forEach(button => {
            button.addEventListener('click', this.onSaveClick.bind(this));
        })
    }

    onInputKeyup(e) {
        // Enter
        if (e.keyCode === 13) {
            e.preventDefault();
            const span = e.currentTarget;
            this.postSave(span).then(() => {
              span.blur();
            });
        }

        // Escape
        if (e.keyCode === 27) {
            e.preventDefault();
            const span = e.currentTarget;
            const tr = span.closest('tr');
            const div = span.closest('.text-inline-edit');
            const column = div.dataset.column;

            // Restore current row
            span.innerText = span.dataset.originalValue;
            div.classList.remove('changed');

            // Restore selected rows
            this.restoreSelectedRows(tr, column);

            span.blur();
        }
    }

    onInputKeydown(e) {
        const span = e.currentTarget;
        const originalValue = span.dataset.originalValue;
        const newValue = span.innerText;
        const div = span.closest('.text-inline-edit');
        const tr = span.closest('tr');
        const column = div.dataset.column;

        // Update changed state
        if (newValue !== originalValue) {
            div.classList.add('changed');
        } else {
            div.classList.remove('changed');
        }

        // Live update selected rows (silently, without showing buttons)
        const selectedRows = this.getSelectedRows(tr);
        if (selectedRows.length > 1) {
            selectedRows.forEach(rowData => {
                // Skip the current row (already being edited)
                if (rowData.uid === tr.dataset.uid && rowData.table === tr.dataset.table) {
                    return;
                }

                // Update the cell text in this row without triggering changed state
                const cell = rowData.row.querySelector(`[data-column="${column}"] span.inline-edit`);
                if (cell) {
                    cell.innerText = newValue;
                    // Don't add "changed" class to prevent buttons from showing
                }
            });
        }
    }

    onAbortClick(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent row selection when clicking abort button
        const button = e.currentTarget;
        const div = button.closest('.text-inline-edit');
        const span = div.querySelector('span.inline-edit');
        const tr = span.closest('tr');
        const column = div.dataset.column;

        // Restore current row
        span.innerText = span.dataset.originalValue;
        div.classList.remove('changed');

        // Restore selected rows
        this.restoreSelectedRows(tr, column);
    }

    onInputBlur(e) {
        const span = e.currentTarget
        setTimeout(() => {
            if (this.saveClick) {
                this.saveClick = false;
                return;
            }
            e.preventDefault();
            const tr = span.closest('tr');
            const div = span.closest('.text-inline-edit');
            const column = div.dataset.column;

            // Restore current row
            span.innerText = span.dataset.originalValue;
            div.classList.remove('changed');

            // Restore selected rows
            this.restoreSelectedRows(tr, column);
        }, 300);
    }

    /**
    * Restore original values for all selected rows
    */
    restoreSelectedRows(tr, column) {
        const selectedRows = this.getSelectedRows(tr);
        if (selectedRows.length > 1) {
            selectedRows.forEach(rowData => {
                const cell = rowData.row.querySelector(`[data-column="${column}"] span.inline-edit`);
                if (cell) {
                    cell.innerText = cell.dataset.originalValue;
                    cell.closest('.text-inline-edit')?.classList.remove('changed');
                }
            });
        }
    }

    /**
    * Unselect all selected rows
    */
    unselectAllRows() {
        const checkedCheckboxes = document.querySelectorAll(this.selectors.checkboxChecked);
        checkedCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    /**
    * Get selected rows for batch editing
    * Returns array of {table, uid} objects
    */
    getSelectedRows(currentRow) {
        const selectedRows = [];

        // Check if there are any selected checkboxes
        const checkedCheckboxes = document.querySelectorAll(this.selectors.checkboxChecked);

        if (checkedCheckboxes.length === 0) {
            return selectedRows;
        }

        // Check if the current row is selected
        const currentCheckbox = currentRow.querySelector(this.selectors.checkbox);
        if (!currentCheckbox || !currentCheckbox.checked) {
            return selectedRows;
        }

        // Collect all selected rows
        checkedCheckboxes.forEach(checkbox => {
            const row = checkbox.closest(this.selectors.tableRow);
            if (row && row.dataset.uid && row.dataset.table) {
                selectedRows.push({
                    table: row.dataset.table,
                    uid: row.dataset.uid,
                    workspaceId: row.getAttribute('data-t3ver_wsid'),
                    row: row
                });
            }
        });

        return selectedRows;
    }

    async postSave(span) {
        const div = span.closest('.text-inline-edit');
        const button = div.querySelector('button[data-action="save"]');
        const tr = span.closest('tr');

        Icons.getIcon('spinner-circle', Icons.sizes.small, null, 'disabled').then(icon => {
            button.disabled = true;
            button.innerHTML = icon
        })

        const column = div.dataset.column;
        const newValue = span.innerText;

        // Check if we should do batch editing
        const selectedRows = this.getSelectedRows(tr);

        if (selectedRows.length > 1) {
            // Batch editing for multiple selected rows
            return this.postSaveBatch(selectedRows, column, newValue, span, button, div);
        } else {
            // Single row editing
            return this.postSaveSingle(tr, column, newValue, span, button, div);
        }
    }

    async postSaveSingle(tr, column, newValue, span, button, div) {
        const workspaceId = tr.getAttribute('data-t3ver_wsid');
        const table = tr.dataset.table;
        const uid = tr.dataset.uid;

        const payload = new FormData();
        payload.append('table', table);
        payload.append('uid', uid);
        payload.append('column', column);
        payload.append('newValue', newValue);

        return new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_inline_edit)
          .withQueryArguments({workspaceId: workspaceId})
          .post('', {body: payload})
          .then(() => {
            span.dataset.originalValue = newValue;
            div.classList.remove('changed');
            // Unselect all rows after successful save
            this.unselectAllRows();
        }).finally(() => {
            button.disabled = false;
            Icons.getIcon('actions-check', Icons.sizes.small, null).then(icon => {
                button.innerHTML = icon
            })
            this.saveClick = false;
        }).catch((error) => {
            Notification.error(
                TYPO3.lang['inlineEdit.error.title'],
                TYPO3.lang['inlineEdit.error.message']
            );
        })
    }

    async postSaveBatch(selectedRows, column, newValue, originalSpan, originalButton, originalDiv) {
        const totalRows = selectedRows.length;
        let successCount = 0;
        let errorCount = 0;

        Notification.info(
            TYPO3.lang['inlineEdit.batchEdit.title'],
            TYPO3.lang['inlineEdit.batchEdit.saving'].replace('%d', totalRows)
        );

        // Save all selected rows
        const savePromises = selectedRows.map(async (rowData) => {
            const payload = new FormData();
            payload.append('table', rowData.table);
            payload.append('uid', rowData.uid);
            payload.append('column', column);
            payload.append('newValue', newValue);

            try {
                await new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_inline_edit)
                    .withQueryArguments({workspaceId: rowData.workspaceId})
                    .post('', {body: payload});

                successCount++;

                // Update the cell in the DOM for this row
                const cell = rowData.row.querySelector(`[data-column="${column}"] span.inline-edit`);
                if (cell) {
                    cell.innerText = newValue;
                    cell.dataset.originalValue = newValue;
                    cell.closest('.text-inline-edit')?.classList.remove('changed');
                }
            } catch (error) {
                errorCount++;
            }
        });

        return Promise.all(savePromises).then(() => {
            // Show final notification
            if (errorCount === 0) {
                Notification.success(
                    TYPO3.lang['inlineEdit.batchEdit.title'],
                    TYPO3.lang['inlineEdit.batchEdit.success'].replace('%d', successCount)
                );
            } else if (successCount > 0) {
                Notification.warning(
                    TYPO3.lang['inlineEdit.batchEdit.title'],
                    TYPO3.lang['inlineEdit.batchEdit.partialSuccess']
                        .replace('%d', successCount)
                        .replace('%d', errorCount)
                );
            } else {
                Notification.error(
                    TYPO3.lang['inlineEdit.batchEdit.title'],
                    TYPO3.lang['inlineEdit.batchEdit.allFailed'].replace('%d', totalRows)
                );
            }

            // Reset original button
            originalButton.disabled = false;
            Icons.getIcon('actions-check', Icons.sizes.small, null).then(icon => {
                originalButton.innerHTML = icon
            })
            this.saveClick = false;

            // Update original span
            originalSpan.dataset.originalValue = newValue;
            originalDiv.classList.remove('changed');

            // Unselect all rows after successful batch save
            this.unselectAllRows();
        });
    }

    onSaveClick(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent row selection when clicking save button
        this.saveClick = true;

        const button = e.currentTarget
        const div = button.closest('.text-inline-edit');
        const span = div.querySelector('span');

        this.postSave(span);
    }
}

new RecordlistInlineEdit();
