import UserSettings from "./user-settings.js";

export default class RecordlistSearchToggle {
  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll(".toggleSearchButton").forEach(link => {
      link.addEventListener("click", this.onSearchToggleClick.bind(this));
    });
  }

  onSearchToggleClick(e) {
    e.preventDefault();
    const button = e.currentTarget;
    button.classList.toggle("active");
    const isActive = button.classList.contains("active") ? "1" : "0";
    UserSettings.update(TYPO3.settings.XimaTypo3Recordlist.currentTable + ".isSearchButtonActive", isActive);
    document.querySelector("#searchInputs").classList.toggle("hidden");
  }
}

new RecordlistSearchToggle();
