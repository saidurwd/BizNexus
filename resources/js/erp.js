import { Chart, registerables } from 'chart.js';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

Chart.register(...registerables);
window.Chart = Chart;
window.TomSelect = TomSelect;

/**
 * Any <select data-searchable> becomes a type-to-search dropdown (charts of accounts, customers, suppliers).
 */
function initSearchableSelects(root = document) {
    root.querySelectorAll('select[data-searchable]:not(.tomselected)').forEach((select) => {
        new TomSelect(select, {
            allowEmptyOption: true,
            maxOptions: null,
            plugins: select.multiple ? ['remove_button'] : [],
        });
    });
}

window.initSearchableSelects = initSearchableSelects;
document.addEventListener('DOMContentLoaded', () => initSearchableSelects());
