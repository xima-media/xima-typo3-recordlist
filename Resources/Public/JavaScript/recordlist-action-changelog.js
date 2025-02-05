import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import $ from "jquery";

export default class RecordlistActionChangelog {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a[data-action=\"changes\"]").forEach(link => {
      link.addEventListener("click", this.onChangelogClick.bind(this));
    });
  }

  onChangelogClick(e) {
    e.preventDefault();

    const $tr = $(e.currentTarget).closest("tr");

    const payload = {
      action: "RemoteServer",
      data: [{
        stage: $tr.data("stage"),
        t3ver_oid: $tr.data("t3ver_oid"),
        table: $tr.data("table"),
        uid: $tr.data("uid"),
        filterFields: true
      }, null],
      method: "getRowDetails",
      tid: 1,
      type: "rpc"
    };

    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch).post(
      payload,
      {
        headers: {
          "Content-Type": "application/json; charset=utf-8"
        }
      }
    ).then(async (response) => {
      const item = (await response.resolve())[0].result.data[0];
      const $content = $("<div />");
      const $tabsNav = $("<ul />", { class: "nav nav-tabs", role: "tablist" });
      const $tabsContent = $("<div />", { class: "tab-content" });
      const modalButtons = [];

      $content.append(
        $("<p />").html(TYPO3.lang.path.replace("{0}", item.path_Live)),
        $("<p />").html(
          TYPO3.lang.current_step.replace("{0}", item.label_Stage)
            .replace("{1}", item.stage_position)
            .replace("{2}", item.stage_count)
        )
      );

      if (item.diff.length > 0) {
        $tabsNav.append(
          $("<li />", { role: "presentation", class: "nav-item" }).append(
            $("<a />", {
              class: "nav-link",
              href: "#workspace-changes",
              "aria-controls": "workspace-changes",
              role: "tab",
              "data-bs-toggle": "tab"
            }).text(TYPO3.lang["window.recordChanges.tabs.changeSummary"])
          )
        );
        $tabsContent.append(
          $("<div />", { role: "tabpanel", class: "tab-pane", id: "workspace-changes" }).append(
            $("<div />", { class: "form-section" }).append(
              this.generateDiffView(item.diff)
            )
          )
        );
      }

      if (item.comments.length > 0) {
        $tabsNav.append(
          $("<li />", { role: "presentation", class: "nav-item" }).append(
            $("<a />", {
              class: "nav-link",
              href: "#workspace-comments",
              "aria-controls": "workspace-comments",
              role: "tab",
              "data-bs-toggle": "tab"
            }).html(TYPO3.lang["window.recordChanges.tabs.comments"] + "&nbsp;").append(
              $("<span />", { class: "badge" }).text(item.comments.length)
            )
          )
        );
        $tabsContent.append(
          $("<div />", { role: "tabpanel", class: "tab-pane", id: "workspace-comments" }).append(
            $("<div />", { class: "form-section" }).append(
              this.generateCommentView(item.comments)
            )
          )
        );
      }

      if (item.history.total > 0) {
        $tabsNav.append(
          $("<li />", { role: "presentation", class: "nav-item" }).append(
            $("<a />", {
              class: "nav-link",
              href: "#workspace-history",
              "aria-controls": "workspace-history",
              role: "tab",
              "data-bs-toggle": "tab"
            }).text(TYPO3.lang["window.recordChanges.tabs.history"])
          )
        );

        $tabsContent.append(
          $("<div />", { role: "tabpanel", class: "tab-pane", id: "workspace-history" }).append(
            $("<div />", { class: "form-section" }).append(
              this.generateHistoryView(item.history.data)
            )
          )
        );
      }

      // Mark the first tab and pane as active
      $tabsNav.find("li > a").first().addClass("active");
      $tabsContent.find(".tab-pane").first().addClass("active");

      // Attach tabs
      $content.append(
        $("<div />").append(
          $tabsNav,
          $tabsContent
        )
      );

      if (item.label_PrevStage !== false && $tr.data("stage") !== $tr.data("prevStage")) {
        modalButtons.push({
          text: item.label_PrevStage.title,
          active: true,
          btnClass: "btn-default",
          name: "prevstage",
          trigger: (e, modal) => {
            modal.hideModal();
            this.sendToStage($tr, "prev");
          }
        });
      }

      if (item.label_NextStage !== false) {
        modalButtons.push({
          text: item.label_NextStage.title,
          active: true,
          btnClass: "btn-default",
          name: "nextstage",
          trigger: (e, modal) => {
            modal.hideModal();
            this.sendToStage($tr, "next");
          }
        });
      }
      modalButtons.push({
        text: TYPO3.lang.close,
        active: true,
        btnClass: "btn-info",
        name: "cancel",
        trigger: (e, modal) => modal.hideModal()
      });

      Modal.advanced({
        type: Modal.types.default,
        title: TYPO3.lang["window.recordInformation"].replace("{0}", $tr.find(".t3js-title-live").text().trim()),
        content: $content,
        severity: SeverityEnum.info,
        buttons: modalButtons,
        size: Modal.sizes.medium
      });
    });
  };

  generateDiffView(diff) {
    const $diff = $("<div />", { class: "diff" });

    for (const currentDiff of diff) {
      $diff.append(
        $("<div />", { class: "diff-item" }).append(
          $("<div />", { class: "diff-item-title" }).text(currentDiff.label),
          $("<div />", { class: "diff-item-result" }).html(currentDiff.content)
        )
      );
    }
    return $diff;
  }

  generateCommentView(comments) {
    const $comments = $("<div />");

    for (const comment of comments) {
      const $panel = $("<div />", { class: "panel panel-default" });

      if (comment.user_comment.length > 0) {
        $panel.append(
          $("<div />", { class: "panel-body" }).html(comment.user_comment)
        );
      }

      $panel.append(
        $("<div />", { class: "panel-footer" }).append(
          $("<span />", { class: "badge badge-success me-2" }).text(comment.previous_stage_title + " > " + comment.stage_title),
          $("<span />", { class: "badge badge-info" }).text(comment.tstamp)
        )
      );

      $comments.append(
        $("<div />", { class: "media" }).append(
          $("<div />", { class: "media-left text-center" }).text(comment.user_username).prepend(
            $("<div />").html(comment.user_avatar)
          ),
          $("<div />", { class: "media-body" }).append($panel)
        )
      );
    }

    return $comments;
  }

  generateHistoryView(data) {
    const $history = $("<div />");

    for (const currentData of data) {
      const $panel = $("<div />", { class: "panel panel-default" });
      let $diff;

      if (typeof currentData.differences === "object") {
        if (currentData.differences.length === 0) {
          // Somehow here are no differences. What a pity, skip that record
          continue;
        }
        $diff = $("<div />", { class: "diff" });

        for (let j = 0; j < currentData.differences.length; ++j) {
          $diff.append(
            $("<div />", { class: "diff-item" }).append(
              $("<div />", { class: "diff-item-title" }).text(currentData.differences[j].label),
              $("<div />", { class: "diff-item-result" }).html(currentData.differences[j].html)
            )
          );
        }

        $panel.append(
          $("<div />").append($diff)
        );
      } else {
        $panel.append(
          $("<div />", { class: "panel-body" }).text(currentData.differences)
        );
      }
      $panel.append(
        $("<div />", { class: "panel-footer" }).append(
          $("<span />", { class: "badge badge-info" }).text(currentData.datetime)
        )
      );

      $history.append(
        $("<div />", { class: "media" }).append(
          $("<div />", { class: "media-left text-center" }).text(currentData.user).prepend(
            $("<div />").html(currentData.user_avatar)
          ),
          $("<div />", { class: "media-body" }).append($panel)
        )
      );
    }

    return $history;
  }
}

new RecordlistActionChangelog();
