@extends('layouts.erp')

@section('title', __('Return goods on :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Return goods on :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Enter what goes back to the supplier. The return reverses the receipt at once; goods already invoiced leave a credit note due from the supplier.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.supplier-returns.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Supplier') }}</label>
                    <input type="text" class="form-control" value="{{ $order->supplier?->name }}" disabled>
                </div>
                <div class="col-md-2">
                    <label for="return_date" class="form-label">{{ __('Return date') }}</label>
                    <input type="date" id="return_date" name="return_date" value="{{ old('return_date', app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label for="warehouse_id" class="form-label">{{ __('From warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="reason" class="form-label">{{ __('Reason') }}</label>
                    <input type="text" id="reason" name="reason" value="{{ old('reason') }}" class="form-control" maxlength="255" placeholder="{{ __('e.g. damaged in transit') }}">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Received') }}</th>
                            <th class="text-end">{{ __('Invoiced') }}</th>
                            <th style="width: 160px" class="text-end">{{ __('Return now') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @continue(bccomp((string) $line->received_quantity, '0', 4) <= 0)
                            @php $decimals = $line->product?->unit?->decimals ?? 0; @endphp
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->received_quantity, $decimals) }} {{ $line->product?->unit?->code }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->invoiced_quantity, $decimals) }}</td>
                                <td><input type="number" name="lines[{{ $line->id }}]" value="{{ old("lines.{$line->id}") }}" class="form-control form-control-sm text-end" step="any" min="0" max="{{ (float) $line->received_quantity }}" aria-label="{{ __('Return now') }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm(@js(__('Post this return? Stock and the ledger are updated at once.')))">{{ __('Post return') }}</button>
            <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
