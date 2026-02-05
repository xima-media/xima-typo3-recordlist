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
    const button = e.currentTarget;
    button.classList.toggle("active");
    const isActive = button.classList.contains("active") ? "1" : "0";
    UserSettings.update(TYPO3.settings.XimaTypo3Recordlist.currentTable + ".isFilterButtonActive", isActive);
    document.querySelector("#filterInputs").classList.toggle("hidden");
  }
}

new RecordlistFilterToggle();
