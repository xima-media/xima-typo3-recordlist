import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import Notification from "@typo3/backend/notification.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";

export default class RecordlistActionDelete {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a[data-delete2]").forEach(link => {
      link.addEventListener("click", this.onDeleteClick.bind(this));
    });
  }

  onDeleteClick(e) {
    const btn = e.currentTarget;
    const table = btn.closest("tr").getAttribute("data-table");
    const uid = btn.closest("tr").getAttribute("data-uid");

    const payload = new FormData();
    payload.append("table", table);
    payload.append("uid", uid);

    const modal = Modal.confirm(
      TYPO3.lang["modal.delete.record.title"],
      TYPO3.lang["modal.delete.record.message"],
      SeverityEnum.warning,
      [
        {
          text: TYPO3.lang["modal.delete.button.cancel"],
          active: true,
          btnClass: "btn-default",
          name: "cancel",
          trigger: () => {
            Modal.dismiss();
          }
        },
        {
          text: TYPO3.lang["modal.delete.button.confirm"],
          btnClass: "btn-warning",
          name: "ok",
          trigger: () => {
            new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_delete).post("", { body: payload }, "")
              .then(async () => {
                top?.TYPO3.Backend.ContentContainer.refresh();
                Notification.success(TYPO3.lang["notification.delete.record.success.title"], TYPO3.lang["notification.delete.record.success.message"], 5);
              })
              .finally(() => {
                Modal.dismiss();
              });
          }
        }
      ]
    );
  }
}

new RecordlistActionDelete();
