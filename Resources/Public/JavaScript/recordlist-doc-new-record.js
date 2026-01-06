import Modal from "@typo3/backend/modal.js";

export default class RecordlistDocNewRecord {
  constructor() {
    this.init();
  }

  init() {
    if (document.querySelectorAll(".new-record-in-page").length > 1) {
      document.querySelectorAll(".new-record-in-page").forEach(btn => {
        btn.addEventListener("click", this.onNewRecordInPageClick.bind(this));
      });
    }
  }

  onNewRecordInPageClick(e) {
    e.preventDefault();
    const btn = e.currentTarget;

    // construct select element
    const selection = document.createElement("select");
    selection.id = "page-for-new-record";
    selection.classList.add("form-select");
    document.querySelectorAll(".new-record-in-page.hidden").forEach(btn => {
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
          text: btn.getAttribute("title"),
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
