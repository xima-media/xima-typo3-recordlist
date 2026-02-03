import UserSettings from "./user-settings.js";

export default class RecordlistTemplateSelection {

  constructor() {
    this.init();
  }

  init() {
    document.querySelectorAll('*[data-doc-button="templateSelection"]').forEach(link => {
      link.addEventListener("click", this.onTemplateClick.bind(this));
    });
  }

  async onTemplateClick(e) {
    e.preventDefault();

    const templateName = e.currentTarget.dataset.templateName;
    if (!templateName) {
      return;
    }

    const tableName = TYPO3.settings.XimaTypo3Recordlist.currentTable;
    const settingName = tableName + '.template';

    try {
      await UserSettings.update(settingName, templateName);
      // Reload the page to apply the new template
      window.location.reload();
    } catch (error) {
      console.error('Failed to save template selection:', error);
    }
  }
}

new RecordlistTemplateSelection();
