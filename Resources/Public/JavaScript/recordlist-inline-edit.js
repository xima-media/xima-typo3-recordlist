import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";

class RecordlistInlineEdit {

    saveClick = false

    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('.text-inline-edit input').forEach(input => {
            input.addEventListener('keyup', this.onInputKeydown.bind(this));
            input.addEventListener('keydown', this.onInputKeyup.bind(this));
            input.addEventListener('blur', this.onInputBlur.bind(this));
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
            const input = e.currentTarget;
            this.postSave(input).then(() => {
               input.blur();
            });
        }

        // Escape
        if (e.keyCode === 27) {
            e.preventDefault();
            const input = e.currentTarget;
            input.value = input.dataset.originalValue;
            input.closest('.text-inline-edit').classList.remove('changed');
        }
    }

    onInputKeydown(e) {
        const originalValue = e.currentTarget.dataset.originalValue;
        const newValue = e.currentTarget.value;
        if (newValue !== originalValue) {
            e.currentTarget.parentElement.classList.add('changed');
        } else {
            e.currentTarget.parentElement.classList.remove('changed');
        }
    }

    onAbortClick(e) {
        e.preventDefault();
        const input = e.currentTarget.closest('.text-inline-edit').querySelector('input');
        input.value = input.dataset.originalValue;
        e.currentTarget.closest('.text-inline-edit').classList.remove('changed');
    }

    onInputBlur(e) {
        const input = e.currentTarget
        setTimeout(() => {
            if (this.saveClick) {
                return;
            }
            e.preventDefault();
            input.value = input.dataset.originalValue;
            input.closest('.text-inline-edit').classList.remove('changed');
        }, 100);

    }

    async postSave(input) {
        const div = input.closest('.text-inline-edit');
        const button = div.querySelector('button[data-action="save"]');
        const tr = input.closest('tr');

        Icons.getIcon('spinner-circle', Icons.sizes.small, null, 'disabled').then(icon => {
            button.disabled = true;
            button.innerHTML = icon
        })

        const table = tr.dataset.table;
        const uid = tr.dataset.uid;
        const column = div.dataset.column;
        const newValue = input.value;

        const payload = new FormData();
        payload.append('table', table);
        payload.append('uid', uid);
        payload.append('column', column);
        payload.append('newValue', newValue);

        return new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_inline_edit).post('', {body: payload}).then(() => {
            input.dataset.originalValue = newValue;
            div.classList.remove('changed');
        }).finally(() => {
            button.disabled = false;
            Icons.getIcon('actions-check', Icons.sizes.small, null).then(icon => {
                button.innerHTML = icon
            })
            this.saveClick = false;
        }).catch((error) => {
            console.log(error);
            Notification.error('Error', 'An error occurred while saving the data');
        })
    }

    onSaveClick(e) {
        e.preventDefault();
        this.saveClick = true;

        const button = e.currentTarget
        const div = button.closest('.text-inline-edit');
        const input = div.querySelector('input');

        this.postSave(input);
    }
}

new RecordlistInlineEdit();
