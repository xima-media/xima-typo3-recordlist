export default class RecordlistOrderLinks {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("th a[data-order-field]").forEach(link => {
      link.addEventListener("click", this.onOrderLinkClick.bind(this));
    });
  }

  onOrderLinkClick(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const field = link.getAttribute("data-order-field") ?? "";
    const direction = link.getAttribute("data-order-direction") ?? "";
    const fieldInput = document.querySelector("input[name=\"order_field\"]");
    const directionInput = document.querySelector("input[name=\"order_direction\"]");
    fieldInput.value = field;
    directionInput.value = direction;
    fieldInput.closest("form").submit();
  }
}

new RecordlistOrderLinks();
