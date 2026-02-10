import UserSettings from "./user-settings.js";

export default class RecordlistFilterToggle {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll(".toggleFiltersButton").forEach(link => {
      link.addEventListener("click", this.onFilterToggleClick.bind(this));
    });
  }

  onFilterToggleClick(e) {
    e.preventDefault();

    // v13: use module-body instead of the iframe
    let container = document.querySelector(".module-body");
    if (!container) {
      container = window;
    }

    // scrollt to top
    container.scrollTo({top: 0, behavior: "smooth"});

    // if scroll position is not at the top (+ some offset for the filterbox), do not close the filterbox, but scroll to the top
    if (container.scrollTop > 100 && !document.querySelector("#filterInputs").classList.contains("hidden")) {
      return;
    }

    const button = e.currentTarget;
    button.classList.toggle("active");
    const isActive = button.classList.contains("active") ? "1" : "0";
    UserSettings.update(TYPO3.settings.XimaTypo3Recordlist.currentTable + ".isFilterButtonActive", isActive);
    document.querySelector("#filterInputs").classList.toggle("hidden");
  }
}

new RecordlistFilterToggle();
