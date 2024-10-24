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

        console.log(document.querySelectorAll('form'))
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

        const downloadSettingFields = document.querySelector('#downloadSettingsForm')
        downloadSettingFields.querySelector('input[name="is_download"]').value = 1

        const modal = Modal.advanced({
            content: '',
            title: 'Download records',
            severity: SeverityEnum.notice,
            size: Modal.sizes.small,
            type: Modal.types.default,
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
                        const form = document.querySelector('form')
                        form.submit()
                        Modal.dismiss()
                    }
                }
            ],
            callback: () => {
                modal.querySelector('.modal-body').innerHTML = downloadSettingFields.innerHTML
                modal.querySelector('.modal-body').querySelectorAll('input[type="text"]').forEach(input => {
                    input.addEventListener('change', (e) => {
                        downloadSettingFields.querySelector(`input[name="${e.currentTarget.name}"]`).value = e.currentTarget.value
                    })
                })
                modal.querySelector('.modal-body').querySelectorAll('select').forEach(select => {
                    select.addEventListener('change', (e) => {
                        downloadSettingFields.querySelector(`select[name="${e.currentTarget.name}"]`).value = e.currentTarget.value
                    })
                })
                modal.querySelector('.modal-body').querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                    checkbox.addEventListener('change', (e) => {
                        downloadSettingFields.querySelector(`input[name="${e.currentTarget.name}"]`).checked = e.currentTarget.checked
                    })
                })
            }
        })

        modal.addEventListener('typo3-modal-hidden', () => {
            downloadSettingFields.querySelector('input[name="is_download"]').value = 0
        })
    }
}
