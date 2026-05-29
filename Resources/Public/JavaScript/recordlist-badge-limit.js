import DocumentService from "@typo3/core/document-service.js";
import UserSettings from "./user-settings.js";

class RecordlistBadgeLimit {

  constructor() {
    DocumentService.ready().then(() => {
      this.init();
    });
  }

  init() {
    document.querySelectorAll('.badge-show-more').forEach(button => {
      button.addEventListener('click', event => {
        const container = event.currentTarget.closest('.badge-container')
        const tableName = event.currentTarget.closest('tr').getAttribute('data-table')
        const columnName = container.getAttribute('data-column-name')

        UserSettings.update(`badge_limits_expanded.${tableName}.${columnName}`, true)

        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-over-limit`).forEach(badge => badge.classList.remove('badge-hidden'))
        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-show-less`).forEach(btn => btn.classList.remove('badge-hidden'))
        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-show-more`).forEach(btn => btn.classList.add('badge-hidden'))
      })
    });

    document.querySelectorAll('.badge-show-less').forEach(button => {
      button.addEventListener('click', event => {
        const container = event.currentTarget.closest('.badge-container')
        const tableName = event.currentTarget.closest('tr').getAttribute('data-table')
        const columnName = container.getAttribute('data-column-name')

        UserSettings.update(`badge_limits_expanded.${tableName}.${columnName}`, false)

        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-over-limit`).forEach(badge => badge.classList.add('badge-hidden'))
        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-show-more`).forEach(btn => btn.classList.remove('badge-hidden'))
        document.querySelectorAll(`.badge-container[data-column-name="${columnName}"] .badge-show-less`).forEach(btn => btn.classList.add('badge-hidden'))
      })
    });
  }

}

new RecordlistBadgeLimit();
