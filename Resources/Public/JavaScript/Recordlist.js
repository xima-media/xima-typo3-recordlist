define(['./tslib.es6-lce-iSb7', 'TYPO3/CMS/Core/Ajax/AjaxRequest', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Enum/Severity'], (function (tslib_es6, AjaxRequest, Modal, severity_js) { 'use strict';

    class Recordlist {
        constructor() {
            this.bindEvents();
        }
        bindEvents() {
            var _a;
            document.querySelectorAll('th a[data-order-field]').forEach(a => {
                a.addEventListener('click', this.onOrderLinkClick.bind(this));
            });
            document.querySelectorAll('a[data-nextpage]').forEach(a => {
                a.addEventListener('click', this.onPaginationLinkClick.bind(this));
            });
            document.querySelectorAll('a[data-delete2]').forEach(a => {
                a.addEventListener('click', this.onDeleteLinkClick.bind(this));
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
            if (document.querySelectorAll('.new-record-in-page').length > 1) {
                document.querySelectorAll('.new-record-in-page').forEach(btn => {
                    btn.addEventListener('click', this.onNewRecordInPageClick.bind(this));
                });
            }
        }
        onNewRecordInPageClick(e) {
            e.preventDefault();
            const btn = e.currentTarget;
            // construct select element
            const selection = document.createElement('select');
            selection.id = 'page-for-new-record';
            selection.classList.add('form-select');
            document.querySelectorAll('.new-record-in-page.hidden').forEach(btn => {
                var _a, _b;
                const option = document.createElement('option');
                option.value = (_a = btn.getAttribute('href')) !== null && _a !== void 0 ? _a : '';
                option.text = (_b = btn.getAttribute('title')) !== null && _b !== void 0 ? _b : '';
                selection.appendChild(option);
            });
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
                            top.list_frame.location.href = selection.value;
                            Modal.currentModal.trigger('modal-dismiss');
                        }
                    }
                ]
            });
        }
        updateUserSettings(settingName, settingValue) {
            const payload = new FormData();
            // eslint-disable-next-line @typescript-eslint/restrict-plus-operands
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
        onDeleteLinkClick(e) {
            var _a, _b, _c, _d;
            const btn = e.currentTarget;
            const table = (_b = (_a = btn === null || btn === void 0 ? void 0 : btn.closest('tr')) === null || _a === void 0 ? void 0 : _a.getAttribute('data-table')) !== null && _b !== void 0 ? _b : '';
            const uid = (_d = (_c = btn === null || btn === void 0 ? void 0 : btn.closest('tr')) === null || _c === void 0 ? void 0 : _c.getAttribute('data-uid')) !== null && _d !== void 0 ? _d : '';
            const $modal = Modal.confirm('Datensatz löschen', 'Sind Sie sich sicher, dass Sie diesen Datensatz löschen möchten?', severity_js.SeverityEnum.warning, [
                {
                    text: 'Nein, abbrechen',
                    active: true,
                    btnClass: 'btn-default',
                    name: 'cancel',
                    trigger: () => {
                        $modal.modal('hide');
                    }
                },
                {
                    text: 'Ja, löschen',
                    btnClass: 'btn-warning',
                    name: 'ok'
                }
            ]);
            $modal.on('button.clicked', (modalEvent) => {
                if (modalEvent.target.name === 'ok') {
                    const payload = new FormData();
                    // eslint-disable-next-line @typescript-eslint/restrict-plus-operands
                    payload.append('table', table);
                    payload.append('uid', uid);
                    new AjaxRequest(TYPO3.settings.ajaxUrls.xima_recordlist_delete).post('', { body: payload }, '').then(() => tslib_es6.__awaiter(this, void 0, void 0, function* () {
                        top === null || top === void 0 ? void 0 : top.TYPO3.Backend.ContentContainer.refresh();
                    }));
                    $modal.modal('hide');
                }
            });
        }
    }
    new Recordlist();

}));
