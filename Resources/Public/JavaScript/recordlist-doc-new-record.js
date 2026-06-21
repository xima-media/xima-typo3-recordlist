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

    // construct select element from every available target page (incl. root/first)
    const selection = document.createElement("select");
    selection.id = "page-for-new-record";
    selection.classList.add("form-select");
    document.querySelectorAll(".new-record-in-page").forEach(btn => {
      const option = document.createElement("option");
      option.value = btn.getAttribute("href") ?? "";
      option.text = btn.getAttribute("title") ?? "";
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
