import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'
import UserSettings from "./user-settings.js";
import Sortable from '@xima/recordlist/contrib/sortable.esm.js';

export default class RecordlistDocShowColumns {

  modal = null

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

    this.modal = Modal.advanced({
      content: '',
      title: TYPO3.lang['header.button.showColumns'],
      severity: SeverityEnum.notice,
      size: Modal.sizes.medium,
      type: Modal.types.default,
      buttons: [
        {
          text: TYPO3.lang['showColumns.abort'],
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: () => Modal.dismiss()
        },
        {
          text: TYPO3.lang['showColumns.refresh'],
          btnClass: 'btn-primary',
          name: 'save',
          trigger: this.onModalSave.bind(this)
        }
      ],
      callback: this.modalOpenCallback.bind(this)
    });
  }

  modalOpenCallback() {
    const columnsSettingFields = document.querySelector('#columnsSettingsForm')
    this.modal.querySelector('.modal-body').innerHTML = columnsSettingFields.innerHTML

    const columnsItems = this.modal.querySelectorAll('[data-column-name]')
    const sortableList = this.modal.querySelector('.modal-body').querySelector('#sortable-list')

    Sortable.create(sortableList, {});

    this.modal.querySelector('.modal-body').querySelector('input[name="columns-filter"]').addEventListener('input', (e) => {
      columnsItems.forEach(li => {
        if (li.getAttribute('data-column-name').includes(e.currentTarget.value)) {
          li.classList.remove('d-none')
        } else {
          li.classList.add('d-none')
        }
      })
    })

    this.modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-all"]').forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault()
        columnsItems.forEach(div => {
          div.querySelector('input[type="checkbox"]').checked = true
        })
      })
    })

    this.modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-none"]').forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault()
        columnsItems.forEach(li => {
          li.querySelector('input[type="checkbox"]').checked = false
        })
      })
    })

    this.modal.querySelector('.modal-body').querySelectorAll('button[data-action="select-toggle"]').forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault()
        columnsItems.forEach(li => {
          li.querySelector('input[type="checkbox"]').checked = !li.querySelector('input[type="checkbox"]').checked
        })
      })
    })
  }

  onModalSave() {
    const activeColumns = []

    this.modal.querySelector('.modal-body').querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
      if (checkbox.checked) {
        activeColumns.push(checkbox.value)
      }
    })

    UserSettings.update('activeColumns', activeColumns.join(',')).then(r => {
      window.location.reload()
    })

    Modal.dismiss()
  }
}

new RecordlistDocShowColumns();
