import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";
import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'

class RecordlistActionHiddenToggle {

  saveClick = false

  constructor() {
    this.bindEvents();
  }

  bindEvents() {
    document.querySelectorAll('a[data-hidden-toggle]').forEach(button => {
      button.addEventListener('click', this.onHiddenToggleClick.bind(this));
    })
  }

  onHiddenToggleClick(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const tr = button.closest('tr');

    const hide = button.dataset.hiddenToggle === 'hide'
    const target = hide ? 'unhide' : 'hide';
    const overlay = hide ? 'overlay-hidden' : null;

    const table = tr.dataset.table;
    const uid = tr.dataset.uid;
    const column = button.dataset.hiddenField;
    const newValue = hide ? 1 : 0;

    const payload = new FormData();
    payload.append('table', table);
    payload.append('uid', uid);
    payload.append('column', column);
    payload.append('newValue', newValue);

    new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_inline_edit).post('', {body: payload}).then(() => {
      const currentIcon = tr.querySelector('span.t3js-icon')
      if (currentIcon) {
        Icons.getIcon(currentIcon.dataset.identifier, Icons.sizes.small, overlay, 'default').then(icon => {
          currentIcon.innerHTML = icon
        })
      }

      tr.querySelector(`[data-hidden-toggle="${target}"]`).classList.remove('hidden');
      button.classList.add('hidden');
    }).catch(() => {
      Notification.error(
        TYPO3.lang['hiddenToggle.error.title'],
        TYPO3.lang['hiddenToggle.error.message']
      );
    })

  }
}

new RecordlistActionHiddenToggle();
