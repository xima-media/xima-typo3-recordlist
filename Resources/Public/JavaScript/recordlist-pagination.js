export default class RecordlistPagination {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a[data-nextpage]").forEach(link => {
      link.addEventListener("click", this.onPaginationLinkClick.bind(this));
    });

    document.querySelectorAll("a[data-action=\"pagination-jump\"]").forEach(link => {
      link.addEventListener("click", this.onPaginationJump.bind(this));
    });

    document.querySelectorAll("input[name=\"current_page\"]").forEach(input => {
      input.addEventListener("keypress", this.onPaginationInputKeypress.bind(this));
    });

    document.querySelectorAll("select[name=\"items_per_page\"]").forEach(select => {
      select.addEventListener("change", this.onItemsPerPageChange.bind(this));
    });
  }

  updateAndSubmitPaginationInput(nextPage) {
    const paginationInputs = document.querySelectorAll("input[name=\"current_page\"]");
    paginationInputs.forEach(input => {
      input.value = nextPage;
    });
    paginationInputs[0]?.closest("form")?.submit();
  }

  onPaginationLinkClick(e) {
    e.preventDefault();
    const link = e.currentTarget;
    const nextPage = link.getAttribute("data-nextpage") ?? "";
    this.updateAndSubmitPaginationInput(nextPage);
  }

  onPaginationJump(e) {
    e.preventDefault();
    const paginationInput = document.querySelector("input[name=\"current_page\"]");
    const currentPage = paginationInput?.value ?? "1";
    this.updateAndSubmitPaginationInput(currentPage);
  }

  onPaginationInputKeypress(e) {
    if (e.key !== "Enter") {
      return;
    }

    const input = e.currentTarget;
    const nextPage = input.value;
    this.updateAndSubmitPaginationInput(nextPage);
  }

  onItemsPerPageChange(e) {
    const select = e.currentTarget;
    document.querySelectorAll("select[name=\"items_per_page\"]").forEach(otherSelect => {
      otherSelect.value = select.value;
    });
    select.closest("form")?.submit();
  }
}

new RecordlistPagination();
