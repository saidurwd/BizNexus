@extends('layouts.erp')

@section('title', __('Supplier Returns'))

@section('content_header')
    <h1>{{ __('Supplier Returns') }}</h1>
    <p class="text-body-secondary mb-0">{{ __('Goods sent back to suppliers. Returns are made from the purchase order they were received on.') }}</p>
@endsection

@section('content')
    <x-finance.list-filters :filters="$filters" :search-label="__('Return number, reason or supplier')" />

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
                        <th>{{ __('Reason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $return)
                        <tr>
                            <td><a href="{{ route('inventory.supplier-returns.show', $return->id) }}">{{ $return->return_number }}</a></td>
                            <td>{{ Formatter::date($return->return_date) }}</td>
                            <td>{{ $return->supplier?->name }}</td>
                            <td><a href="{{ route('inventory.purchase-orders.show', $return->purchase_order_id) }}">{{ $return->purchaseOrder?->order_number }}</a></td>
                            <td>{{ $return->warehouse?->code }}</td>
                            <td>{{ $return->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary py-4">{{ __('No supplier returns found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($returns->hasPages())
            <div class="card-footer">{{ $returns->links() }}</div>
        @endif
    </div>
@endsection
