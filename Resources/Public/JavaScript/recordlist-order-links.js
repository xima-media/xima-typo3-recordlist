export default class RecordlistOrderLinks {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("th button.dropdown-toggle").forEach(button => {
      button.addEventListener("click", this.onOrderButtonClick.bind(this));
    });

    document.querySelectorAll("th a[data-order-field]").forEach(button => {
      button.addEventListener("click", this.onOrderLinkClick.bind(this));
    });
  }

  onOrderButtonClick(e) {
    e.preventDefault();

    const dropdown = e.currentTarget.nextElementSibling;
    dropdown.classList.add("show");

    // Close the dropdown if clicked outside
    const closeDropdown = (event) => {
      if (!dropdown.contains(event.target) && event.target !== e.currentTarget) {
        dropdown.classList.remove("show");
        document.removeEventListener("mousedown", closeDropdown);
      }
    };
    document.addEventListener("mousedown", closeDropdown);
  }

  onOrderLinkClick(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const field = link.getAttribute("data-order-field") ?? "";
    const direction = link.getAttribute("data-order-direction") ?? "";
    const fieldInput = document.querySelector("input[name=\"order_field\"]");
    const directionInput = document.querySelector("input[name=\"order_direction\"]");

    // Set the hidden inputs and submit the form
    fieldInput.value = field;
    directionInput.value = direction;
    fieldInput.closest("form").submit();

    // Close the dropdown menu
    const dropdown = link.closest(".dropdown-menu");
    if (dropdown) {
      dropdown.classList.remove("show");
    }
  }
}

new RecordlistOrderLinks();
