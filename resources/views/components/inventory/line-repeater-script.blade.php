{{--
    Behaviour for inventory line tables: [data-repeater] holds tbody[data-rows], template[data-row-template]
    (rows use __INDEX__ in field names), [data-add-row] and [data-remove-row] buttons. Choosing a product in a
    select[data-product] fills the row's [data-fill="<key>"] fields from the option's data-<key> attributes,
    and [data-unit] shows the product's unit.
--}}
@once
    @push('js')
        <script type="module">
            const fillRow = (select) => {
                const option = select.selectedOptions[0];
                const row = select.closest('tr');
                if (!option || !row) return;
                row.querySelectorAll('[data-fill]').forEach((field) => {
                    const value = option.dataset[field.dataset.fill];
                    if (value !== undefined && (field.value === '' || field.dataset.overwrite !== undefined)) {
                        field.value = value;
                    }
                });
                const unit = row.querySelector('[data-unit]');
                if (unit) unit.textContent = option.dataset.unit ?? '';
            };

            document.querySelectorAll('[data-repeater]').forEach((container) => {
                const rows = container.querySelector('[data-rows]');
                const template = container.querySelector('[data-row-template]');
                let next = rows.querySelectorAll('tr').length;

                container.querySelector('[data-add-row]')?.addEventListener('click', () => {
                    rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', next++));
                    window.initSearchableSelects?.(rows.lastElementChild);
                });

                rows.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-row]');
                    if (button && rows.querySelectorAll('tr').length > 1) {
                        button.closest('tr').remove();
                    }
                });

                rows.addEventListener('change', (event) => {
                    if (event.target.matches('select[data-product]')) fillRow(event.target);
                });
            });
        </script>
    @endpush
@endonce
