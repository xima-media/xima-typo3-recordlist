/*
* This file is part of the TYPO3 CMS project.
*
* It is free software; you can redistribute it and/or modify it under
* the terms of the GNU General Public License, either version 2
* of the License, or any later version.
*
* For the full copyright and license information, please read the
* LICENSE.txt file that was distributed with this source code.
*
* The TYPO3 project - inspiring people to share!
*
* Self-contained tooltip implementation compatible with the @typo3/core/tooltip.js API.
* Replaces the Bootstrap Tooltip dependency for TYPO3 v14+ compatibility.
*/
import DocumentService from "@typo3/core/document-service.js";

const STYLE_ID = "xima-tooltip-styles";

function injectStyles() {
  if (document.getElementById(STYLE_ID)) return;
  const style = document.createElement("style");
  style.id = STYLE_ID;
  style.textContent = `
.xima-tooltip {
  position: absolute;
  z-index: 1080;
  display: block;
  max-width: 200px;
  padding: .25rem .5rem;
  font-size: .75rem;
  line-height: 1.5;
  color: #fff;
  background-color: rgba(0,0,0,.85);
  border-radius: .25rem;
  pointer-events: none;
  opacity: 0;
  transition: opacity .15s linear;
  word-wrap: break-word;
}
.xima-tooltip.show { opacity: .9; }
`;
  document.head.appendChild(style);
}

function resolveContainer(container) {
  if (!container || container === "body") return document.body;
  if (typeof container === "string") return document.querySelector(container) || document.body;
  return container;
}

function getTitle(element) {
  return (
    element.getAttribute("data-bs-original-title") ||
    element.getAttribute("data-bs-title") ||
    element.getAttribute("title") ||
    ""
  );
}

function resolvePlacement(element, preferred) {
  if (preferred && preferred !== "auto") return preferred;
  const rect = element.getBoundingClientRect();
  return rect.top >= window.innerHeight - rect.bottom ? "top" : "bottom";
}

function positionTooltip(tooltipEl, anchorEl, placement) {
  const anchor = anchorEl.getBoundingClientRect();
  const tip = tooltipEl.getBoundingClientRect();
  const scrollX = window.scrollX || window.pageXOffset;
  const scrollY = window.scrollY || window.pageYOffset;
  const gap = 6;

  let top, left;
  switch (placement) {
    case "bottom":
      top = scrollY + anchor.bottom + gap;
      left = scrollX + anchor.left + (anchor.width - tip.width) / 2;
      break;
    case "left":
    case "start":
      top = scrollY + anchor.top + (anchor.height - tip.height) / 2;
      left = scrollX + anchor.left - tip.width - gap;
      break;
    case "right":
    case "end":
      top = scrollY + anchor.top + (anchor.height - tip.height) / 2;
      left = scrollX + anchor.right + gap;
      break;
    case "top":
    default:
      top = scrollY + anchor.top - tip.height - gap;
      left = scrollX + anchor.left + (anchor.width - tip.width) / 2;
  }

  left = Math.max(scrollX + 4, Math.min(left, scrollX + window.innerWidth - tip.width - 4));
  top = Math.max(scrollY + 4, Math.min(top, scrollY + window.innerHeight - tip.height - 4));

  tooltipEl.style.top = top + "px";
  tooltipEl.style.left = left + "px";
}

class TooltipInstance {
  static _instances = new WeakMap();

  constructor(element, options = {}) {
    this._element = element;
    this._options = Object.assign(
      { container: "body", trigger: "hover", delay: { show: 500, hide: 100 } },
      options
    );
    this._tooltipEl = null;
    this._showTimer = null;
    this._hideTimer = null;
    this._boundOnShow = this._onShow.bind(this);
    this._boundOnHide = this._onHide.bind(this);
    this._setup();
    TooltipInstance._instances.set(element, this);
    injectStyles();
  }

  static getInstance(element) {
    return TooltipInstance._instances.get(element) || null;
  }

  static getOrCreateInstance(element, options = {}) {
    return TooltipInstance.getInstance(element) || new TooltipInstance(element, options);
  }

