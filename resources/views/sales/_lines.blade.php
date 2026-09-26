{{--
    Product lines of a quotation or sales order. Expects $document (or null), $products and $taxes.
    Choosing a product fills its description, sales price and tax code.
--}}
@php
    $lines = old('lines', $document?->lines->map(fn ($line) => $line->only(['product_id', 'description', 'quantity', 'unit_price', 'discount_amount', 'tax_id']))->all() ?: [['quantity' => 1]]);
    $productOption = fn ($product, $selected) => '<option value="'.$product->id.'" data-description="'.e($product->name).'" data-unit_price="'.e((float) $product->sales_price).'" data-tax_id="'.e($product->sales_tax_id).'" data-unit="'.e($product->unit?->code).'"'.((string) $selected === (string) $product->id ? ' selected' : '').'>'.e($product->sku.' — '.$product->name).'</option>';
@endphp

<div data-repeater>
    @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 240px">{{ __('Product') }}</th>
                    <th style="min-width: 200px">{{ __('Description') }}</th>
                    <th style="width: 110px" class="text-end">{{ __('Qty') }}</th>
                    <th style="width: 50px"></th>
                    <th style="width: 130px" class="text-end">{{ __('Unit price') }}</th>
                    <th style="width: 120px" class="text-end">{{ __('Discount') }}</th>
                    <th style="min-width: 140px">{{ __('Tax code') }}</th>
                    <th style="width: 40px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                </tr>
            </thead>
            <tbody data-rows>
                @foreach (array_values($lines) as $index => $line)
                    @include('sales._line', ['index' => $index, 'line' => $line])
                @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button>
    <template data-row-template>
        @include('sales._line', ['index' => '__INDEX__', 'line' => ['quantity' => 1]])
    </template>
    <div class="form-text">{{ __('Tax shown on quotations and orders is an estimate; the invoice applies the tax rules.') }}</div>
</div>

<x-inventory.line-repeater-script />
