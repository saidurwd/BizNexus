@php
    $field = fn (string $name) => "lines[{$index}][{$name}]";
    $old = fn (string $name, $default = null) => old("lines.{$index}.{$name}", $line[$name] ?? $default);
    $error = fn (string $name) => $errors->first("lines.{$index}.{$name}");
@endphp
<tr>
    @if ($products)
        <td>
            <select name="{{ $field('product_id') }}" class="form-select form-select-sm" data-line-product data-searchable aria-label="{{ __('Product') }}">
                <option value="">{{ __('No product') }}</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-account="{{ $product->accountIdFor('revenue') }}" data-description="{{ $product->name }}" data-price="{{ (float) $product->sales_price }}" data-tax="{{ $product->sales_tax_id }}" @selected((string) $old('product_id') === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="{{ $field('warehouse_id') }}" data-field="warehouse_id" class="form-select form-select-sm @if ($error('warehouse_id')) is-invalid @endif" aria-label="{{ __('Warehouse') }}">
                <option value="">—</option>
                @foreach ($warehouses ?? [] as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((string) $old('warehouse_id') === (string) $warehouse->id)>{{ $warehouse->code }}</option>
                @endforeach
            </select>
            @if ($error('warehouse_id'))<div class="invalid-feedback">{{ $error('warehouse_id') }}</div>@endif
        </td>
    @endif
    <td>
        <select name="{{ $field('account_id') }}" class="form-select form-select-sm @if ($error('account_id')) is-invalid @endif" data-searchable data-field="account_id" required aria-label="{{ __('Account') }}">
            <option value="">{{ __('Select account') }}</option>
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}" @selected((string) $old('account_id') === (string) $account->id)>{{ $account->account_code }} — {{ $account->account_name }}</option>
            @endforeach
        </select>
        @if ($error('account_id'))<div class="invalid-feedback">{{ $error('account_id') }}</div>@endif
    </td>
    <td>
        <input type="text" data-field="description" name="{{ $field('description') }}" value="{{ $old('description') }}" class="form-control form-control-sm @if ($error('description')) is-invalid @endif" maxlength="255" required aria-label="{{ __('Description') }}">
        @if ($error('description'))<div class="invalid-feedback">{{ $error('description') }}</div>@endif
    </td>
    <td>
        <input type="number" name="{{ $field('quantity') }}" value="{{ $old('quantity', 1) }}" class="form-control form-control-sm text-end @if ($error('quantity')) is-invalid @endif" step="any" min="0" required data-field="quantity" aria-label="{{ __('Quantity') }}">
    </td>
    <td>
        <input type="number" name="{{ $field('unit_price') }}" value="{{ $old('unit_price') }}" class="form-control form-control-sm text-end @if ($error('unit_price')) is-invalid @endif" step="any" min="0" required data-field="unit_price" aria-label="{{ __('Unit price') }}">
    </td>
    <td>
        <input type="number" name="{{ $field('discount_amount') }}" value="{{ $old('discount_amount') }}" class="form-control form-control-sm text-end" step="any" min="0" data-field="discount_amount" aria-label="{{ __('Discount') }}">
    </td>
    <td>
        <select name="{{ $field('tax_id') }}" data-field="tax_id" class="form-select form-select-sm" aria-label="{{ __('Tax code') }}">
            <option value="">{{ __('By tax rules') }}</option>
            @foreach ($taxes as $tax)
                <option value="{{ $tax->id }}" @selected((string) $old('tax_id') === (string) $tax->id)>{{ $tax->tax_code }} — {{ Formatter::percent($tax->rate) }}%</option>
            @endforeach
        </select>
    </td>
    <td class="text-end" data-line-net>0.00</td>
    <td>
        <button type="button" class="btn btn-sm btn-link text-danger" data-remove-line title="{{ __('Remove line') }}"><i class="bi bi-x-lg"></i></button>
    </td>
</tr>