  _setup() {
    const triggers = (this._options.trigger || "hover").split(" ");
    if (triggers.includes("hover")) {
      this._element.addEventListener("mouseenter", this._boundOnShow);
      this._element.addEventListener("mouseleave", this._boundOnHide);
    }
    if (triggers.includes("focus")) {
      this._element.addEventListener("focus", this._boundOnShow);
      this._element.addEventListener("blur", this._boundOnHide);
    }
    const title = this._element.getAttribute("title");
    if (title) {
      this._element.setAttribute("data-bs-original-title", title);
      this._element.removeAttribute("title");
    }
  }

  _onShow() {
    clearTimeout(this._hideTimer);
    const delay = this._options.delay?.show ?? 500;
    this._showTimer = setTimeout(() => this.show(), delay);
  }

  _onHide() {
    clearTimeout(this._showTimer);
    const delay = this._options.delay?.hide ?? 100;
    this._hideTimer = setTimeout(() => this.hide(), delay);
  }

  show() {
    const title = getTitle(this._element);
    if (!title) return;
    if (!this._tooltipEl) {
      this._tooltipEl = document.createElement("div");
      this._tooltipEl.className = "xima-tooltip";
      this._tooltipEl.setAttribute("role", "tooltip");
      resolveContainer(this._options.container).appendChild(this._tooltipEl);
    }
    this._tooltipEl.textContent = title;
    const placement = resolvePlacement(
      this._element,
      this._element.getAttribute("data-bs-placement")
    );
    positionTooltip(this._tooltipEl, this._element, placement);
    requestAnimationFrame(() => {
      if (this._tooltipEl) this._tooltipEl.classList.add("show");
    });
  }

  hide() {
    if (this._tooltipEl) {
      this._tooltipEl.classList.remove("show");
    }
  }

  dispose() {
    clearTimeout(this._showTimer);
    clearTimeout(this._hideTimer);
    this._element.removeEventListener("mouseenter", this._boundOnShow);
    this._element.removeEventListener("mouseleave", this._boundOnHide);
    this._element.removeEventListener("focus", this._boundOnShow);
    this._element.removeEventListener("blur", this._boundOnHide);
    if (this._tooltipEl) {
      this._tooltipEl.remove();
      this._tooltipEl = null;
    }
    const original = this._element.getAttribute("data-bs-original-title");
    if (original) {
      this._element.setAttribute("title", original);
      this._element.removeAttribute("data-bs-original-title");
    }
    TooltipInstance._instances.delete(this._element);
  }
}

class Tooltip {
  constructor() {
    DocumentService.ready().then(() => {
      this.initialize('[data-bs-toggle="tooltip"]');
    });
  }

  static applyAttributes(t, o) {
    for (const [e, i] of Object.entries(t)) o.setAttribute(e, i);
  }

  initialize(t, o = {}) {
    if (Object.entries(o).length === 0) {
      o = { container: "body", trigger: "hover", delay: { show: 500, hide: 100 } };
    }
    const elements = document.querySelectorAll(t);
    for (const el of elements) TooltipInstance.getOrCreateInstance(el, o);
  }

  show(t, o) {
    const e = { "data-bs-placement": "auto", title: o };
    if (t instanceof NodeList) {
      for (const el of t) {
        Tooltip.applyAttributes(e, el);
        TooltipInstance.getInstance(el)?.show();
      }
    } else if (t instanceof HTMLElement) {
      Tooltip.applyAttributes(e, t);
      TooltipInstance.getInstance(t)?.show();
    }
  }

  hide(t) {
    if (t instanceof NodeList) {
      for (const o of t) {
        const inst = TooltipInstance.getInstance(o);
        if (inst !== null) inst.hide();
      }
    } else if (t instanceof HTMLElement) {
      TooltipInstance.getInstance(t)?.hide();
    }
  }
}

const tooltipObject = new Tooltip;
TYPO3.Tooltip = tooltipObject;
export default tooltipObject;
