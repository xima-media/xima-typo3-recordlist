// @ts-expect-error
import RegularEvent from '@typo3/core/event/regular-event'
// @ts-expect-error
import AjaxRequest from '@typo3/core/ajax/ajax-request'
// @ts-expect-error
import AjaxResponse from '@typo3/core/ajax/ajax-response'
// @ts-expect-error
import Notification from '@typo3/backend/notification'
// @ts-expect-error
import Icons from '@typo3/backend/icons'
// @ts-expect-error
import Modal from '@typo3/backend/modal'
// @ts-expect-error
import { SeverityEnum } from '@typo3/backend/enum/severity'
// @ts-expect-error
import $ from 'jquery'
// @ts-expect-error
import Utility from '@typo3/backend/utility'
// @ts-expect-error
import NProgress from 'nprogress'

class Recordlist {
  protected currentModal: any

  constructor() {
    this.bindEvents()
  }

  protected bindEvents() {
    document.querySelectorAll('[data-workspace-action="readyToPublish"]').forEach(btn => {
      btn.addEventListener('click', this.onReadyToPublishClick.bind(this))
    })

    document.querySelectorAll('[data-workspace-action="remove"]').forEach(btn => {
      btn.addEventListener('click', this.confirmDeleteRecordFromWorkspace.bind(this))
    })

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
      document.querySelector('#searchInputs')?.classList.toggle('hidden')
    })
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

  private readonly confirmDeleteRecordFromWorkspace = (e): void => {
    const $tr = $(e.target).closest('tr')
    const $modal = Modal.confirm(TYPO3.lang['window.discard.title'], TYPO3.lang['window.discard.message'], SeverityEnum.warning, [
      {
        text: TYPO3.lang.cancel,
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: (): void => {
          $modal.modal('hide')
        }
      },
      {
        text: TYPO3.lang.ok,
        btnClass: 'btn-warning',
        name: 'ok'
      }
    ])
    $modal.on('button.clicked', (modalEvent): void => {
      if ((modalEvent.target as HTMLAnchorElement).name === 'ok') {
        const payload = {
          action: 'Actions',
          data: [$tr.data('table'), $tr.data('uid')],
          method: 'deleteSingleRecord',
          tid: 2,
          type: 'rpc'
        }

        new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
          .post(payload, {
            headers: {
              'Content-Type': 'application/json; charset=utf-8'
            }
          })
          .then(async response => {
            top.TYPO3.Backend.ContentContainer.refresh()
          })
        $modal.modal('hide')
      }
    })
  }

  protected onReadyToPublishClick(e: PointerEvent) {
    e.preventDefault()
    const tr = e.currentTarget.closest('tr')
    const affectedRecord = {
      table: tr.getAttribute('data-table'),
      uid: tr.getAttribute('data-uid'),
      t3ver_oid: tr.getAttribute('data-t3ver_oid')
    }

    const payload = {
      action: 'Actions',
      data: ['-10', [affectedRecord], TYPO3.settings.Workspaces.token],
      method: 'sendToSpecificStageWindow',
      tid: 2,
      type: 'rpc'
    }

    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
      .post(payload, {
        headers: {
          'Content-Type': 'application/json; charset=utf-8'
        }
      })
      .then(async response => {
        this.renderSendToStageWindow(await response.resolve(), affectedRecord)
      })
  }

  protected renderSendToStageWindow(response, affectedRecord) {
    const result = response[0].result
    const $form = $('<form />')

    if (typeof result.sendMailTo !== 'undefined' && result.sendMailTo.length > 0) {
      $form.append($('<label />', { class: 'control-label' }).text(TYPO3.lang['window.sendToNextStageWindow.itemsWillBeSentTo']))
      $form.append(
        $('<div />', { class: 'form-group' }).append(
          $('<button type="button" class="btn btn-default btn-xs t3js-workspace-recipients-selectall" />').text(
            TYPO3.lang['window.sendToNextStageWindow.selectAll']
          ),
          '&nbsp;',
          $('<button type="button" class="btn btn-default btn-xs t3js-workspace-recipients-deselectall" />').text(
            TYPO3.lang['window.sendToNextStageWindow.deselectAll']
          )
        )
      )

      for (const recipient of result.sendMailTo) {
        $form.append(
          $('<div />', { class: 'form-check' }).append(
            $('<input />', {
              type: 'checkbox',
              name: 'recipients',
              class: 'form-check-input t3js-workspace-recipient',
              id: recipient.name,
              value: recipient.value
            })
              .prop('checked', recipient.checked)
              .prop('disabled', recipient.disabled),
            $('<label />', {
              class: 'form-check-label',
              for: recipient.name
            }).text(recipient.label)
          )
        )
      }
    }

    if (typeof result.additional !== 'undefined') {
      $form.append(
        $('<div />', { class: 'form-group hidden' }).append(
          $('<label />', {
            class: 'control-label',
            for: 'additional'
          }).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients']),
          $('<textarea />', {
            class: 'form-control',
            name: 'additional',
            id: 'additional'
          }).text(result.additional.value),
          $('<span />', { class: 'help-block' }).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients.hint'])
        )
      )
    }

    $form.append(
      $('<div />', { class: 'form-group' }).append(
        $('<label />', {
          class: 'control-label',
          for: 'comments'
        }).text(TYPO3.lang['window.sendToNextStageWindow.comments']),
        $('<textarea />', {
          class: 'form-control',
          name: 'comments',
          id: 'comments'
        }).text(result.comments.value)
      )
    )

    Modal.show(TYPO3.lang.actionSendToStage, $form, SeverityEnum.info, [
      {
        text: TYPO3.lang.cancel,
        active: true,
        btnClass: 'btn-default',
        name: 'cancel',
        trigger: (): void => {
          Modal.currentModal.trigger('modal-dismiss')
        }
      },
      {
        text: TYPO3.lang.ok,
        btnClass: 'btn-primary',
        name: 'ok',
        trigger: (): void => {
          const serializedForm = Utility.convertFormToObject($form.get(0))
          serializedForm.affects = {
            elements: [affectedRecord],
            nextStage: -10
          }

          const payload = {
            action: 'Actions',
            data: [serializedForm, TYPO3.settings.Workspaces.token],
            method: 'sendToSpecificStageExecute',
            tid: 2,
            type: 'rpc'
          }

          new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
            .post(payload, {
              headers: {
                'Content-Type': 'application/json; charset=utf-8'
              }
            })
            .then(async response => {
              Notification.success('Anfrage erfolgreich', 'Die Anfrage zur Freigabe wurde erfolgreich übermittelt')
              Modal.currentModal.trigger('modal-dismiss')
              top.TYPO3.Backend.ContentContainer.refresh()
            })
        }
      }
    ])
  }
}

// eslint-disable-next-line no-unused-vars
const RecordlistInstance = new Recordlist()
