import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import DocumentService from "@typo3/core/document-service.js";
import { diffWords, diffLines } from "@xima/recordlist/contrib/diff.js";

const LOCALSTORAGE_KEY = "xima_recordlist_history_hide_system";

// Fields with no editorial value — hidden by default
const SYSTEM_COLUMNS = new Set([
  "tstamp", "crdate", "cruser_id", "editlock",
  "l10n_diffsource", "l10n_state", "l18n_diffsource",
  "t3ver_oid", "t3ver_wsid", "t3ver_state", "t3ver_stage",
  "t3ver_count", "t3ver_move_id", "t3ver_orig_uid", "t3ver_label",
  "sorting",
]);

const STYLES = `
  .xima-history-wrap { padding: 0 .125rem; }

  /* toolbar */
  .xima-history-toolbar {
    display: flex; align-items: center; justify-content: flex-end;
    padding-bottom: .625rem; margin-bottom: .125rem;
    border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6);
  }
  .xima-history-toolbar .form-check-label { font-size: .8125rem; cursor: pointer; }

  /* entries */
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

  /* diff list */
  .xima-history-diff { margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: .5rem; }
  .xima-history-field { display: flex; flex-direction: column; gap: .3rem; }
  .xima-history-field-name { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--typo3-text-color-muted, #6c757d); }

  /* system-field visibility — controlled by wrapper class */
  .xima-history-field[data-system] { }
  .xima-history--hide-system .xima-history-field[data-system] { display: none; }

  /* notice shown when all fields of an entry are system fields */
  .xima-history-only-system { display: none; font-size: .8125rem; font-style: italic; color: var(--typo3-text-color-muted, #6c757d); padding: .25rem 0; }
  .xima-history--hide-system .xima-history-entry--all-system .xima-history-only-system { display: block; }
  .xima-history--hide-system .xima-history-entry--all-system .xima-history-diff { display: none; }

  /* diff blocks */
  .xima-history-unified {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .8rem; line-height: 1.6; border-radius: .3rem; overflow: hidden;
    border: 1px solid var(--typo3-component-border-color, #dee2e6);
  }
  .xima-history-line { display: block; padding: .1rem .5rem; white-space: pre-wrap; word-break: break-all; }
  .xima-history-line-del { background: #fff5f5; color: #c0392b; border-left: 3px solid #e74c3c; }
  .xima-history-line-ins { background: #f0fff4; color: #1e6b42; border-left: 3px solid #27ae60; }
  .xima-history-line-ctx { background: var(--typo3-component-bg, #fff); color: inherit; border-left: 3px solid transparent; }
  .xima-history-inline del { background: #ffdce0; color: inherit; text-decoration: line-through; border-radius: .15rem; padding: 0 .1em; }
  .xima-history-inline ins { background: #cdffd8; color: inherit; text-decoration: none; border-radius: .15rem; padding: 0 .1em; }

  /* badges */
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

const MULTILINE_THRESHOLD = 80;

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

    wrap.appendChild(this.buildToolbar(wrap));

    for (const entry of entries) {
      wrap.appendChild(this.buildEntry(entry));
    }

    if (this.getHideSystemPref()) {
      wrap.classList.add("xima-history--hide-system");
    }

    return wrap;
  }

  buildToolbar(wrap) {
    const toolbar = document.createElement("div");
    toolbar.className = "xima-history-toolbar";

    const label = document.createElement("label");
    label.className = "form-check form-switch mb-0 d-flex align-items-center gap-2 user-select-none";

    const input = document.createElement("input");
    input.type = "checkbox";
    input.className = "form-check-input mt-0";
    input.checked = this.getHideSystemPref();

    const text = document.createElement("span");
    text.className = "form-check-label";
    text.textContent = "Hide system fields";

    label.appendChild(input);
    label.appendChild(text);
    toolbar.appendChild(label);

    input.addEventListener("change", () => {
      this.saveHideSystemPref(input.checked);
      wrap.classList.toggle("xima-history--hide-system", input.checked);
    });

    return toolbar;
  }

  buildEntry(entry) {
    const el = document.createElement("div");
    el.className = "xima-history-entry";
    el.appendChild(this.buildHeader(entry));

    if (entry.fieldChanges && entry.fieldChanges.length > 0) {
      const allSystem = entry.fieldChanges.every(c => SYSTEM_COLUMNS.has(c.field));
      if (allSystem) {
        el.classList.add("xima-history-entry--all-system");
        const notice = document.createElement("p");
        notice.className = "xima-history-only-system";
        notice.textContent = "Only system fields were changed in this revision.";
        el.appendChild(notice);
      }
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
      if (SYSTEM_COLUMNS.has(change.field)) {
        item.setAttribute("data-system", "");
      }

      const label = document.createElement("span");
      label.className = "xima-history-field-name";
      label.textContent = change.field;
      item.appendChild(label);

      const oldVal = String(change.oldValue ?? "");
      const newVal = String(change.newValue ?? "");
      const isMultiline = oldVal.includes("\n") || newVal.includes("\n")
        || oldVal.length > MULTILINE_THRESHOLD || newVal.length > MULTILINE_THRESHOLD;

      item.appendChild(
        isMultiline
          ? this.buildUnifiedDiff(oldVal, newVal)
          : this.buildInlineDiff(oldVal, newVal)
      );

      list.appendChild(item);
    }

    return list;
  }

  buildInlineDiff(oldVal, newVal) {
    const chunks = diffWords(oldVal, newVal);
    const container = document.createElement("div");
    container.className = "xima-history-unified xima-history-inline";

    const line = document.createElement("span");
    line.className = "xima-history-line xima-history-line-ctx";

    for (const chunk of chunks) {
      const node = chunk.added
        ? document.createElement("ins")
        : chunk.removed
          ? document.createElement("del")
          : document.createElement("span");
      node.textContent = chunk.value;
      line.appendChild(node);
    }

    container.appendChild(line);
    return container;
  }

  buildUnifiedDiff(oldVal, newVal) {
    const chunks = diffLines(oldVal, newVal);
    const container = document.createElement("div");
    container.className = "xima-history-unified";

    for (const chunk of chunks) {
      const lines = chunk.value.replace(/\n$/, "").split("\n");
      const prefix = chunk.added ? "+" : chunk.removed ? "−" : " ";
      const cls = chunk.added
        ? "xima-history-line xima-history-line-ins"
        : chunk.removed
          ? "xima-history-line xima-history-line-del"
          : "xima-history-line xima-history-line-ctx";

      for (const text of lines) {
        const line = document.createElement("span");
        line.className = cls;
        line.textContent = prefix + " " + text;
        container.appendChild(line);
      }
    }

    return container;
  }

  getHideSystemPref() {
    try {
      return localStorage.getItem(LOCALSTORAGE_KEY) !== "false";
    } catch {
      return true;
    }
  }

  saveHideSystemPref(value) {
    try {
      localStorage.setItem(LOCALSTORAGE_KEY, String(value));
    } catch {}
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
