import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'

export default class RecordlistDocShowColumns {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll(".showColumnsButton").forEach(link => {
      link.addEventListener("click", this.onShowColumnsClick.bind(this));
    });
  }

  onShowColumnsClick(e) {
    e.preventDefault();

    const columnsSettingFields = document.querySelector('#columnsSettingsForm')

    const modal = Modal.advanced({
      content: '',
      title: 'Show columns',
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
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
          text: 'Save',
          btnClass: 'btn-primary',
          name: 'save',
          trigger: () => {
            const form = document.querySelector('form')
            form.submit()
            Modal.dismiss()
          }
        }
      ],
      callback: () => {
        modal.querySelector('.modal-body').innerHTML = columnsSettingFields.innerHTML
        const columnsDivs = modal.querySelectorAll('[data-column-name]')

        modal.querySelector('.modal-body').querySelector('input[name="columns-filter"]').addEventListener('input', (e) => {
          columnsDivs.forEach(div => {
            if (div.getAttribute('data-column-name').includes(e.currentTarget.value)) {
              div.style.display = 'block'
            } else {
              div.style.display = 'none'
            }
          })
        })

        modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-all"]').forEach(button => {
          button.addEventListener('click', (e) => {
            e.preventDefault()
            columnsDivs.forEach(div => {
              div.querySelector('input[type="checkbox"]').checked = true
            })
          })
        })

        modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-none"]').forEach(button => {
          button.addEventListener('click', (e) => {
            e.preventDefault()
            columnsDivs.forEach(div => {
              div.querySelector('input[type="checkbox"]').checked = false
            })
          })
        })

        modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-toggle"]').forEach(button => {
          button.addEventListener('click', (e) => {
            e.preventDefault()
            columnsDivs.forEach(div => {
              div.querySelector('input[type="checkbox"]').checked = !div.querySelector('input[type="checkbox"]').checked
            })
          })
        })
      }
    });
  }
}

new RecordlistDocShowColumns();
