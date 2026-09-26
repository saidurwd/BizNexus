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
    <td><input type="text" name="{{ $name('description') }}" value="{{ $line['description'] ?? '' }}" class="form-control form-control-sm" maxlength="500" data-fill="description" aria-label="{{ __('Description') }}"></td>
    <td><input type="number" name="{{ $name('quantity') }}" value="{{ $line['quantity'] ?? 1 }}" class="form-control form-control-sm text-end @if ($error('quantity')) is-invalid @endif" step="any" min="0" required aria-label="{{ __('Quantity') }}"></td>
    <td class="small text-body-secondary" data-unit>{{ $selectedProduct?->unit?->code }}</td>
    <td><input type="number" name="{{ $name('unit_price') }}" value="{{ $line['unit_price'] ?? '' }}" class="form-control form-control-sm text-end @if ($error('unit_price')) is-invalid @endif" step="any" min="0" required data-fill="unit_price" aria-label="{{ __('Unit price') }}"></td>
    <td><input type="number" name="{{ $name('discount_amount') }}" value="{{ (float) ($line['discount_amount'] ?? 0) ?: '' }}" class="form-control form-control-sm text-end" step="any" min="0" aria-label="{{ __('Discount') }}"></td>
    <td>
        <select name="{{ $name('tax_id') }}" class="form-select form-select-sm" data-fill="tax_id" aria-label="{{ __('Tax code') }}">
            <option value="">{{ __('No tax') }}</option>
            @foreach ($taxes as $tax)
                <option value="{{ $tax->id }}" @selected((string) ($line['tax_id'] ?? '') === (string) $tax->id)>{{ $tax->tax_code }}</option>
            @endforeach
        </select>
    </td>
    <td><button type="button" class="btn btn-sm btn-link text-danger" data-remove-row title="{{ __('Remove line') }}"><i class="bi bi-x-lg"></i></button></td>
</tr>
