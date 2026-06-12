import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from '@typo3/backend/notification.js'
import DocumentService from '@typo3/core/document-service.js'

class RecordlistActionSorting {

  constructor() {
    DocumentService.ready().then(() => {
      this.bindEvents();
    });
  }

  bindEvents() {
    document.querySelectorAll('a[data-sorting-move]').forEach(button => {
      button.addEventListener('click', this.onSortingMoveClick.bind(this));
    });
  }

  onSortingMoveClick(e) {
    e.preventDefault();
    const button = e.currentTarget;
    const tr = button.closest('tr');

    const payload = new FormData();
    payload.append('table', tr.dataset.table);
    payload.append('uid', tr.dataset.uid);
    payload.append('direction', button.dataset.sortingMove);

    new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_move).post('', {body: payload}).then(async response => {
      // 204 = record already at the top/bottom boundary, nothing changed
      if (response.response.status === 204) {
        return;
      }
      // re-render the list so the new order is reflected
      window.location.reload();
    }).catch(() => {
      Notification.error(
        TYPO3.lang['sorting.error.title'],
        TYPO3.lang['sorting.error.message']
      );
    });
  }
}

new RecordlistActionSorting();
