@extends('layouts.erp')

@php $currencyCode = $receipt->currency?->code; @endphp

@section('title', __('Goods receipt :number', ['number' => $receipt->receipt_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Goods receipt :number', ['number' => $receipt->receipt_number]) }}</h1>
        <x-status-badge :status="$receipt->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Purchase order') }}</dt>
                <dd class="col-sm-9"><a href="{{ route('inventory.purchase-orders.show', $receipt->purchase_order_id) }}">{{ $receipt->purchaseOrder?->order_number }}</a></dd>
                <dt class="col-sm-3">{{ __('Supplier') }}</dt>
                <dd class="col-sm-9">{{ $receipt->supplier?->name }}</dd>
                <dt class="col-sm-3">{{ __('Receipt date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($receipt->receipt_date) }}</dd>
                <dt class="col-sm-3">{{ __('Warehouse') }}</dt>
                <dd class="col-sm-9">{{ $receipt->warehouse?->code }} — {{ $receipt->warehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('Delivery note') }}</dt>
                <dd class="col-sm-9">{{ $receipt->delivery_note ?? '—' }}</dd>
                @if ($receipt->currency)
                    <dt class="col-sm-3">{{ __('Currency') }}</dt>
                    <dd class="col-sm-9">{{ $currencyCode }} · {{ __('rate :rate', ['rate' => Formatter::rate($receipt->exchange_rate)]) }}</dd>
                @endif
                @if ($receipt->journal)
                    <dt class="col-sm-3">{{ __('Journal') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('finance.journals.show', $receipt->journal->id) }}">{{ $receipt->journal->journal_number }}</a></dd>
                @endif
                <dt class="col-sm-3">{{ __('Received by') }}</dt>
                <dd class="col-sm-9">{{ $receipt->createdBy?->name ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Product') }}</th>
                        <th class="text-end">{{ __('Quantity') }}</th>
                        <th class="text-end">{{ __('Unit price') }}</th>
                        <th class="text-end">{{ __('Value') }}</th>
                        @if ($receipt->currency)<th class="text-end">{{ __('Functional value') }}</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receipt->lines as $line)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a> {{ $line->product?->name }}</td>
                            <td class="text-end">{{ Formatter::quantity($line->quantity, $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                            <td class="text-end">{{ Formatter::unitPrice($line->unit_price, $currencyCode) }}</td>
                            <td class="text-end">{{ Formatter::amount($line->value, $currencyCode) }}</td>
                            @if ($receipt->currency)<td class="text-end">{{ Formatter::amount($line->functional_value) }}</td>@endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('inventory.goods-receipts.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
@endsection
