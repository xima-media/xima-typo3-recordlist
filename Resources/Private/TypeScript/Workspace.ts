// @ts-expect-error
import AjaxRequest from '@typo3/core/ajax/ajax-request'
// @ts-expect-error
import Notification from '@typo3/backend/notification'
// @ts-expect-error
import Modal from '@typo3/backend/modal'
// @ts-expect-error
import { SeverityEnum } from '@typo3/backend/enum/severity'
// @ts-expect-error
import $ from 'jquery'
// @ts-expect-error
import Utility from '@typo3/backend/utility'

class Workspace {
  protected currentModal: any

  protected moduleName: string

  constructor() {
    this.bindEvents()
  }

  protected bindEvents(): void {
    document.querySelectorAll('[data-workspace-action="readyToPublish"]').forEach(btn => {
      btn.addEventListener('click', this.onReadyToPublishClick.bind(this))
    })

    document.querySelectorAll('[data-workspace-action="remove"]').forEach(btn => {
      btn.addEventListener('click', this.confirmDeleteRecordFromWorkspace.bind(this))
    })

    document.querySelectorAll('[data-workspace-action="publish"]').forEach(btn => {
      btn.addEventListener('click', this.confirmPublishRecordFromWorkspace.bind(this))
    })
  }

  protected confirmPublishRecordFromWorkspace(e: PointerEvent): void {
    e.preventDefault()
    const btn = e.currentTarget as HTMLAnchorElement
    const tr = btn.closest('tr')

    if (!tr) {
      return
    }

    // eslint-disable-next-line @typescript-eslint/no-this-alias
    const self = this
    const uid = parseInt(tr.getAttribute('data-uid') ?? '')
    const table = tr.getAttribute('data-table') ?? ''
    let t3verOid = parseInt(tr.getAttribute('data-t3ver_oid') ?? '')
    if (!t3verOid) {
      t3verOid = uid
    }

    const recordsToPublish = [
      {
        liveId: t3verOid,
        table: table,
        versionId: uid
      }
    ]

    const sysFileReferencesToPublish = tr.getAttribute('data-sys-file-references') ?? ''
    if (sysFileReferencesToPublish) {
      const sysFileReferences = JSON.parse(sysFileReferencesToPublish)
      Array.prototype.push.apply(recordsToPublish, sysFileReferences)
    }

    Modal.advanced({
      title: 'Datensatz veröffentlichen',
      size: Modal.sizes.small,
      severity: SeverityEnum.info,
      content: 'Möchten Sie den Datensatz wirklich veröffentlichen?',
      buttons: [
        {
          text: 'Nein, abbrechen',
          icon: 'actions-close',
          btnClass: 'btn-default',
          trigger: function () {
            Modal.currentModal.trigger('modal-dismiss')
          }
        },
        {
          text: 'Ja, veröffentlichen',
          icon: 'actions-check',
          btnClass: 'btn-success',
          trigger: function () {
            self.publishRecords(recordsToPublish)
            Modal.currentModal.trigger('modal-dismiss')
          }
        }
      ]
    })
  }
  protected publishRecords(records): void {
    const payload = {
      action: 'Actions',
      data: [
        {
          action: 'publish',
          selection: records
        }
      ],
      method: 'executeSelectionAction',
      tid: 3,
      type: 'rpc'
    }

    new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
      .post(payload, {
        headers: {
          'Content-Type': 'application/json; charset=utf-8'
        }
      })
      .then(async (response: { resolve: () => any[] | PromiseLike<any[]> }) => {
        top?.TYPO3.Backend.ContentContainer.refresh()
      })
  }

  private readonly confirmDeleteRecordFromWorkspace = (e: { target: any }): void => {
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
    $modal.on('button.clicked', (modalEvent: { target: HTMLAnchorElement }): void => {
      if (modalEvent.target.name === 'ok') {
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
          .then(async () => {
            top?.TYPO3.Backend.ContentContainer.refresh()
          })
        $modal.modal('hide')
      }
    })
  }

  protected onReadyToPublishClick(e: PointerEvent): void {
    e.preventDefault()
    const eventTarget: HTMLElement = e.currentTarget as HTMLElement
    const tr = eventTarget.closest('tr')

    if (!tr) {
      return
    }

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
      .then(async (response: { resolve: () => any[] | PromiseLike<any[]> }) => {
        this.renderSendToStageWindow(await response.resolve(), affectedRecord)
      })
  }

  protected renderSendToStageWindow(response: any[], affectedRecord: Object): void {
    const result = response[0].result
    const $form = $('<form />')

    if (typeof result.sendMailTo !== 'undefined' && result.sendMailTo.length > 0) {
      $form.append(
        $('<div />', { class: 'form-group hidden' }).append(
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
          $('<div />', { class: 'form-check hidden' }).append(
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
            .then(async () => {
              Notification.success('Anfrage erfolgreich', 'Die Anfrage zur Freigabe wurde erfolgreich übermittelt')
              Modal.currentModal.trigger('modal-dismiss')
              top?.TYPO3.Backend.ContentContainer.refresh()
            })
        }
      }
    ])
  }
}

new Workspace()
