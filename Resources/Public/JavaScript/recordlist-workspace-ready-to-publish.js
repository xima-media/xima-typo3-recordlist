import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import NProgress from 'nprogress';

export default class RecordlistWorkspaceReadyToPublish {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("[data-workspace-action=\"readyToPublish\"]").forEach(btn => {
      btn.addEventListener("click", this.onReadyToPublishClick.bind(this));
    });
  }

  onReadyToPublishClick(e) {
    e.preventDefault();

    const eventTarget = e.currentTarget;
    const tr = eventTarget.closest("tr");

    if (!tr) {
      return;
    }

    const affectedRecord = {
      table: tr.getAttribute("data-table"),
      uid: tr.getAttribute("data-uid"),
      t3ver_oid: tr.getAttribute("data-t3ver_oid")
    };

    const payload = {
      action: "Actions",
      data: ["-10", [affectedRecord], TYPO3.settings.Workspaces.token],
      method: "sendToSpecificStageWindow",
      tid: 1,
      type: "rpc"
    };

    NProgress.configure({ parent: `tr[data-uid="${tr.getAttribute('data-uid')}"]`, showSpinner: true });
    NProgress.start();
    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
      .post(payload, {
        headers: {
          "Content-Type": "application/json; charset=utf-8"
        }
      })
      .then(response => response.resolve())
      .then(response => {
        console.log(response);
      })
  }
}

new RecordlistWorkspaceReadyToPublish();
