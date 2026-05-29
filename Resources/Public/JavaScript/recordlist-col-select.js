import DocumentService from "@typo3/core/document-service.js";

class RecordlistColSelect {

  constructor() {
    DocumentService.ready().then(() => {
      this.init()
    })
  }

  init() {
    const searchForm = document.querySelector('#recordlist-search-form')
    if (!searchForm) {
      return
    }

    document.querySelectorAll('a.badge.badge-pill.select-badge').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault()

        const fieldName = link.dataset.fieldName
        const value = link.dataset.value

        const selectInput = searchForm.querySelector(`select[name="filter[${fieldName}][value]"]`)
        if (!selectInput) {
          return
        }

        // Toggle: clicking an already-active badge clears the filter
        if (selectInput.value === value) {
          selectInput.value = ''
        } else {
          selectInput.value = value
        }

        searchForm.requestSubmit()
      })
    })
  }
}

new RecordlistColSelect()
