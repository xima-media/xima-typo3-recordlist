export default class RecordlistPagination {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a[data-nextpage]").forEach(link => {
      link.addEventListener("click", this.onPaginationLinkClick.bind(this));
    });

    document.querySelectorAll("input[name=\"current_page\"]").forEach(input => {
      input.addEventListener("keypress", this.onPaginationInputKeypress.bind(this));
    });
  }

  updateAndSubmitPaginationInput(nextPage) {
    const paginationInput = document.querySelector("tr + tr input[name=\"current_page\"]");
    paginationInput.value = nextPage;
    paginationInput.closest("form")?.submit();
  }

  onPaginationLinkClick(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const nextPage = link.getAttribute("data-nextpage") ?? "";
    this.updateAndSubmitPaginationInput(nextPage);
  }

  onPaginationInputKeypress(e) {
    e.preventDefault();

    if (e.key !== "Enter") {
      return;
    }

    const input = e.currentTarget;
    const nextPage = input.value;
    this.updateAndSubmitPaginationInput(nextPage);
  }
}

new RecordlistPagination();
