import UserSettings from "./user-settings.js";

export default class RecordlistFilterToggle {

  typo3version = null;

  containerElement = null;

  showFilterButton = null;

  hideFilterButton = null;

  filterInputsContainer = null;

  constructor(typo3version) {
    this.typo3version = typo3version;
    this.cacheDom();
    this.bindEvents();
  }

  cacheDom() {
    this.showFilterButton = document.querySelector(".toggleFiltersButton.show");
    this.hideFilterButton = document.querySelector(".toggleFiltersButton.toHide");
    this.filterInputsContainer = document.querySelector("#filterInputs");

    if (this.typo3version === 13) {
      this.containerElement = document.querySelector(".module-body");
    } else {
      this.containerElement = window;
    }
  }

  bindEvents() {
    this.showFilterButton.addEventListener("click", this.onFilterToggleClick.bind(this));
    this.hideFilterButton.addEventListener("click", this.onFilterToggleClick.bind(this));

    // add intersection observer to change the state of the toggle buttons based on the scroll position
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.target.id === "filterInputs") {
          if (entry.isIntersecting) {
            this.showFilterButton.classList.add("hidden");
            this.hideFilterButton.classList.remove("hidden");
          } else {
            this.showFilterButton.classList.remove("hidden");
            this.hideFilterButton.classList.add("hidden");
          }
        }
      });
    }, {threshold: 0.1});

    observer.observe(this.filterInputsContainer);
  }

  onFilterToggleClick(e) {
    e.preventDefault();

    // scrollt to top
    this.containerElement.scrollTo({top: 0, behavior: "smooth"});

    const scrollTop = this.containerElement.scrollTop ?? this.containerElement.scrollY ?? 0;

    // if scroll position is not at the top (+ some offset for the filterbox), do not close the filterbox, but scroll to the top
    if (scrollTop > 100 && !document.querySelector("#filterInputs").classList.contains("hidden")) {
      return;
    }

    const isActive = this.filterInputsContainer.classList.contains("hidden");

    UserSettings.update(TYPO3.settings.XimaTypo3Recordlist.currentTable + ".isFilterButtonActive", isActive ? 1 : 0);

    if (isActive) {
      this.filterInputsContainer.classList.remove("hidden");
      this.showFilterButton.classList.add("hidden");
      this.hideFilterButton.classList.remove("hidden");
    } else {
      this.filterInputsContainer.classList.add("hidden");
      this.showFilterButton.classList.remove("hidden");
      this.hideFilterButton.classList.add("hidden");
    }
  }
}
