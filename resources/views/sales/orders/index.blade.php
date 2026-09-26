@extends('layouts.erp')

@section('title', __('Sales Orders'))

@section('content_header')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1 class="m-0">{{ __('Sales Orders') }}</h1>
        @can('sales.orders.create')
            <a href="{{ route('sales.orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ __('New sales order') }}</a>
        @endcan
    </div>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :statuses="\Modules\Sales\Models\SalesOrder::STATUSES" :search-label="__('Number, customer reference or customer')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Delivery date') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td><a href="{{ route('sales.orders.show', $order->id) }}">{{ $order->order_number }}</a></td>
                            <td>{{ Formatter::date($order->order_date) }}</td>
                            <td>{{ $order->customer?->name }}</td>
                            <td>{{ $order->delivery_date ? Formatter::date($order->delivery_date) : '—' }}</td>
                            <td>{{ $order->warehouse?->code }}</td>
                            <td class="text-end">{{ Formatter::amount($order->total_amount, $order->currency?->code) }} <small class="text-body-secondary">{{ $order->currency?->code }}</small></td>
                            <td><x-status-badge :status="$order->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">{{ __('No sales orders found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
