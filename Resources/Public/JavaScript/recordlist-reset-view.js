import DocumentService from "@typo3/core/document-service.js";

export default class RecordlistResetView {
  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    document.querySelectorAll('*[data-doc-button="resetViewButton"]').forEach(button => {
      button.addEventListener("click", this.onResetViewClick.bind(this));
    });
  }

  onResetViewClick(e) {
    e.preventDefault();
    const form = document.querySelector('#recordlist-search-form');
    if (!form) return;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reset_view';
    input.value = '1';
    form.appendChild(input);
    form.submit();
  }
}

new RecordlistResetView();
