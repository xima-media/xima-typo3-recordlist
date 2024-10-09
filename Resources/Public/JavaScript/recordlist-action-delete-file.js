import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";
import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'

class RecordlistActionDeleteFile {

    saveClick = false

    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('a[data-delete-file]').forEach(button => {
            button.addEventListener('click', this.onDeleteClick.bind(this));
        })
    }

    onDeleteClick(e) {
        e.preventDefault();
        const button = e.currentTarget;
        Modal.confirm(TYPO3.lang['modal.delete.title'], TYPO3.lang['modal.delete.message'], SeverityEnum.warning, [
            {
                text: TYPO3.lang['modal.delete.button.cancel'],
                active: true,
                btnClass: 'btn-default',
                trigger: () => {
                    Modal.dismiss()
                }
            },
            {
                text: TYPO3.lang['modal.delete.button.confirm'],
                btnClass: 'btn-danger',
                trigger: () => {
                    this.deleteFile(button)
                    Modal.dismiss()
                }
            }
        ]);
    }

    deleteFile(button) {
        const tr = button.closest('tr');
        const uid = tr.dataset.uid;

        const payload = new FormData();
        payload.append('uid', uid);

        new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_delete_file).post('', {body: payload}).then(() => {
            Notification.success(TYPO3.lang['notification.delete.success.title'], TYPO3.lang['notification.delete.success.message']);
            tr.remove();
        }).catch(() => {
            Notification.error('Error', 'An error occurred while deleting the file');
        })
    }

}

new RecordlistActionDeleteFile();
