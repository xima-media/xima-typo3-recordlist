import DocumentService from "@typo3/core/document-service.js";
import flatpickr from "flatpickr";
import "flatpickr/dist/l10n";
import ShortcutButtonsPlugin from "shortcut-buttons-flatpickr";

/**
 * Enhances date-column filters with a flatpickr range picker (two month views,
 * two-click range selection) plus preset shortcut buttons. Reuses the flatpickr
 * bundle that TYPO3 core already ships, so no extra dependency is introduced.
 *
 * The picker only drives two hidden inputs (`[value]` start, `[valueEnd]` end);
 * the operator `<select>` decides whether the field behaves as a range
 * (`between`) or a single-boundary comparison (lt/gt/eq/neq).
 */
class RecordlistFilterDaterange {

  constructor() {
    DocumentService.ready().then(() => this.init());
  }

  init() {
    document.querySelectorAll('[data-recordlist-daterange]').forEach(container => {
      if (container.dataset.daterangeInitialized) {
        return;
      }
      container.dataset.daterangeInitialized = '1';
      this.setupContainer(container);
    });
  }

  setupContainer(container) {
    const display = container.querySelector('[data-daterange-display]');
    const startInput = container.querySelector('[data-daterange-start]');
    const endInput = container.querySelector('[data-daterange-end]');
    const exprName = container.dataset.exprName;
    const exprSelect = exprName
      ? document.querySelector(`select[name="${exprName}"]`)
      : null;

    if (!display || !startInput || !endInput) {
      return;
    }

    const presets = {
      last7days: container.dataset.presetLast7days || 'Last 7 days',
      last30days: container.dataset.presetLast30days || 'Last 30 days',
      thisMonth: container.dataset.presetThismonth || 'This month',
      lastMonth: container.dataset.presetLastmonth || 'Last month',
    };

    const locale = this.resolveLocale();
    let instance = null;

    const isRange = () => (exprSelect ? exprSelect.value === 'between' : true);

    const writeHidden = selectedDates => {
      if (!selectedDates.length) {
        startInput.value = '';
        endInput.value = '';
        return;
      }
      const fmt = date => flatpickr.formatDate(date, 'Y-m-d');
      startInput.value = fmt(selectedDates[0]);
      endInput.value = isRange()
        ? fmt(selectedDates[selectedDates.length > 1 ? 1 : 0])
        : '';
    };

    const build = () => {
      if (instance) {
        instance.destroy();
        instance = null;
      }

      const range = isRange();
      const seed = [startInput.value, range ? endInput.value : '']
        .filter(value => value !== '');

      const options = {
        mode: range ? 'range' : 'single',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd.m.Y',
        showMonths: range ? 2 : 1,
        // Render inline below the field: the filter panel sits low on the page,
        // so flatpickr's default body-appended popup flips up and detaches from
        // the input. Static positioning keeps it anchored under the field.
        static: true,
        locale,
        defaultDate: seed,
        allowInput: false,
        onChange: selectedDates => writeHidden(selectedDates),
      };

      if (range) {
        options.plugins = [ShortcutButtonsPlugin({
          theme: 'typo3',
          button: [
            { label: presets.last7days },
            { label: presets.last30days },
            { label: presets.thisMonth },
            { label: presets.lastMonth },
          ],
          onClick: (index, fp) => fp.setDate(this.presetRange(index), true),
        })];
      }

      instance = flatpickr(display, options);
    };

    build();

    if (exprSelect) {
      exprSelect.addEventListener('change', () => build());
    }
  }

  /**
   * Concrete start/end dates for a preset button (static, resolved at click time).
   * @param {number} index button index matching the configured preset order
   * @returns {Date[]} [start, end]
   */
  presetRange(index) {
    const today = new Date();
    const start = new Date(today);

    switch (index) {
      case 0: // Last 7 days
        start.setDate(today.getDate() - 7);
        return [start, today];
      case 1: // Last 30 days
        start.setDate(today.getDate() - 30);
        return [start, today];
      case 2: // This month
        return [new Date(today.getFullYear(), today.getMonth(), 1), today];
      case 3: { // Last month
        const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const last = new Date(today.getFullYear(), today.getMonth(), 0);
        return [first, last];
      }
      default:
        return [today, today];
    }
  }

  resolveLocale() {
    let lang = document.documentElement.lang || 'default';
    if (lang === 'en') {
      lang = 'default';
    } else if (lang === 'ch') {
      lang = 'zh';
    }
    return (flatpickr.l10ns && flatpickr.l10ns[lang]) || flatpickr.l10ns.default;
  }

}

new RecordlistFilterDaterange();
