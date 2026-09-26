@php
    $name = fn (string $key) => "lines[{$index}][{$key}]";
    $error = fn (string $key) => $errors->first("lines.{$index}.{$key}");
    $selectedProduct = $products->firstWhere('id', (int) ($line['product_id'] ?? 0));
@endphp
<tr>
    <td>
        <select name="{{ $name('product_id') }}" class="form-select form-select-sm @if ($error('product_id')) is-invalid @endif" data-product data-searchable required aria-label="{{ __('Product') }}">
            <option value="">{{ __('Select product') }}</option>
            @foreach ($products as $product)
                {!! $productOption($product, $line['product_id'] ?? null) !!}
            @endforeach
        </select>
        @if ($error('product_id'))<div class="invalid-feedback">{{ $error('product_id') }}</div>@endif
    </td>
    @if ($isCount)
        <td class="text-end text-body-secondary">{{ isset($line['booked']) ? Formatter::quantity($line['booked'], $selectedProduct?->unit?->decimals ?? 0) : '—' }}</td>
        <td><input type="number" name="{{ $name('counted_quantity') }}" value="{{ $line['counted_quantity'] ?? '' }}" class="form-control form-control-sm text-end @if ($error('counted_quantity')) is-invalid @endif" step="any" min="0" required aria-label="{{ __('Counted') }}"></td>
    @else
        <td><input type="number" name="{{ $name('quantity') }}" value="{{ $line['quantity'] ?? '' }}" class="form-control form-control-sm text-end @if ($error('quantity')) is-invalid @endif" step="any" required aria-label="{{ __('Quantity change') }}"></td>
        <td><input type="number" name="{{ $name('unit_cost') }}" value="{{ $line['unit_cost'] ?? '' }}" class="form-control form-control-sm text-end" step="any" min="0" placeholder="{{ __('Average') }}" aria-label="{{ __('Unit cost') }}"></td>
    @endif
    <td class="small text-body-secondary" data-unit>{{ $selectedProduct?->unit?->code }}</td>
    <td><button type="button" class="btn btn-sm btn-link text-danger" data-remove-row title="{{ __('Remove line') }}"><i class="bi bi-x-lg"></i></button></td>
</tr>
