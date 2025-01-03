import Icons from "@typo3/backend/icons.js";

export default class RecordlistLoadingAnimations {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll("a.btn.translate").forEach(a => {
      a.addEventListener("click", this.onButtonClick.bind(this));
    });
  }

  onButtonClick(e) {
    const btn = e.currentTarget;
    Icons.getIcon("spinner-circle-dark", Icons.sizes.small, null, "disabled").then((icon) => {
      btn.classList.add("active");
      btn.innerHTML = icon;
    });
  }
}

new RecordlistLoadingAnimations();
