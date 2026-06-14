import DocumentService from "@typo3/core/document-service.js";
import Icons from "@typo3/backend/icons.js";

/**
 * Collapses excess row action buttons into a "More" dropdown.
 *
 * The number of action buttons rendered per row is only knowable client-side: the
 * Translate action emits one button per language and each action partial has its own
 * server-side visibility conditions. Once a row's primary action group holds more visible
 * units than the per-user threshold, the overflow is moved into a Bootstrap dropdown to
 * keep the action column compact (see issue #92).
 */
export default class RecordlistActionMore {
  static PROCESSED_ATTR = "data-recordlist-actions-processed";
  static MORE_ICON = "actions-menu-alternative";

  constructor() {
    DocumentService.ready()
      .then(() => {
        this.threshold = this.resolveThreshold();
        // A threshold of 0 (or negative) disables collapsing entirely.
        if (this.threshold <= 0) {
          return null;
        }
        return Icons.getIcon(RecordlistActionMore.MORE_ICON, Icons.sizes.small);
      })
      .then(iconMarkup => {
        if (!iconMarkup) {
          return;
        }
        this.iconMarkup = iconMarkup;
        this.collapseAll();
        this.observeAjaxRows();
      });
  }

  resolveThreshold() {
    const raw = TYPO3?.settings?.XimaTypo3Recordlist?.actionsThreshold;
    const value = parseInt(raw, 10);
    return Number.isNaN(value) ? 0 : value;
  }

  collapseAll() {
    document
      .querySelectorAll(`[data-recordlist-actions]:not([${RecordlistActionMore.PROCESSED_ATTR}])`)
      .forEach(group => this.collapseGroup(group));
  }

  collapseGroup(group) {
    // Guard: never process the same group twice (idempotent across re-runs).
    group.setAttribute(RecordlistActionMore.PROCESSED_ATTR, "1");

    // Each direct element child is one action unit; a nested .btn-group (e.g. Sorting)
    // counts as a single unit, not its inner buttons.
    const units = Array.from(group.children);
    if (units.length <= this.threshold) {
      return;
    }

    const overflow = units.slice(this.threshold);

    const moreGroup = document.createElement("div");
    moreGroup.className = "btn-group dropdown recordlist-action-more";

    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "btn btn-default dropdown-toggle";
    toggle.setAttribute("data-bs-toggle", "dropdown");
    toggle.setAttribute("aria-expanded", "false");
    const label = TYPO3?.lang?.["table.button.more"] ?? "More";
    toggle.title = label;
    toggle.setAttribute("aria-label", label);
    toggle.innerHTML = this.iconMarkup;

    const menu = document.createElement("ul");
    menu.className = "dropdown-menu dropdown-menu-end recordlist-action-more-menu";

    // Move (not clone) the overflow units so their existing listeners and tooltip
    // instances keep working — and no duplicate buttons end up in the DOM.
    overflow.forEach(unit => {
      const item = document.createElement("li");
      item.appendChild(unit);
      this.addLabels(unit);
      menu.appendChild(item);
    });

    moreGroup.appendChild(toggle);
    moreGroup.appendChild(menu);
    group.appendChild(moreGroup);
  }

  /**
   * In the table the action buttons are icon-only (their name lives in `title`). Inside the
   * dropdown there is room for text, so surface that title as a visible label next to the
   * icon. A unit may itself be the action button, or a nested group of buttons (e.g. Sorting).
   */
  addLabels(unit) {
    const targets = unit.matches("a, button") ? [unit] : unit.querySelectorAll("a, button");
    targets.forEach(el => {
      // Bootstrap's tooltip moves `title` to `data-bs-original-title` (and removes `title`)
      // as soon as it initialises — so read that first, then fall back to a not-yet-
      // initialised `title` or an `aria-label`.
      const text = el.getAttribute("data-bs-original-title")
        || el.getAttribute("title")
        || el.getAttribute("aria-label");
      if (!text || el.querySelector(".recordlist-action-more-label")) {
        return;
      }
      const span = document.createElement("span");
      span.className = "recordlist-action-more-label";
      span.textContent = text;
      el.appendChild(span);
    });
  }

  /**
   * The list module can inject fresh rows via AJAX. The idempotency guard only prevents
   * re-processing existing groups, so newly added groups need to be collapsed too.
   */
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
        this.collapseAll();
      });
    });
    observer.observe(target, { childList: true, subtree: true });
  }
}

new RecordlistActionMore();
