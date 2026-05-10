import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import $ from "jquery";
import Utility from "@typo3/backend/utility.js";
import Notification from "@typo3/backend/notification.js";
import DocumentService from "@typo3/core/document-service.js";

export default class RecordlistWorkspaceReadyToPublish {
  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    document.querySelectorAll("[data-workspace-action=\"sendToSpecificStageExecute\"]").forEach(btn => {
      btn.addEventListener("click", this.onSendToSpecificStageClick.bind(this));
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
    const typo3version = parseInt(btn.getAttribute("data-typo3-version"));
    const tr = btn.closest("tr");
    const workspaceId = tr.getAttribute("data-t3ver_wsid");
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
        trigger: async () => {
          modal.hideModal();

          const discardMethod = typo3version === 13 ? "deleteSingleRecord" : "discardSingleRecord";
          const state = tr.getAttribute("data-state");

          // Discard inline children first.
          const inlineReferences = this.getInlineReferences(tr);
          const discardErrors = [];
          for (const ref of inlineReferences) {
            try {
              await new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
                .withQueryArguments({workspaceId: workspaceId})
                .post({
                  action: "Actions",
                  data: [ref.table, ref.versionId],
                  method: discardMethod,
                  tid: 2,
                  type: "rpc"
                }, {
                  headers: { "Content-Type": "application/json; charset=utf-8" }
                });
            } catch (error) {
              console.error("Failed to discard inline reference", ref, error);
              discardErrors.push(ref);
            }
          }

          // Discard the parent only when it has an actual workspace version.
          if (state !== "children-modified") {
            await new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
              .withQueryArguments({workspaceId: workspaceId})
              .post({
                action: "Actions",
                data: [tr.getAttribute("data-table"), tr.getAttribute("data-uid")],
                method: discardMethod,
                tid: 2,
                type: "rpc"
              }, {
                headers: { "Content-Type": "application/json; charset=utf-8" }
              });
          }

          if (discardErrors.length > 0) {
            Notification.error(
              TYPO3.lang["workspace.discard.error.title"],
              TYPO3.lang["workspace.discard.error.message"]
            );
          }

          top?.TYPO3.Backend.ContentContainer.refresh();
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
    const workspaceId = tr.getAttribute("data-t3ver_wsid");
    let t3verOid = parseInt(tr.getAttribute("data-t3ver_oid") ?? "");
    if (!t3verOid) {
      t3verOid = uid;
    }

    const state = tr.getAttribute("data-state");
    const recordsToPublish = state !== "children-modified"
      ? [{ liveId: t3verOid, table: table, versionId: uid }]
      : [];

    const sysFileReferencesToPublish = tr.getAttribute("data-sys-file-references") ?? "";
    if (sysFileReferencesToPublish) {
      const sysFileReferences = JSON.parse(sysFileReferencesToPublish);
      Array.prototype.push.apply(recordsToPublish, sysFileReferences);
    }

    const modal = Modal.advanced({
      title: TYPO3.lang['workspace.readyToPublish.modal.title'],
      size: Modal.sizes.small,
      severity: SeverityEnum.info,
      content: TYPO3.lang['workspace.readyToPublish.modal.content'],
      buttons: [
        {
          text: TYPO3.lang['workspace.readyToPublish.button.cancel'],
          icon: "actions-close",
          btnClass: "btn-default",
          trigger: function() {
            modal.hideModal();
          }
        },
        {
          text: TYPO3.lang['workspace.readyToPublish.button.confirm'],
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

  onSendToSpecificStageClick(e) {
    e.preventDefault();

    const eventTarget = e.currentTarget;
    const tr = eventTarget.closest("tr");
    const workspaceId = tr.getAttribute("data-t3ver_wsid");
    const workspaceStage = parseInt(eventTarget.getAttribute("data-workspace-stage"));
    const stageName = workspaceStage === -10 ? 'readyToPublish' : 'requestChanges';

    if (!tr) {
      return;
    }

    const state = tr.getAttribute("data-state");
    const isChildrenOnly = state === "children-modified";

    const affectedRecord = {
      table: tr.getAttribute("data-table"),
      uid: tr.getAttribute("data-uid"),
      t3ver_oid: tr.getAttribute("data-t3ver_oid")
    };

    const inlineElements = this.getInlineReferences(tr).map(ref => ({
      table: ref.table,
      uid: String(ref.versionId),
      t3ver_oid: String(ref.liveId)
    }));

    // For children-only records the parent has no workspace version; use the first
    // inline child to open the stage window and omit the parent from the execute payload.
    const windowElements = isChildrenOnly ? inlineElements.slice(0, 1) : [affectedRecord];

    const payload = {
      action: "Actions",
      data: [workspaceStage, windowElements, TYPO3.settings.Workspaces.token],
      method: "sendToSpecificStageWindow",
      tid: 1,
      type: "rpc"
    };

    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
      .withQueryArguments({workspaceId: workspaceId})
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
              elements: isChildrenOnly ? inlineElements : [affectedRecord, ...inlineElements],
              nextStage: workspaceStage
            };

            const payload = {
              action: "Actions",
              data: [serializedForm, TYPO3.settings.Workspaces.token],
              method: "sendToSpecificStageExecute",
              tid: 2,
              type: "rpc"
            };

            new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
              .withQueryArguments({workspaceId: workspaceId})
              .post(payload, {
                headers: {
                  "Content-Type": "application/json; charset=utf-8"
                }
              })
              .then(async () => {
                Notification.success(
                  TYPO3.lang[`workspace.${stageName}.notification.success.title`],
                  TYPO3.lang[`workspace.${stageName}.notification.success.description`]
                );
                modal.hideModal();
                top?.TYPO3.Backend.ContentContainer.refresh();
              });

          }
        });
      });
  }

  getInlineReferences(tr) {
    const raw = tr.getAttribute("data-sys-file-references") ?? "";
    if (!raw) {
      return [];
    }
    try {
      return JSON.parse(raw).filter(ref => ref.table && ref.table !== "sys_file_reference");
    } catch {
      return [];
    }
  }

  renderSendToStageWindow(response) {
    const result = response[0].result;
    const $form = $("<form />");

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
