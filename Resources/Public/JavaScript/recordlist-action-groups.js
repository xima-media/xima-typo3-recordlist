import DocumentService from "@typo3/core/document-service.js";
import * as bootstrap from "bootstrap";

// Bootstrap exposes Tooltip as a named export on v13 and on the default export on v14;
// resolve from the namespace so a named import breaks neither version.
const Tooltip = bootstrap.Tooltip ?? bootstrap.default?.Tooltip ?? null;

// Moves tagged row actions into server-rendered dropdown shells to keep the action column
// narrow. A shell declares its group via `data-action-group-menu="<name>"`; every bar
// action marked `data-action-group="<name>"` is relocated into the matching menu. Groups
// are discovered from the DOM, so adding one needs no change here — see
// Partials/ActionGroupDropdowns.html.
export default class RecordlistActionGroups {
  static PROCESSED_ATTR = "data-recordlist-actions-processed";

  constructor() {
    DocumentService.ready().then(() => {
      this.groupAll();
      this.observeInsertedBars();
    });
  }

  // A bar marked `.recordlist-actions-ungrouped` opted out of grouping and keeps every
  // action in the bar.
  groupAll() {
    document
      .querySelectorAll(
        `[data-recordlist-actions]:not(.recordlist-actions-ungrouped):not([${RecordlistActionGroups.PROCESSED_ATTR}])`
      )
      .forEach(bar => this.groupBar(bar));
  }

  // Standard navigation (sort, paginate, filter) reloads the list frame and re-runs this
  // module, so the constructor pass covers it. This observer only catches action bars
  // inserted into the DOM after load (the processed-guard makes re-grouping idempotent).
  observeInsertedBars() {
    const target = document.querySelector("main.recordlist") ?? document.body;
    let scheduled = false;
    new MutationObserver(() => {
      if (scheduled) {
        return;
      }
      scheduled = true;
      window.requestAnimationFrame(() => {
        scheduled = false;
        this.groupAll();
      });
    }).observe(target, {childList: true, subtree: true});
  }

  groupBar(bar) {
    bar.setAttribute(RecordlistActionGroups.PROCESSED_ATTR, "1");

    const cell = bar.parentElement;
    if (!cell) {
      return;
    }

    cell.querySelectorAll("[data-action-group-menu]").forEach(shell => {
      const category = shell.getAttribute("data-action-group-menu");
      const menu = shell.querySelector(".recordlist-action-group-menu");
      if (!menu) {
        return;
      }
      Array.from(bar.children)
        .filter(action => action.matches(`[data-action-group="${category}"]`))
        .forEach(action => {
          const entry = document.createElement("li");
          entry.appendChild(action);
          this.addLabel(action);
          this.removeTooltip(action);
          menu.appendChild(entry);
        });
    });
  }

  // Inside the menu the action carries a visible label, so its tooltip is redundant (and
  // would overlay the menu). Dispose any initialised tooltip and strip the attributes.
  removeTooltip(action) {
    const targets = action.matches('[data-bs-toggle="tooltip"]') ? [action] : [];
    action.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => targets.push(el));
    targets.forEach(el => {
      Tooltip?.getInstance(el)?.dispose();
      el.removeAttribute("data-bs-toggle");
      el.removeAttribute("title");
    });
  }

  // Icon-only actions carry their name in `title`; surface it as a visible label. Bootstrap
  // moves `title` to `data-bs-original-title` on tooltip init, so read that first. Actions
  // that already render their own text are left untouched.
  addLabel(action) {
    const targets = action.matches("a, button") ? [action] : action.querySelectorAll("a, button");
    targets.forEach(el => {
      if (el.textContent.trim() || el.querySelector(".recordlist-action-label")) {
        return;
      }
      const text = el.getAttribute("data-bs-original-title")
        || el.getAttribute("title")
        || el.getAttribute("aria-label");
      if (!text) {
        return;
      }
      const span = document.createElement("span");
      span.className = "recordlist-action-label";
      span.textContent = text;
      el.appendChild(span);
    });
  }
}

new RecordlistActionGroups();
