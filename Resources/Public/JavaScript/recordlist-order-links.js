import DocumentService from "@typo3/core/document-service.js";

export default class RecordlistOrderLinks {
  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    document.querySelectorAll("th a[data-order-field]").forEach(button => {
      button.addEventListener("click", this.onOrderLinkClick.bind(this));
    });
  }

  onOrderLinkClick(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const field = link.getAttribute("data-order-field") ?? "";
    const direction = link.getAttribute("data-order-direction") ?? "";
    const fieldInput = document.querySelector("input[name=\"order_field\"]");
    const directionInput = document.querySelector("input[name=\"order_direction\"]");

    // Clicking the currently active ordering again unsets it and restores the default ordering;
    // otherwise apply the clicked field and direction.
    const isActive = fieldInput.value === field && directionInput.value === direction;
    fieldInput.value = isActive ? "" : field;
    directionInput.value = isActive ? "" : direction;
    fieldInput.closest("form").submit();

    // Close the dropdown menu
    const dropdown = link.closest(".dropdown-menu");
    if (dropdown) {
      dropdown.classList.remove("show");
    }
  }
}

new RecordlistOrderLinks();
