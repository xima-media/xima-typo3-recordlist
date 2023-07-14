define(['TYPO3/CMS/Core/Ajax/AjaxRequest', 'TYPO3/CMS/Backend/Notification', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Enum/Severity', 'jquery', 'TYPO3/CMS/Backend/Utility'], (function (AjaxRequest, Notification, Modal, severity_js, $, Utility) { 'use strict';

    /*! *****************************************************************************
    Copyright (c) Microsoft Corporation.

    Permission to use, copy, modify, and/or distribute this software for any
    purpose with or without fee is hereby granted.

    THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
    REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
    INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
    LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
    OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
    PERFORMANCE OF THIS SOFTWARE.
    ***************************************************************************** */
    /* global Reflect, Promise */


    function __awaiter(thisArg, _arguments, P, generator) {
        function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
        return new (P || (P = Promise))(function (resolve, reject) {
            function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
            function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
            function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
            step((generator = generator.apply(thisArg, _arguments || [])).next());
        });
    }

    class Recordlist {
        constructor() {
            this.confirmDeleteRecordFromWorkspace = (e) => {
                const $tr = $(e.target).closest('tr');
                const $modal = Modal.confirm(TYPO3.lang['window.discard.title'], TYPO3.lang['window.discard.message'], severity_js.SeverityEnum.warning, [
                    {
                        text: TYPO3.lang.cancel,
                        active: true,
                        btnClass: 'btn-default',
                        name: 'cancel',
                        trigger: () => {
                            $modal.modal('hide');
                        }
                    },
                    {
                        text: TYPO3.lang.ok,
                        btnClass: 'btn-warning',
                        name: 'ok'
                    }
                ]);
                $modal.on('button.clicked', (modalEvent) => {
                    if (modalEvent.target.name === 'ok') {
                        const payload = {
                            action: 'Actions',
                            data: [$tr.data('table'), $tr.data('uid')],
                            method: 'deleteSingleRecord',
                            tid: 2,
                            type: 'rpc'
                        };
                        new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
                            .post(payload, {
                            headers: {
                                'Content-Type': 'application/json; charset=utf-8'
                            }
                        })
                            .then((response) => __awaiter(this, void 0, void 0, function* () {
                            top.TYPO3.Backend.ContentContainer.refresh();
                        }));
                        $modal.modal('hide');
                    }
                });
            };
            this.bindEvents();
        }
        bindEvents() {
            var _a;
            document.querySelectorAll('[data-workspace-action="readyToPublish"]').forEach(btn => {
                btn.addEventListener('click', this.onReadyToPublishClick.bind(this));
            });
            document.querySelectorAll('[data-workspace-action="remove"]').forEach(btn => {
                btn.addEventListener('click', this.confirmDeleteRecordFromWorkspace.bind(this));
            });
            document.querySelectorAll('th a[data-order-field]').forEach(a => {
                a.addEventListener('click', this.onOrderLinkClick.bind(this));
            });
            document.querySelectorAll('a[data-nextpage]').forEach(a => {
                a.addEventListener('click', this.onPaginationLinkClick.bind(this));
            });
            (_a = document.querySelector('.toggleSearchButton')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', e => {
                var _a;
                e.preventDefault();
                const button = e.currentTarget;
                button.classList.toggle('active');
                const isActive = button.classList.contains('active') ? '1' : '0';
                this.updateUserSettings('isSearchButtonActive', isActive);
                (_a = document.querySelector('#searchInputs')) === null || _a === void 0 ? void 0 : _a.classList.toggle('hidden');
            });
        }
        updateUserSettings(settingName, settingValue) {
            const payload = new FormData();
            payload.append(TYPO3.settings.XimaTypo3Recordlist.moduleName + '[' + settingName + ']', settingValue);
            new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_usersetting).post('', { body: payload });
        }
        onPaginationLinkClick(e) {
            var _a, _b;
            e.preventDefault();
            const link = e.currentTarget;
            const nextPage = (_a = link.getAttribute('data-nextpage')) !== null && _a !== void 0 ? _a : '';
            const paginationInput = document.querySelector('tr + tr input[name="current_page"]');
            paginationInput.value = nextPage;
            (_b = paginationInput.closest('form')) === null || _b === void 0 ? void 0 : _b.submit();
        }
        onOrderLinkClick(e) {
            var _a, _b, _c;
            e.preventDefault();
            const link = e.currentTarget;
            const field = (_a = link.getAttribute('data-order-field')) !== null && _a !== void 0 ? _a : '';
            const direction = (_b = link.getAttribute('data-order-direction')) !== null && _b !== void 0 ? _b : '';
            const fieldInput = document.querySelector('input[name="order_field"]');
            const directionInput = document.querySelector('input[name="order_direction"]');
            fieldInput.value = field;
            directionInput.value = direction;
            (_c = fieldInput.closest('form')) === null || _c === void 0 ? void 0 : _c.submit();
        }
        onReadyToPublishClick(e) {
            e.preventDefault();
            const tr = e.currentTarget.closest('tr');
            const affectedRecord = {
                table: tr.getAttribute('data-table'),
                uid: tr.getAttribute('data-uid'),
                t3ver_oid: tr.getAttribute('data-t3ver_oid')
            };
            const payload = {
                action: 'Actions',
                data: ['-10', [affectedRecord], TYPO3.settings.Workspaces.token],
                method: 'sendToSpecificStageWindow',
                tid: 2,
                type: 'rpc'
            };
            new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
                .post(payload, {
                headers: {
                    'Content-Type': 'application/json; charset=utf-8'
                }
            })
                .then((response) => __awaiter(this, void 0, void 0, function* () {
                this.renderSendToStageWindow(yield response.resolve(), affectedRecord);
            }));
        }
        renderSendToStageWindow(response, affectedRecord) {
            const result = response[0].result;
            const $form = $('<form />');
            if (typeof result.sendMailTo !== 'undefined' && result.sendMailTo.length > 0) {
                $form.append($('<label />', { class: 'control-label' }).text(TYPO3.lang['window.sendToNextStageWindow.itemsWillBeSentTo']));
                $form.append($('<div />', { class: 'form-group' }).append($('<button type="button" class="btn btn-default btn-xs t3js-workspace-recipients-selectall" />').text(TYPO3.lang['window.sendToNextStageWindow.selectAll']), '&nbsp;', $('<button type="button" class="btn btn-default btn-xs t3js-workspace-recipients-deselectall" />').text(TYPO3.lang['window.sendToNextStageWindow.deselectAll'])));
                for (const recipient of result.sendMailTo) {
                    $form.append($('<div />', { class: 'form-check' }).append($('<input />', {
                        type: 'checkbox',
                        name: 'recipients',
                        class: 'form-check-input t3js-workspace-recipient',
                        id: recipient.name,
                        value: recipient.value
                    })
                        .prop('checked', recipient.checked)
                        .prop('disabled', recipient.disabled), $('<label />', {
                        class: 'form-check-label',
                        for: recipient.name
                    }).text(recipient.label)));
                }
            }
            if (typeof result.additional !== 'undefined') {
                $form.append($('<div />', { class: 'form-group hidden' }).append($('<label />', {
                    class: 'control-label',
                    for: 'additional'
                }).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients']), $('<textarea />', {
                    class: 'form-control',
                    name: 'additional',
                    id: 'additional'
                }).text(result.additional.value), $('<span />', { class: 'help-block' }).text(TYPO3.lang['window.sendToNextStageWindow.additionalRecipients.hint'])));
            }
            $form.append($('<div />', { class: 'form-group' }).append($('<label />', {
                class: 'control-label',
                for: 'comments'
            }).text(TYPO3.lang['window.sendToNextStageWindow.comments']), $('<textarea />', {
                class: 'form-control',
                name: 'comments',
                id: 'comments'
            }).text(result.comments.value)));
            Modal.show(TYPO3.lang.actionSendToStage, $form, severity_js.SeverityEnum.info, [
                {
                    text: TYPO3.lang.cancel,
                    active: true,
                    btnClass: 'btn-default',
                    name: 'cancel',
                    trigger: () => {
                        Modal.currentModal.trigger('modal-dismiss');
                    }
                },
                {
                    text: TYPO3.lang.ok,
                    btnClass: 'btn-primary',
                    name: 'ok',
                    trigger: () => {
                        const serializedForm = Utility.convertFormToObject($form.get(0));
                        serializedForm.affects = {
                            elements: [affectedRecord],
                            nextStage: -10
                        };
                        const payload = {
                            action: 'Actions',
                            data: [serializedForm, TYPO3.settings.Workspaces.token],
                            method: 'sendToSpecificStageExecute',
                            tid: 2,
                            type: 'rpc'
                        };
                        new AjaxRequest(TYPO3.settings.ajaxUrls.workspace_dispatch)
                            .post(payload, {
                            headers: {
                                'Content-Type': 'application/json; charset=utf-8'
                            }
                        })
                            .then((response) => __awaiter(this, void 0, void 0, function* () {
                            Notification.success('Anfrage erfolgreich', 'Die Anfrage zur Freigabe wurde erfolgreich übermittelt');
                            Modal.currentModal.trigger('modal-dismiss');
                            top.TYPO3.Backend.ContentContainer.refresh();
                        }));
                    }
                }
            ]);
        }
    }
    // eslint-disable-next-line no-unused-vars
    new Recordlist();

}));
