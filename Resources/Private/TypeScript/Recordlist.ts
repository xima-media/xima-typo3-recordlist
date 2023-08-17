// @ts-expect-error
import AjaxRequest from '@typo3/core/ajax/ajax-request'
// @ts-expect-error
import Modal from '@typo3/backend/modal'

class Recordlist {
  protected currentModal: any

  protected moduleName: string

  constructor() {
    this.bindEvents()
  }

  protected bindEvents(): void {
    document.querySelectorAll('th a[data-order-field]').forEach(a => {
      a.addEventListener('click', this.onOrderLinkClick.bind(this))
    })

    document.querySelectorAll('a[data-nextpage]').forEach(a => {
      a.addEventListener('click', this.onPaginationLinkClick.bind(this))
    })

    document.querySelector('.toggleSearchButton')?.addEventListener('click', e => {
      e.preventDefault()
      const button = e.currentTarget as HTMLAnchorElement
      button.classList.toggle('active')
      const isActive = button.classList.contains('active') ? '1' : '0'
      this.updateUserSettings('isSearchButtonActive', isActive)
      document.querySelector('#searchInputs')?.classList.toggle('hidden')
    })

    if (document.querySelectorAll('.new-record-in-page').length > 1) {
      document.querySelectorAll('.new-record-in-page').forEach(btn => {
        btn.addEventListener('click', this.onNewRecordInPageClick.bind(this))
      })
    }
  }

  protected onNewRecordInPageClick(e: PointerEvent): void {
    e.preventDefault()
    // construct select element
    const selection = document.createElement('select')
    selection.id = 'page-for-new-record'
    selection.classList.add('form-select')
    document.querySelectorAll('.new-record-in-page.hidden').forEach(btn => {
      const option = document.createElement('option')
      option.value = btn.getAttribute('href') ?? ''
      option.text = btn.getAttribute('title') ?? ''
      selection.appendChild(option)
    })
    // display modal
    Modal.advanced({
      title: 'Select page for creation',
      size: Modal.sizes.small,
      content: selection,
      buttons: [
        {
          text: 'Create element',
          icon: 'actions-add',
          btnClass: 'btn-primary',
          trigger: function () {
            // @ts-expect-error
            top.list_frame.location.href = selection.value
            Modal.currentModal.trigger('modal-dismiss')
          }
        }
      ]
    })
  }

  protected updateUserSettings(settingName: string, settingValue: string): void {
    const payload = new FormData()
    // eslint-disable-next-line @typescript-eslint/restrict-plus-operands
    payload.append(TYPO3.settings.XimaTypo3Recordlist.moduleName + '[' + settingName + ']', settingValue)
    new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_usersetting).post('', { body: payload })
  }

  protected onPaginationLinkClick(e: PointerEvent): void {
    e.preventDefault()
    const link = e.currentTarget as HTMLAnchorElement
    const nextPage: string = link.getAttribute('data-nextpage') ?? ''
    const paginationInput = document.querySelector('tr + tr input[name="current_page"]') as HTMLInputElement
    paginationInput.value = nextPage
    paginationInput.closest('form')?.submit()
  }

  protected onOrderLinkClick(e: PointerEvent): void {
    e.preventDefault()
    const link = e.currentTarget as HTMLAnchorElement
    const field = link.getAttribute('data-order-field') ?? ''
    const direction = link.getAttribute('data-order-direction') ?? ''
    const fieldInput = document.querySelector('input[name="order_field"]') as HTMLInputElement
    const directionInput = document.querySelector('input[name="order_direction"]') as HTMLInputElement
    fieldInput.value = field
    directionInput.value = direction
    fieldInput.closest('form')?.submit()
  }
}

new Recordlist()
