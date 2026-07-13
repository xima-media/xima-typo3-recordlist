import Modal from "@typo3/backend/modal.js";
import DocumentService from "@typo3/core/document-service.js";

export default class RecordlistDocNewRecord {
  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    const trigger = document.querySelector(".new-record-trigger");
    if (trigger) {
      trigger.addEventListener("click", this.onNewRecordTriggerClick.bind(this));
    }
  }

  onNewRecordTriggerClick(e) {
    e.preventDefault();
    const trigger = e.currentTarget;

    // construct select element from the pages carried on the trigger's data attribute
    const pages = JSON.parse(trigger.dataset.pages ?? "[]");
    const selection = document.createElement("select");
    selection.id = "page-for-new-record";
    selection.classList.add("form-select");
    pages.forEach(page => {
      const option = document.createElement("option");
      option.value = page.href ?? "";
      option.text = page.title ?? "";
      selection.appendChild(option);
    });

    // display modal
    Modal.advanced({
      title: TYPO3.lang.newRecordinPageModalTitle,
      size: Modal.sizes.small,
      content: selection,
      buttons: [
        {
          text: trigger.getAttribute("title"),
          icon: "actions-add",
          btnClass: "btn-primary",
          trigger: function() {
            top.list_frame.location.href = selection.value;
            Modal.dismiss();
          }
        }
      ]
    });
  }
}

new RecordlistDocNewRecord();
