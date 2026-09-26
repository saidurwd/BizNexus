@props([
    'accounts',
    'taxes',
    'lines' => [],
    'currency' => null,
])

{{--
    Line entry for sales and purchase documents: account, description, quantity, unit price, line discount and
    tax code. Tax is calculated on the server when the document is saved; the running total here is before tax.
--}}
@php
    $lines = array_values($lines ?: [['account_id' => null, 'description' => '', 'quantity' => 1, 'unit_price' => '', 'discount_amount' => '', 'tax_id' => null]]);
    $lineErrors = $errors->has('lines') ? $errors->first('lines') : null;
@endphp

<div class="document-lines" data-next-index="{{ count($lines) }}">
    @if ($lineErrors)
        <div class="alert alert-danger py-2">{{ $lineErrors }}</div>
    @endif

    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 220px">{{ __('Account') }}</th>
                    <th style="min-width: 200px">{{ __('Description') }}</th>
                    <th style="width: 100px" class="text-end">{{ __('Qty') }}</th>
                    <th style="width: 140px" class="text-end">{{ __('Unit price') }}</th>
                    <th style="width: 120px" class="text-end">{{ __('Discount') }}</th>
                    <th style="min-width: 160px">{{ __('Tax code') }}</th>
                    <th style="width: 140px" class="text-end">{{ __('Net') }}</th>
                    <th style="width: 40px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                </tr>
            </thead>
            <tbody data-lines>
                @foreach ($lines as $index => $line)
                    @include('components.finance.document-line-row', ['index' => $index, 'line' => $line])
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" class="text-end">
                        <strong>{{ __('Total before tax') }}</strong>
                        @if ($currency)
                            <small class="text-body-secondary">({{ $currency }})</small>
                        @endif
                        <div class="small text-body-secondary">{{ __('Tax is calculated from the tax codes when you save.') }}</div>
                    </td>
                    <td class="text-end fw-bold" data-lines-total>0.00</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <button type="button" class="btn btn-sm btn-outline-primary" data-add-line>
        <i class="bi bi-plus-lg"></i> {{ __('Add line') }}
    </button>

    <template data-line-template>
        @include('components.finance.document-line-row', ['index' => '__INDEX__', 'line' => ['quantity' => 1]])
    </template>
</div>

@once
    @push('js')
        <script type="module">
            document.querySelectorAll('.document-lines').forEach((container) => {
                const body = container.querySelector('[data-lines]');
                const template = container.querySelector('[data-line-template]');
                const totalCell = container.querySelector('[data-lines-total]');
                const number = (input) => parseFloat(input?.value || '0') || 0;

                const recalculate = () => {
                    let total = 0;
                    body.querySelectorAll('tr').forEach((row) => {
                        const net = number(row.querySelector('[data-field=quantity]')) * number(row.querySelector('[data-field=unit_price]'))
                            - number(row.querySelector('[data-field=discount_amount]'));
                        row.querySelector('[data-line-net]').textContent = net.toFixed(2);
                        total += net;
                    });
                    totalCell.textContent = total.toFixed(2);
                };

                container.querySelector('[data-add-line]').addEventListener('click', () => {
                    const index = container.dataset.nextIndex++;
                    body.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                    window.initSearchableSelects?.(body.lastElementChild);
                    recalculate();
                });

                body.addEventListener('click', (event) => {
                    const button = event.target.closest('[data-remove-line]');
                    if (button && body.querySelectorAll('tr').length > 1) {
                        button.closest('tr').remove();
                        recalculate();
                    }
                });

                body.addEventListener('input', recalculate);
                recalculate();
            });
        </script>
    @endpush
@endonce
