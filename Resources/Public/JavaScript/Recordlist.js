define(['TYPO3/CMS/Core/Ajax/AjaxRequest', 'TYPO3/CMS/Backend/Modal'], (function (AjaxRequest, Modal) { 'use strict';

    // @ts-expect-error
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
    }
    new Recordlist();

}));
