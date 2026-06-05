import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Modal from "@typo3/backend/modal.js";
import { SeverityEnum } from "@typo3/backend/enum/severity.js";
import DocumentService from "@typo3/core/document-service.js";

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
              trigger: (e, modal) => modal.hideModal()
            }
          ]
        });
      });
  }

  buildContent(entries) {
    const wrapper = document.createElement("div");

    if (!entries || entries.length === 0) {
      const empty = document.createElement("p");
      empty.className = "text-muted";
      empty.textContent = TYPO3.lang["modal.history.noEntries"];
      wrapper.appendChild(empty);
      return wrapper;
    }

    const table = document.createElement("table");
    table.className = "table table-striped table-hover table-sm";

    const thead = document.createElement("thead");
    thead.innerHTML = "<tr><th>Date</th><th>User</th><th>Action</th><th>Field</th><th>Old value</th><th>New value</th></tr>";
    table.appendChild(thead);

    const tbody = document.createElement("tbody");

    for (const entry of entries) {
      const date = new Date(entry.tstamp * 1000).toLocaleString();
      const action = this.getActionLabel(entry.actiontype);

      if (entry.fieldChanges && entry.fieldChanges.length > 0) {
        entry.fieldChanges.forEach((change, index) => {
          const tr = document.createElement("tr");
          tr.innerHTML = `
            <td class="text-nowrap">${index === 0 ? date : ""}</td>
            <td>${index === 0 ? this.escapeHtml(entry.user) : ""}</td>
            <td>${index === 0 ? action : ""}</td>
            <td><code>${this.escapeHtml(change.field)}</code></td>
            <td class="text-muted">${this.escapeHtml(String(change.oldValue ?? ""))}</td>
            <td>${this.escapeHtml(String(change.newValue ?? ""))}</td>
          `;
          tbody.appendChild(tr);
        });
      } else {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td class="text-nowrap">${date}</td>
          <td>${this.escapeHtml(entry.user)}</td>
          <td>${action}</td>
          <td colspan="3"></td>
        `;
        tbody.appendChild(tr);
      }
    }

    table.appendChild(tbody);
    wrapper.appendChild(table);
    return wrapper;
  }

  getActionLabel(actiontype) {
    const labels = {
      1: "Created",
      2: "Modified",
      3: "Moved",
      4: "Deleted",
      5: "Restored",
      6: "Stage changed",
      7: "Published"
    };
    return labels[actiontype] ?? String(actiontype);
  }

  escapeHtml(str) {
    return str
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }
}

new RecordlistActionHistory();
