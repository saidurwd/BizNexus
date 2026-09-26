@extends('layouts.erp')

@section('title', __('Purchase Orders'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Purchase Orders') }}</h1>
        @can('inventory.purchase-orders.create')
            <a href="{{ route('inventory.purchase-orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New purchase order') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="\Modules\Inventory\Models\PurchaseOrder::STATUSES" :search-label="__('Order number, supplier reference or supplier')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Expected') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('inventory.purchase-orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                            <td>{{ Formatter::date($order->order_date) }}</td>
                            <td>{{ $order->supplier?->name }}</td>
                            <td>{{ $order->expected_date ? Formatter::date($order->expected_date) : '—' }}</td>
                            <td>{{ $order->warehouse?->code }}</td>
                            <td class="text-end">{{ Formatter::amount($order->total_amount, $order->currency?->code) }} <small class="text-body-secondary">{{ $order->currency?->code }}</small></td>
                            <td><x-status-badge :status="$order->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No purchase orders found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
