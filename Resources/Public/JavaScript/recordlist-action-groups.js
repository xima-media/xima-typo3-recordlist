import DocumentService from "@typo3/core/document-service.js";
import * as bootstrap from "bootstrap";

// TYPO3 v13 exposes Bootstrap's classes as named exports (`import { Tooltip }`), while v14
// exposes them on the default export. A named import throws on v14 ("no export named
// Tooltip"), so resolve the class from the namespace at runtime to support both.
const Tooltip = bootstrap.Tooltip ?? bootstrap.default?.Tooltip ?? null;

// Moves translation / workspace row actions into their dropdowns to keep the action column
// narrow (see issue #92).
//
// The dropdown shells — toggle, icon and empty menu — are rendered server-side
// (Partials/ActionGroupDropdowns.html) so they appear at their final size on first paint:
// no icon flicker, no layout shift. This module only relocates the tagged action buttons
// into the matching menu. The actions vary per row (translations emit one button per
// language, workspace actions are stage-/permission-dependent), so the move is client-side;
// recordlist.css hides a shell when the row carries no action of that category.
export default class RecordlistActionGroups {
  static PROCESSED_ATTR = "data-recordlist-actions-processed";
  static CATEGORIES = ["translation", "workspace"];

  constructor() {
    DocumentService.ready().then(() => {
      this.groupAll();
      this.observeAjaxRows();
    });
  }

  groupAll() {
    document
      .querySelectorAll(`[data-recordlist-actions]:not([${RecordlistActionGroups.PROCESSED_ATTR}])`)
      .forEach(container => this.groupContainer(container));
  }

  groupContainer(container) {
    // Guard: never process the same container twice (idempotent across re-runs).
    container.setAttribute(RecordlistActionGroups.PROCESSED_ATTR, "1");

    const cell = container.parentElement;
    if (!cell) {
      return;
    }

    RecordlistActionGroups.CATEGORIES.forEach(category => {
      const menu = cell.querySelector(`.recordlist-action-${category} .recordlist-action-group-menu`);
      if (!menu) {
        return;
      }
      // Each direct element child is one action unit; a nested .btn-group counts as one.
      Array.from(container.children)
        .filter(child => child.matches(`[data-action-group="${category}"]`))
        .forEach(item => {
          const entry = document.createElement("li");
          entry.appendChild(item);
          this.addLabels(item);
          this.removeTooltips(item);
          menu.appendChild(entry);
        });
    });
  }

  // The actions now carry a visible text label inside the dropdown, so their tooltips are
  // redundant (and would overlay the menu). Dispose any initialised tooltip and strip the
  // attributes so neither Bootstrap nor a native `title` tooltip is shown.
  removeTooltips(unit) {
    const targets = unit.matches('[data-bs-toggle="tooltip"]') ? [unit] : [];
    unit.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => targets.push(el));
    targets.forEach(el => {
      Tooltip?.getInstance(el)?.dispose();
      el.removeAttribute("data-bs-toggle");
      el.removeAttribute("title");
    });
  }

  // Icon-only actions carry their name in `title`. Inside the dropdown there is room for
  // text, so surface that as a visible label. Bootstrap's tooltip moves `title` to
  // `data-bs-original-title` (and removes `title`) on init, so read that first. Actions
  // that already render their own text label (e.g. Publish) are left untouched.
  addLabels(unit) {
    const targets = unit.matches("a, button") ? [unit] : unit.querySelectorAll("a, button");
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

  // The list module can inject fresh rows via AJAX. The idempotency guard only prevents
  // re-processing existing containers, so newly added ones need to be grouped too.
  observeAjaxRows() {
    const target = document.querySelector("main.recordlist") ?? document.body;
    let scheduled = false;
    const observer = new MutationObserver(() => {
      if (scheduled) {
        return;
      }
      scheduled = true;
      window.requestAnimationFrame(() => {
        scheduled = false;
        this.groupAll();
      });
    });
    observer.observe(target, { childList: true, subtree: true });
  }
}

new RecordlistActionGroups();
