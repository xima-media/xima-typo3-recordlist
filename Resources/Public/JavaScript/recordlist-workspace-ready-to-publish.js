import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import NProgress from "nprogress";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import $ from "jquery";
import Utility from "@typo3/backend/utility.js";
import Notification from "@typo3/backend/notification.js";

export default class RecordlistWorkspaceReadyToPublish {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("[data-workspace-action=\"readyToPublish\"]").forEach(btn => {
      btn.addEventListener("click", this.onReadyToPublishClick.bind(this));
    });

    document.querySelectorAll("[data-workspace-action=\"remove\"]").forEach(btn => {
      btn.addEventListener("click", this.confirmDeleteRecordFromWorkspace.bind(this));
    });

    document.querySelectorAll("[data-workspace-action=\"publish\"]").forEach(btn => {
      btn.addEventListener("click", this.confirmPublishRecordFromWorkspace.bind(this));
    });
  }

  confirmDeleteRecordFromWorkspace(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    const tr = btn.closest("tr");
    const modal = Modal.confirm(TYPO3.lang["window.discard.title"], TYPO3.lang["window.discard.message"], SeverityEnum.warning, [
      {
        text: TYPO3.lang.cancel,
        active: true,
        btnClass: "btn-default",
        name: "cancel",
        trigger: () => {
          modal.hideModal();
        }
      },
      {
        text: TYPO3.lang.ok,
        btnClass: "btn-warning",
        name: "ok",
        trigger: () => {
          const payload = {
            action: "Actions",
            data: [tr.getAttribute("data-table"), tr.getAttribute("data-uid")],
            method: "deleteSingleRecord",
            tid: 2,
            type: "rpc"
          };

          modal.hideModal();

          new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
            .post(payload, {
              headers: {
                "Content-Type": "application/json; charset=utf-8"
              }
            })
            .then(async () => {
              top?.TYPO3.Backend.ContentContainer.refresh();
            });
        }
      }
    ]);
  }

  confirmPublishRecordFromWorkspace(e) {
    e.preventDefault();
    const btn = e.currentTarget;
    const tr = btn.closest("tr");

    if (!tr) {
      return;
    }

    const self = this;
    const uid = parseInt(tr.getAttribute("data-uid") ?? "");
    const table = tr.getAttribute("data-table") ?? "";
    const workspaceId = btn.getAttribute("data-workspace-id") ?? "";
    let t3verOid = parseInt(tr.getAttribute("data-t3ver_oid") ?? "");
    if (!t3verOid) {
      t3verOid = uid;
    }

    const recordsToPublish = [
      {
        liveId: t3verOid,
        table: table,
        versionId: uid
      }
    ];

    const sysFileReferencesToPublish = tr.getAttribute("data-sys-file-references") ?? "";
    if (sysFileReferencesToPublish) {
      const sysFileReferences = JSON.parse(sysFileReferencesToPublish);
      Array.prototype.push.apply(recordsToPublish, sysFileReferences);
    }

    const modal = Modal.advanced({
      title: "Datensatz veröffentlichen",
      size: Modal.sizes.small,
      severity: SeverityEnum.info,
      content: "Möchten Sie den Datensatz wirklich veröffentlichen?",
      buttons: [
        {
          text: "Nein, abbrechen",
          icon: "actions-close",
          btnClass: "btn-default",
          trigger: function() {
            modal.hideModal();
          }
        },
        {
          text: "Ja, veröffentlichen",
          icon: "actions-check",
          btnClass: "btn-success",
          trigger: function() {
            self.publishRecords(recordsToPublish, workspaceId);
            modal.hideModal();
          }
        }
      ]
    });
  }

  publishRecords(records, workspaceId) {
    const payload = {
      action: "Actions",
      data: [
        {
          action: "publish",
          selection: records
        },
        null
      ],
      method: "executeSelectionAction",
      tid: 3,
      type: "rpc"
    };

    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
      .withQueryArguments({workspaceId: workspaceId})
      .post(payload, {
        headers: {
          "Content-Type": "application/json; charset=utf-8"
        }
      })
      .then(() => {
        top?.TYPO3.Backend.ContentContainer.refresh();
      });
  }

  onReadyToPublishClick(e) {
    e.preventDefault();

    const eventTarget = e.currentTarget;
    const tr = eventTarget.closest("tr");
    const workspaceId = tr.getAttribute("data-t3ver_wsid");

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

    NProgress.configure({ parent: `tr[data-uid="${tr.getAttribute("data-uid")}"]`, showSpinner: true });
    NProgress.start();
    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch + '&workspaceId=' + workspaceId)
      .post(payload, {
        headers: {
          "Content-Type": "application/json; charset=utf-8"
        }
      })
      .then(async response => {
        const modal = this.renderSendToStageWindow(await response.resolve());
        modal.addEventListener("button.clicked", (modalEvent) => {
          const target = modalEvent.target;
          if (target.name === "ok") {
            const serializedForm = Utility.convertFormToObject(modal.querySelector("form"));
            serializedForm.affects = {
              elements: [affectedRecord],
              nextStage: "-10"
            };

            const payload = {
              action: "Actions",
              data: [serializedForm, TYPO3.settings.Workspaces.token],
              method: "sendToSpecificStageExecute",
              tid: 2,
              type: "rpc"
            };

            new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch + '&workspaceId=' + workspaceId)
              .post(payload, {
                headers: {
                  "Content-Type": "application/json; charset=utf-8"
                }
              })
              .then(async () => {
                Notification.success("Anfrage erfolgreich", "Die Anfrage zur Freigabe wurde erfolgreich übermittelt");
                modal.hideModal();
                top?.TYPO3.Backend.ContentContainer.refresh();
              });

          }
        });
      });
  }

  renderSendToStageWindow(response) {
    const result = response[0].result;
    const $form = $("<form />");

    if (typeof result.sendMailTo !== "undefined" && result.sendMailTo.length > 0) {

      for (const recipient of result.sendMailTo) {
        $form.append(
          $("<div />", { class: "form-check hidden" }).append(
            $("<input />", {
              type: "checkbox",
              name: "recipients",
              class: "form-check-input t3js-workspace-recipient",
              id: recipient.name,
              value: recipient.value
            }).prop("checked", recipient.checked).prop("disabled", recipient.disabled),
            $("<label />", {
              class: "form-check-label",
              for: recipient.name
            }).text(recipient.label)
          )
        );
      }
    }

    if (typeof result.additional !== "undefined") {
      $form.append(
        $("<div />", { class: "form-group hidden" }).append(
          $("<label />", {
            class: "form-label",
            "for": "additional"
          }).text(TYPO3.lang["window.sendToNextStageWindow.additionalRecipients"]),
          $("<textarea />", {
            class: "form-control",
            name: "additional",
            id: "additional"
          }).text(result.additional.value),
          $("<div />", { class: "form-text" }).text(TYPO3.lang["window.sendToNextStageWindow.additionalRecipients.hint"])
        )
      );
    }

    $form.append(
      $("<div />", { class: "form-group" }).append(
        $("<label />", {
          class: "form-label",
          "for": "comments"
        }).text(TYPO3.lang["window.sendToNextStageWindow.comments"]),
        $("<textarea />", {
          class: "form-control",
          name: "comments",
          id: "comments"
        }).text(result.comments.value)
      )
    );

    const modal = Modal.show(
      TYPO3.lang.actionSendToStage,
      $form,
      SeverityEnum.info,
      [
        {
          text: TYPO3.lang.cancel,
          active: true,
          btnClass: "btn-default",
          name: "cancel",
          trigger: () => {
            modal.hideModal();
          }
        },
        {
          text: TYPO3.lang.ok,
          btnClass: "btn-primary",
          name: "ok"
        }
      ]
    );

    return modal;
  }
}

new RecordlistWorkspaceReadyToPublish();
