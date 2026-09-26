@extends('layouts.erp')

@section('title', __('Receive goods for :order', ['order' => $order->order_number]))

@section('content_header')
    <h1>{{ __('Receive goods for :order', ['order' => $order->order_number]) }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Enter what arrived. Stock items go into the warehouse at the order price; the receipt posts at once.') }}</p>
@endsection

@section('content')
    <form method="POST" action="{{ route('inventory.goods-receipts.store', $order->id) }}" class="card">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Supplier') }}</label>
                    <input type="text" class="form-control" value="{{ $order->supplier?->name }}" disabled>
                </div>
                <div class="col-md-2">
                    <label for="receipt_date" class="form-label">{{ __('Receipt date') }}</label>
                    <input type="date" id="receipt_date" name="receipt_date" value="{{ old('receipt_date', app(\Modules\Core\Services\CompanyContextService::class)->today()->toDateString()) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="warehouse_id" class="form-label">{{ __('Warehouse') }}</label>
                    <select id="warehouse_id" name="warehouse_id" class="form-select" required>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((string) old('warehouse_id', $order->warehouse_id) === (string) $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="delivery_note" class="form-label">{{ __('Delivery note') }}</label>
                    <input type="text" id="delivery_note" name="delivery_note" value="{{ old('delivery_note') }}" class="form-control" maxlength="100">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th class="text-end">{{ __('Ordered') }}</th>
                            <th class="text-end">{{ __('Already received') }}</th>
                            <th style="width: 160px" class="text-end">{{ __('Received now') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->lines as $line)
                            @php $decimals = $line->product?->unit?->decimals ?? 0; @endphp
                            <tr>
                                <td><strong>{{ $line->product?->sku }}</strong> {{ $line->description }}
                                    @unless ($line->product?->isStocked())<span class="badge text-bg-light">{{ $line->product?->typeLabel() }}</span>@endunless
                                </td>
                                <td class="text-end">{{ Formatter::quantity($line->quantity, $decimals) }} {{ $line->product?->unit?->code }}</td>
                                <td class="text-end">{{ Formatter::quantity($line->received_quantity, $decimals) }}</td>
                                <td>
                                    @if (bccomp($line->outstandingQuantity(), '0', 4) > 0)
                                        <input type="number" name="lines[{{ $line->id }}]" value="{{ old("lines.{$line->id}", (float) $line->outstandingQuantity()) }}" class="form-control form-control-sm text-end" step="any" min="0" max="{{ (float) $line->outstandingQuantity() }}" aria-label="{{ __('Received now') }}">
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
            <button type="submit" class="btn btn-primary" onclick="return confirm(@js(__('Post this goods receipt? Stock and the ledger are updated at once.')))">{{ __('Post receipt') }}</button>
            <a href="{{ route('inventory.purchase-orders.show', $order->id) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
        </div>
    </form>
@endsection
