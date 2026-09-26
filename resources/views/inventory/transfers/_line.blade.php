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
                <option value="{{ $product->id }}" data-unit="{{ $product->unit?->code }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
            @endforeach
        </select>
    </td>
    <td><input type="number" name="{{ $name('quantity') }}" value="{{ $line['quantity'] ?? '' }}" class="form-control form-control-sm text-end @if ($error('quantity')) is-invalid @endif" step="any" min="0" required aria-label="{{ __('Quantity') }}"></td>
    <td class="small text-body-secondary" data-unit>{{ $selectedProduct?->unit?->code }}</td>
    <td><button type="button" class="btn btn-sm btn-link text-danger" data-remove-row title="{{ __('Remove line') }}"><i class="bi bi-x-lg"></i></button></td>
</tr>
