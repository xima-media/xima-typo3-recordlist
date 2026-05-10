import DocumentService from "@typo3/core/document-service.js";

class RecordlistColGroup {

  constructor() {
    DocumentService.ready().then(() => {
      this.init()
    });
  }

  init() {
    const searchForm = document.querySelector('#recordlist-search-form')
    if (!searchForm) {
      return
    }

    document.querySelectorAll('a.badge.badge-pill.group-badge').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault()

        const fieldName = link.dataset.fieldName
        const storedValue = link.dataset.storedValue

        const selectInput = searchForm.querySelector(`select[name="filter[${fieldName}][value]"]`)
        if (!selectInput) {
          return
        }

        // Toggle: clicking an already-active badge clears the filter
        if (selectInput.value === storedValue) {
          selectInput.value = ''
        } else {
          selectInput.value = storedValue
        }

        searchForm.requestSubmit()
      })
    })
  }
}

new RecordlistColGroup()
