import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import DocumentService from "@typo3/core/document-service.js";

const STYLES = `
  .xima-history-wrap { overflow-y: auto; max-height: 60vh; padding: 0 .25rem; }
  .xima-history-entry { padding: .875rem 0; border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6); }
  .xima-history-entry:last-child { border-bottom: none; padding-bottom: 0; }
  .xima-history-header { display: flex; align-items: center; gap: .5rem; margin-bottom: .625rem; flex-wrap: wrap; }
  .xima-history-user { display: inline-flex; align-items: center; gap: .375rem; font-weight: 600; font-size: .9375rem; }
  .xima-history-avatar {
    display: inline-flex; align-items: center; justify-content: center;
    width: 1.625rem; height: 1.625rem; border-radius: 50%;
    background: var(--typo3-state-info-background, #cff4fc);
    color: var(--typo3-state-info-color, #055160);
    font-size: .6875rem; font-weight: 700; flex-shrink: 0; line-height: 1;
    border: 1px solid rgba(0,0,0,.06);
  }
  .xima-history-date { margin-left: auto; font-size: .8125rem; color: var(--typo3-text-color-muted, #6c757d); white-space: nowrap; }
  .xima-history-diff { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: .5rem; }
  .xima-history-field { display: flex; flex-direction: column; gap: .2rem; }
  .xima-history-field-name { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--typo3-text-color-muted, #6c757d); margin-bottom: .1rem; }
  .xima-history-del,
  .xima-history-ins { display: block; padding: .2rem .5rem; border-radius: .25rem; font-size: .875rem; word-break: break-word; line-height: 1.4; }
  .xima-history-del { background: #ffeef0; color: #a8071a; text-decoration: line-through; border-left: 3px solid #f5222d; }
  .xima-history-ins { background: #f6ffed; color: #135200; border-left: 3px solid #52c41a; }
  .xima-history-del:empty, .xima-history-ins:empty { display: none; }
  .xima-history-badge { font-size: .7rem; font-weight: 600; padding: .2em .55em; border-radius: .3rem; letter-spacing: .03em; }
  .xima-history-badge-modify  { background: #e8f4fd; color: #0c63e4; }
  .xima-history-badge-create  { background: #d1f7c4; color: #1a5c07; }
  .xima-history-badge-delete  { background: #ffe4e6; color: #9f1239; }
  .xima-history-badge-restore { background: #fff7e6; color: #92400e; }
  .xima-history-badge-move    { background: #f3f0ff; color: #5b21b6; }
  .xima-history-badge-publish { background: #e0f2fe; color: #075985; }
  .xima-history-badge-default { background: #f3f4f6; color: #374151; }
  .xima-history-empty { color: var(--typo3-text-color-muted, #6c757d); font-style: italic; padding: 1rem 0; }
`;

export default class RecordlistActionHistory {
  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    document.querySelectorAll("a[data-action=\"history\"]").forEach(link => {
      link.addEventListener("click", this.onHistoryClick.bind(this));
    });
  }

  onHistoryClick(e) {
    e.preventDefault();

    const tr = e.currentTarget.closest("tr");
    const table = tr.getAttribute("data-table");
    const uid = tr.getAttribute("data-uid");

    const payload = new FormData();
    payload.append("table", table);
    payload.append("uid", uid);

    new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_history)
      .post("", { body: payload })
      .then(async (response) => {
        const entries = await response.resolve();
        const content = this.buildContent(entries);

        Modal.advanced({
          type: Modal.types.default,
          title: TYPO3.lang["modal.history.title"],
          content: content,
          severity: SeverityEnum.info,
          size: Modal.sizes.large,
          buttons: [
            {
              text: TYPO3.lang["modal.history.close"],
              active: true,
              btnClass: "btn-default",
              name: "close",
              trigger: (evt, modal) => modal.hideModal()
            }
          ]
        });
      });
  }

  buildContent(entries) {
    const wrap = document.createElement("div");
    wrap.className = "xima-history-wrap";

    const style = document.createElement("style");
    style.textContent = STYLES;
    wrap.appendChild(style);

    if (!entries || entries.length === 0) {
      const empty = document.createElement("p");
      empty.className = "xima-history-empty";
      empty.textContent = TYPO3.lang["modal.history.noEntries"];
      wrap.appendChild(empty);
      return wrap;
    }

    for (const entry of entries) {
      wrap.appendChild(this.buildEntry(entry));
    }

    return wrap;
  }

  buildEntry(entry) {
    const el = document.createElement("div");
    el.className = "xima-history-entry";
    el.appendChild(this.buildHeader(entry));

    if (entry.fieldChanges && entry.fieldChanges.length > 0) {
      el.appendChild(this.buildDiff(entry.fieldChanges));
    }

    return el;
  }

  buildHeader(entry) {
    const header = document.createElement("div");
    header.className = "xima-history-header";

    const user = document.createElement("span");
    user.className = "xima-history-user";

    const avatar = document.createElement("span");
    avatar.className = "xima-history-avatar";
    avatar.textContent = (entry.user || "?").charAt(0).toUpperCase();

    const username = document.createElement("span");
    username.textContent = entry.user || "System";

    user.appendChild(avatar);
    user.appendChild(username);
    header.appendChild(user);

    const badge = document.createElement("span");
    badge.className = "xima-history-badge " + this.getActionBadgeClass(entry.actiontype);
    badge.textContent = this.getActionLabel(entry.actiontype);
    header.appendChild(badge);

    const date = document.createElement("span");
    date.className = "xima-history-date";
    date.textContent = new Date(entry.tstamp * 1000).toLocaleString(undefined, {
      day: "2-digit", month: "2-digit", year: "numeric",
      hour: "2-digit", minute: "2-digit"
    });
    header.appendChild(date);

    return header;
  }

  buildDiff(fieldChanges) {
    const list = document.createElement("ul");
    list.className = "xima-history-diff";

    for (const change of fieldChanges) {
      const item = document.createElement("li");
      item.className = "xima-history-field";

      const label = document.createElement("span");
      label.className = "xima-history-field-name";
      label.textContent = change.field;
      item.appendChild(label);

      const oldVal = String(change.oldValue ?? "");
      const newVal = String(change.newValue ?? "");

      if (oldVal !== "") {
        const del = document.createElement("span");
        del.className = "xima-history-del";
        del.textContent = oldVal;
        item.appendChild(del);
      }

      if (newVal !== "") {
        const ins = document.createElement("span");
        ins.className = "xima-history-ins";
        ins.textContent = newVal;
        item.appendChild(ins);
      }

      list.appendChild(item);
    }

    return list;
  }

  getActionLabel(actiontype) {
    const labels = {
      1: "Created", 2: "Modified", 3: "Moved",
      4: "Deleted", 5: "Restored", 7: "Published"
    };
    return labels[actiontype] ?? "Changed";
  }

  getActionBadgeClass(actiontype) {
    const classes = {
      1: "xima-history-badge-create",
      2: "xima-history-badge-modify",
      3: "xima-history-badge-move",
      4: "xima-history-badge-delete",
      5: "xima-history-badge-restore",
      7: "xima-history-badge-publish"
    };
    return classes[actiontype] ?? "xima-history-badge-default";
  }
}

new RecordlistActionHistory();
