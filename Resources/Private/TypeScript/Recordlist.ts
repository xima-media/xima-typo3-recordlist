// @ts-expect-error
import AjaxRequest from '@typo3/core/ajax/ajax-request'
// @ts-expect-error
import Modal from '@typo3/backend/modal'
// @ts-expect-error
import { SeverityEnum } from '@typo3/backend/enum/severity'

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

    document.querySelectorAll('a[data-delete2]').forEach(a => {
      a.addEventListener('click', this.onDeleteLinkClick.bind(this))
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
    const btn = e.currentTarget as HTMLAnchorElement
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
      title: TYPO3.lang.newRecordinPageModalTitle,
      size: Modal.sizes.small,
      content: selection,
      buttons: [
        {
          text: btn.getAttribute('title'),
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

  protected onDeleteLinkClick(e: PointerEvent): void {
    const btn = e.currentTarget as HTMLAnchorElement
    const table = btn?.closest('tr')?.getAttribute('data-table') ?? ''
    const uid = btn?.closest('tr')?.getAttribute('data-uid') ?? ''
    const verOid = btn?.closest('tr')?.getAttribute('data-t3ver_oid') ?? ''

    const payload = new FormData()
    payload.append('table', table)
    payload.append('uid', uid)
    payload.append('verOid', verOid)

    const $modal = Modal.confirm(
      'Datensatz löschen',
      'Sind Sie sich sicher, dass Sie diesen Datensatz löschen möchten?',
      SeverityEnum.warning,
      [
        {
          text: 'Nein, abbrechen',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => {
            $modal.modal('hide')
          }
        },
        {
          text: 'Ja, löschen',
          btnClass: 'btn-warning',
          name: 'ok'
        }
      ]
    )
    $modal.on('button.clicked', (modalEvent: { target: HTMLAnchorElement }): void => {
      if (modalEvent.target.name === 'ok') {
        new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_delete).post('', { body: payload }, '').then(async () => {
          top?.TYPO3.Backend.ContentContainer.refresh()
        })
        $modal.modal('hide')
      }
    })
  }
}

new Recordlist()
