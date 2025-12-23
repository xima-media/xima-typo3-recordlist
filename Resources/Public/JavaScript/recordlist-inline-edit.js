import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";

class RecordlistInlineEdit {

    saveClick = false

    constructor() {
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
            span.innerText = span.dataset.originalValue;
            span.closest('.text-inline-edit').classList.remove('changed');
            span.blur();
        }
    }

    onInputKeydown(e) {
        const originalValue = e.currentTarget.dataset.originalValue;
        const newValue = e.currentTarget.innerText;
        if (newValue !== originalValue) {
            e.currentTarget.parentElement.classList.add('changed');
        } else {
            e.currentTarget.parentElement.classList.remove('changed');
        }
    }

    onAbortClick(e) {
        e.preventDefault();
        const span = e.currentTarget.closest('.text-inline-edit').querySelector('span.inline-edit');
        span.innerText = span.dataset.originalValue;
        e.currentTarget.closest('.text-inline-edit').classList.remove('changed');
    }

    onInputBlur(e) {
        const span = e.currentTarget
        setTimeout(() => {
            if (this.saveClick) {
                this.saveClick = false;
                return;
            }
            e.preventDefault();
            span.innerText = span.dataset.originalValue;
            span.closest('.text-inline-edit').classList.remove('changed');
        }, 300);
    }

    async postSave(span) {
        const div = span.closest('.text-inline-edit');
        const button = div.querySelector('button[data-action="save"]');
        const tr = span.closest('tr');

        Icons.getIcon('spinner-circle', Icons.sizes.small, null, 'disabled').then(icon => {
            button.disabled = true;
            button.innerHTML = icon
        })

        const workspaceId = tr.getAttribute('data-t3ver_wsid');
        const table = tr.dataset.table;
        const uid = tr.dataset.uid;
        const column = div.dataset.column;
        const newValue = span.innerText;

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
        }).finally(() => {
            button.disabled = false;
            Icons.getIcon('actions-check', Icons.sizes.small, null).then(icon => {
                button.innerHTML = icon
            })
            this.saveClick = false;
        }).catch((error) => {
            Notification.error('Error', 'An error occurred while saving the data');
        })
    }

    onSaveClick(e) {
        e.preventDefault();
        this.saveClick = true;

        const button = e.currentTarget
        const div = button.closest('.text-inline-edit');
        const span = div.querySelector('span');

        this.postSave(span);
    }
}

new RecordlistInlineEdit();
