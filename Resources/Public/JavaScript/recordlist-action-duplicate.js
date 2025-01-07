export default class RecordlistActionDuplicate {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a.duplicate").forEach(link => {
      link.addEventListener("click", this.onDuplicateClick.bind(this));
    });
  }

  onDuplicateClick(e) {
    e.preventDefault();
    const linkElement = e.currentTarget;

    const url = linkElement.getAttribute("href");
    const form = document.createElement("form");
    form.method = "POST";
    form.action = url;
    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "_duplicatedoc";
    input.value = "1";
    form.appendChild(input);
    document.body.appendChild(form);

    form.submit();
  }
}

new RecordlistActionDuplicate();
