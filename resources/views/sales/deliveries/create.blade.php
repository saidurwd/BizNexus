@extends('layouts.erp')

@section('title', __('Deliver :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Deliver :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Enter what is shipped. Stock leaves the warehouse at average cost; the delivery posts at once.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('sales.deliveries.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Customer') }}</label>
                    <input type="text" class="form-control" value="{{ $order->customer?->name }}" disabled>
                </div>
                <div class="col-md-2">
                    <label for="delivery_date" class="form-label">{{ __('Delivery date') }}</label>
                    <input type="date" id="delivery_date" name="delivery_date" value="{{ old('delivery_date', app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label for="warehouse_id" class="form-label">{{ __('Ship from') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="carrier" class="form-label">{{ __('Carrier') }}</label>
                    <input type="text" id="carrier" name="carrier" value="{{ old('carrier') }}" class="form-control" maxlength="100">
                </div>
                <div class="col-md-2">
                    <label for="tracking_number" class="form-label">{{ __('Tracking number') }}</label>
                    <input type="text" id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}" class="form-control" maxlength="100">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Ordered') }}</th>
                            <th class="text-end">{{ __('Already delivered') }}</th>
                            <th class="text-end">{{ __('In stock at :warehouse', ['warehouse' => $order->warehouse?->code]) }}</th>
                            <th style="width: 160px" class="text-end">{{ __('Deliver now') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @continue(! $line->needsDelivery())
                            @php $decimals = $line->product?->unit?->decimals ?? 0; @endphp
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->quantity, $decimals) }} {{ $line->product?->unit?->code }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->delivered_quantity, $decimals) }}</td>
                                <td class="text-end">{{ $line->product?->isStocked() ? Formatter::quantity($available[$order->warehouse_id][$line->product_id] ?? 0, $decimals) : '—' }}</td>
                                <td>
                                    @if (bccomp($line->undeliveredQuantity(), '0', 4) > 0)
                                        <input type="number" name="lines[{{ $line->id }}]" value="{{ old("lines.{$line->id}", (float) $line->undeliveredQuantity()) }}" class="form-control form-control-sm text-end" step="any" min="0" max="{{ (float) $line->undeliveredQuantity() }}" aria-label="{{ __('Deliver now') }}">
                                    @else
                                        <span class="text-body-secondary">{{ __('Complete') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <label for="notes" class="form-label">{{ __('Notes') }}</label>
            <textarea id="notes" name="notes" class="form-control" rows="2" maxlength="2000">{{ old('notes') }}</textarea>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm(@js(__('Post this delivery? Stock and the ledger are updated at once.')))">{{ __('Post delivery') }}</button>
            <a href="{{ route('sales.orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
