@php
    $document = $order;
    $today = app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString();
@endphp
<div class="row g-3 mb-3">
    @include('sales._customer-fields')
    <div class="col-md-3">
        <label for="order_date" class="form-label">{{ __('Order date') }}</label>
        <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $order?->order_date?->toDateString() ?? $today) }}" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label for="delivery_date" class="form-label">{{ __('Delivery date') }}</label>
        <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', $order?->delivery_date?->toDateString()) }}" class="form-control @error('delivery_date') is-invalid @enderror">
        @error('delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label for="warehouse_id" class="form-label">{{ __('Ship from') }}</label>
        <select id="warehouse_id" name="warehouse_id" class="form-select" required>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order?->warehouse_id ?? $warehouses->first()?->id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@include('sales._lines')

<div class="mt-3">
    <label for="notes" class="form-label">{{ __('Notes') }}</label>
    <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes', $order?->notes) }}</textarea>
</div>
