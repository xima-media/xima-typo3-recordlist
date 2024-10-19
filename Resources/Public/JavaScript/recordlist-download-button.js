import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'

export default class RecordlistDownloadButton {

    table = ''
    columnConfiguration = []

    constructor(table, columnsConfiguration) {
        this.table = table
        this.columnConfiguration = columnsConfiguration

        if (!this.cacheDom()) {
            return
        }

        this.initEventListener()
    }

    cacheDom() {
        this.downloadButtons = document.querySelectorAll('.recordlist-download-button')
        return this.downloadButtons.length;
    }

    initEventListener() {
        this.downloadButtons.forEach(btn => {
            btn.addEventListener('click', this.onButtonClick.bind(this))
        })
    }

    onButtonClick(e) {
        e.preventDefault()

        const modal = Modal.advanced({
            content: TYPO3.settings.ajaxUrls.xima_recordlist_downloadsettings,
            title: 'Download records',
            severity: SeverityEnum.notice,
            size: Modal.sizes.small,
            type: Modal.types.ajax,
            buttons: [
                {
                    text: 'Close',
                    active: true,
                    btnClass: 'btn-default',
                    name: 'cancel',
                    trigger: () => Modal.dismiss()
                },
                {
                    text: 'Download',
                    btnClass: 'btn-primary',
                    name: 'download',
                    trigger: () => {
                        const form = modal.querySelector('form')
                        form.submit()
                        modal.dismiss()
                    }
                }
            ],
            ajaxCallback: () => {

            }
        })
    }
}
