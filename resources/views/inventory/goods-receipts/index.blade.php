@extends('layouts.erp')

@section('title', __('Goods Receipts'))

@section('content_header')
    <h1>{{ __('Goods Receipts') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Goods are received from an approved purchase order.') }}</p>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :search-label="__('Receipt number, delivery note or supplier')" />

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Number') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Supplier') }}</th>
                        <th>{{ __('Purchase order') }}</th>
                        <th>{{ __('Warehouse') }}</th>
                        <th>{{ __('Delivery note') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($receipts as $receipt)
                        <tr>
                            <td><a href="{{ route('inventory.goods-receipts.show', $receipt->id) }}">{{ $receipt->receipt_number }}</a></td>
                            <td>{{ Formatter::date($receipt->receipt_date) }}</td>
                            <td>{{ $receipt->supplier?->name }}</td>
                            <td><a href="{{ route('inventory.purchase-orders.show', $receipt->purchase_order_id) }}">{{ $receipt->purchaseOrder?->order_number }}</a></td>
                            <td>{{ $receipt->warehouse?->code }}</td>
                            <td>{{ $receipt->delivery_note ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No goods receipts found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($receipts->hasPages())
            <div class="card-footer">{{ $receipts->links() }}</div>
        @endif
    </div>
@endsection
