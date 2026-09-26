{{-- Read-only lines of a quotation or sales order, with optional delivery and invoicing progress. Expects $document, $currencyCode and $progress (bool). --}}
<div class="card mb-3">
    <div class="card-body table-responsive p-0">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Product') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="text-end">{{ __('Qty') }}</th>
                    @if ($progress)
                        <th class="text-end">{{ __('Delivered') }}</th>
                        <th class="text-end">{{ __('Invoiced') }}</th>
                        @isset($available)<th class="text-end">{{ __('In stock') }}</th>@endisset
                    @endif
                    <th class="text-end">{{ __('Unit price') }}</th>
                    <th class="text-end">{{ __('Discount') }}</th>
                    <th>{{ __('Tax code') }}</th>
                    <th class="text-end">{{ __('Net') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($document->lines as $line)
                    @php $decimals = $line->product?->unit?->decimals ?? 0; @endphp
                    <tr>
                        <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a></td>
                        <td>{{ $line->description }}</td>
                        <td class="text-end">{{ Formatter::quantity($line->quantity, $decimals) }} {{ $line->product?->unit?->code }}</td>
                        @if ($progress)
                            <td class="text-end">{{ $line->needsDelivery() ? Formatter::quantity($line->delivered_quantity, $decimals) : '—' }}</td>
                            <td class="text-end">{{ Formatter::quantity($line->invoiced_quantity, $decimals) }}</td>
                            @isset($available)
                                @php $inStock = $available[$line->product_id] ?? 0; @endphp
                                <td class="text-end {{ $line->product?->isStocked() && bccomp((string) $inStock, $line->undeliveredQuantity(), 4) < 0 ? 'text-danger fw-bold' : '' }}">{{ $line->product?->isStocked() ? Formatter::quantity($inStock, $decimals) : '—' }}</td>
                            @endisset
                        @endif
                        <td class="text-end">{{ Formatter::unitPrice($line->unit_price, $currencyCode) }}</td>
                        <td class="text-end">{{ bccomp((string) $line->discount_amount, '0', 4) > 0 ? Formatter::amount($line->discount_amount, $currencyCode) : '—' }}</td>
                        <td>{{ $line->tax?->tax_code ?? '—' }}</td>
                        <td class="text-end">{{ Formatter::amount($line->subtotal, $currencyCode) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="{{ $progress ? (isset($available) ? 9 : 8) : 6 }}" class="text-end">{{ __('Net') }}</td><td class="text-end">{{ Formatter::amount($document->subtotal, $currencyCode) }}</td></tr>
                <tr><td colspan="{{ $progress ? (isset($available) ? 9 : 8) : 6 }}" class="text-end">{{ __('Estimated tax') }}</td><td class="text-end">{{ Formatter::amount($document->tax_amount, $currencyCode) }}</td></tr>
                <tr class="fw-bold"><td colspan="{{ $progress ? (isset($available) ? 9 : 8) : 6 }}" class="text-end">{{ __('Total') }} {{ $currencyCode }}</td><td class="text-end">{{ Formatter::amount($document->total_amount, $currencyCode) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>
