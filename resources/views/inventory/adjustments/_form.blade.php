{{--
    Stock adjustment or count. Expects $adjustment (or null), $reason, $warehouseId, $lines, $warehouses and $products.
    A count takes the counted quantity per product; other reasons a signed change and an optional unit cost.
--}}
@php
    $isCount = $reason === 'count';
    $lines = old('lines', $lines ?: [[]]);
    $productOption = fn ($product, $selected) => '<option value="'.$product->id.'" data-unit="'.e($product->unit?->code).'" data-unit_cost="'.e((float) $product->averageCost()).'"'.((string) $selected === (string) $product->id ? ' selected' : '').'>'.e($product->sku.' — '.$product->name).'</option>';
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label class="form-label">{{ __('Reason') }}</label>
        @if ($isCount)
            <input type="hidden" name="reason" value="count">
            <input type="text" class="form-control" value="{{ __('Stock count') }}" disabled>
        @else
            <select name="reason" class="form-select">
                @foreach (\Modules\Inventory\Models\StockAdjustment::reasonLabels() as $key => $label)
                    @continue($key === 'count')
                    <option value="{{ $key }}" @selected(old('reason', $reason) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-3">
        <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select" required @if ($isCount && ! $adjustment) onchange="window.location = @js(route('inventory.adjustments.create', ['reason' => 'count'])) + '&warehouse=' + this.value" @endif>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $warehouseId) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label for="adjustment_date" class="form-label">{{ __('Date') }}</label>
        <input type="date" id="adjustment_date" name="adjustment_date" value="{{ old('adjustment_date', $adjustment?->adjustment_date?->toDateString() ?? app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control" required>
    </div>
</div>

<p class="text-body-secondary small">
    @if ($isCount)
        {{ __('Enter what was counted. When posted, the difference from the books becomes the adjustment; products you leave out are not changed.') }}
    @else
        {{ __('Enter increases as positive and decreases as negative quantities. Increases use the unit cost you enter, or the current average cost; decreases always leave at the average cost.') }}
    @endif
</p>

<div data-repeater>
    @error('lines')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th style="min-width: 260px">{{ __('Product') }}</th>
                    @if ($isCount)
                        <th class="text-end">{{ __('On the books') }}</th>
                        <th style="width: 160px" class="text-end">{{ __('Counted') }}</th>
                    @else
                        <th style="width: 160px" class="text-end">{{ __('Quantity change') }}</th>
                        <th style="width: 160px" class="text-end">{{ __('Unit cost') }}</th>
                    @endif
                    <th style="width: 60px"></th>
                    <th style="width: 40px"><span class="visually-hidden">{{ __('Remove') }}</span></th>
                </tr>
            </thead>
            <tbody data-rows>
                @foreach (array_values($lines) as $index => $line)
                    @include('inventory.adjustments._line', ['index' => $index, 'line' => $line])
                @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-add-row><i class="bi bi-plus-lg"></i> {{ __('Add line') }}</button>
    <template data-row-template>
        @include('inventory.adjustments._line', ['index' => '__INDEX__', 'line' => []])
    </template>
</div>

<div class="mt-3">
    <label for="notes" class="form-label">{{ __('Notes') }}</label>
    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $adjustment?->notes) }}</textarea>
</div>

<x-inventory.line-repeater-script />
