{{--
    Purchase order header and lines. Expects $order (or null), $prefill, $suppliers, $warehouses, $currencies,
    $products and $taxes.
--}}
@php
    $value = fn (string $field, $default = null) => old($field, $order?->{$field} instanceof \Carbon\CarbonInterface ? $order->{$field}->toDateString() : ($order?->{$field} ?? $prefill[$field] ?? $default));
    $field = fn (string $name) => $errors->has($name) ? 'is-invalid' : '';
    $lines = old('lines', $order?->lines->map(fn ($line) => $line->only(['product_id', 'description', 'quantity', 'unit_price', 'tax_id']))->all() ?? $prefill['lines'] ?? [['quantity' => 1]]);
    $productOption = fn ($product, $selected) => '<option value="'.$product->id.'" data-description="'.e($product->name).'" data-unit_price="'.e((float) $product->purchase_price).'" data-tax_id="'.e($product->purchase_tax_id).'" data-unit="'.e($product->unit?->code).'"'.((string) $selected === (string) $product->id ? ' selected' : '').'>'.e($product->sku.' — '.$product->name).'</option>';
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <label for="supplier_id" class="form-label">{{ __('Supplier') }}</label>
        <select id="supplier_id" name="supplier_id" class="form-select {{ $field('supplier_id') }}" data-searchable required>
            <option value="">{{ __('Select supplier') }}</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) $value('supplier_id') === (string) $supplier->id)>{{ $supplier->supplier_code }} — {{ $supplier->name }}</option>
            @endforeach
        </select>
        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="supplier_reference" class="form-label">{{ __('Supplier reference') }}</label>
        <input type="text" id="supplier_reference" name="supplier_reference" value="{{ $value('supplier_reference') }}" class="form-control" maxlength="100">
    </div>
    <div class="col-md-4">
        <label for="warehouse_id" class="form-label">{{ __('Deliver to') }}</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select {{ $field('warehouse_id') }}" required>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) $value('warehouse_id', $warehouses->first()?->id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="order_date" class="form-label">{{ __('Order date') }}</label>
        <input type="date" id="order_date" name="order_date" value="{{ $value('order_date', app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control {{ $field('order_date') }}" required>
    </div>
    <div class="col-md-3">
        <label for="expected_date" class="form-label">{{ __('Expected delivery') }}</label>
        <input type="date" id="expected_date" name="expected_date" value="{{ $value('expected_date') }}" class="form-control {{ $field('expected_date') }}">
        @error('expected_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="currency_id" class="form-label">{{ __('Currency') }}</label>
        <select id="currency_id" name="currency_id" class="form-select">
            <option value="">{{ __('Company currency') }}</option>
            @foreach ($currencies as $currency)
                <option value="{{ $currency->id }}" @selected((string) $value('currency_id') === (string) $currency->id)>{{ $currency->code }}</option>
            @endforeach
        </select>
    </div>
</div>

<div data-repeater>
    @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 240px">{{ __('Product') }}</th>
                    <th style="min-width: 200px">{{ __('Description') }}</th>
                    <th style="width: 120px" class="text-end">{{ __('Qty') }}</th>
                    <th style="width: 60px"></th>
                    <th style="width: 140px" class="text-end">{{ __('Unit price') }}</th>
                    <th style="min-width: 150px">{{ __('Tax code') }}</th>
                    <th style="width: 40px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                </tr>
            </thead>
            <tbody data-rows>
                @foreach (array_values($lines) as $index => $line)
                    @include('inventory.purchase-orders._line', ['index' => $index, 'line' => $line])
                @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button>
    <template data-row-template>
        @include('inventory.purchase-orders._line', ['index' => '__INDEX__', 'line' => ['quantity' => 1]])
    </template>
</div>

<div class="mt-3">
    <label for="notes" class="form-label">{{ __('Notes') }}</label>
    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ $value('notes') }}</textarea>
</div>

<x-inventory.line-repeater-script />
