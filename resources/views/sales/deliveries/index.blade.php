@extends('layouts.erp')

@section('title', __('Delivery Notes'))

@section('content_header')
    <h1>{{ __('Delivery Notes') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Goods are delivered from a confirmed sales order.') }}</p>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :search-label="__('Delivery number, tracking number or customer')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Sales order') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th>{{ __('Carrier') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td><a href="{{ route('sales.deliveries.show', $delivery->id) }}">{{ $delivery->delivery_number }}</a></td>
                            <td>{{ Formatter::date($delivery->delivery_date) }}</td>
                            <td>{{ $delivery->customer?->name }}</td>
                            <td><a href="{{ route('sales.orders.show', $delivery->sales_order_id) }}">{{ $delivery->salesOrder?->order_number }}</a></td>
                            <td>{{ $delivery->warehouse?->code }}</td>
                            <td>{{ $delivery->carrier ?? '—' }} <small class="text-body-secondary">{{ $delivery->tracking_number }}</small></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No deliveries found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($deliveries->hasPages())
            <div class="card-footer">{{ $deliveries->links() }}</div>
        @endif
    </div>
@endsection
