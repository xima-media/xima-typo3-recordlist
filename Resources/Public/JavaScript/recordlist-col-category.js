import Icons from '@typo3/backend/icons.js'
import AjaxRequest from '@typo3/core/ajax/ajax-request.js'
import Notification from "@typo3/backend/notification.js";
import Modal from '@typo3/backend/modal.js';
import {SeverityEnum} from '@typo3/backend/enum/severity.js'

class RecordlistColCategory {


  constructor() {
    const searchForm = document.querySelector('#recordlist-search-form')

    document.querySelectorAll('a.badge.badge-pill.category-badge').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault()

        const fieldName = link.dataset.fieldName
        const categoryUid = link.dataset.categoryUid

        const treewrapperid = `tree_filter[${fieldName}][value]`
        const categoryFilterInput = searchForm.querySelector(`typo3-formengine-element-category[treewrapperid="${treewrapperid}"]`)
        const input = categoryFilterInput.querySelector('input[type="hidden"]')
        const value = input.value
        const overrideValues = input.dataset.overridevalues ? input.dataset.overridevalues : '[]'
        const overrideValuesArray = JSON.parse(overrideValues)

        if (link.classList.contains('badge-primary')) {
          const index = overrideValuesArray.indexOf(categoryUid)
          if (index > -1) {
            overrideValuesArray.splice(index, 1)
          }
          input.dataset.overridevalues = JSON.stringify(overrideValuesArray)

          const valuesArray = value ? value.split(',') : []
          const valueIndex = valuesArray.indexOf(categoryUid)
          if (valueIndex > -1) {
            valuesArray.splice(valueIndex, 1)
          }
          input.value = valuesArray.join(',')

        } else {
          overrideValuesArray.push(categoryUid)
          input.dataset.overridevalues = JSON.stringify(overrideValuesArray)
          input.value = value ? value + ',' + categoryUid : categoryUid
        }

        searchForm.requestSubmit()
      })
    })
  }
}

new RecordlistColCategory();
