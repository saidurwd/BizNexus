@extends('layouts.erp')

@section('title', __('Supplier return :number', ['number' => $return->return_number]))

@section('content_header')
    <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="m-0">{{ __('Supplier return :number', ['number' => $return->return_number]) }}</h1>
        <x-status-badge :status="$return->status" class="fs-6" />
    </div>
@endsection

@section('content')
    <x-print-toolbar />
    <x-print-document-header :title="__('Supplier return')" :subtitle="$return->return_number" />

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">{{ __('Purchase order') }}</dt>
                <dd class="col-sm-9"><a href="{{ route('inventory.purchase-orders.show', $return->purchase_order_id) }}">{{ $return->purchaseOrder?->order_number }}</a></dd>
                <dt class="col-sm-3">{{ __('Supplier') }}</dt>
                <dd class="col-sm-9">{{ $return->supplier?->name }}</dd>
                <dt class="col-sm-3">{{ __('Return date') }}</dt>
                <dd class="col-sm-9">{{ Formatter::date($return->return_date) }}</dd>
                <dt class="col-sm-3">{{ __('From warehouse') }}</dt>
                <dd class="col-sm-9">{{ $return->warehouse?->code }} — {{ $return->warehouse?->name }}</dd>
                <dt class="col-sm-3">{{ __('Reason') }}</dt>
                <dd class="col-sm-9">{{ $return->reason ?? '—' }}</dd>
                @if ($return->journal)
                    <dt class="col-sm-3">{{ __('Journal') }}</dt>
                    <dd class="col-sm-9"><a href="{{ route('finance.journals.show', $return->journal->id) }}">{{ $return->journal->journal_number }}</a></dd>
                @endif
                <dt class="col-sm-3">{{ __('Created by') }}</dt>
                <dd class="col-sm-9">{{ $return->createdBy?->name ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body table-responsive p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr><th>{{ __('Product') }}</th><th class="text-end">{{ __('Quantity') }}</th><th class="text-end">{{ __('Receipt value') }}</th><th class="text-end">{{ __('Stock value') }}</th></tr>
                </thead>
                <tbody>
                    @foreach ($return->lines as $line)
                        <tr>
                            <td><a href="{{ route('inventory.products.show', $line->product_id) }}">{{ $line->product?->sku }}</a> {{ $line->product?->name }}</td>
                            <td class="text-end">{{ Formatter::quantity($line->quantity, $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                            <td class="text-end">{{ Formatter::amount($line->receipt_value) }}</td>
                            <td class="text-end">{{ Formatter::amount($line->stock_value) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('inventory.supplier-returns.index') }}" class="btn btn-secondary">{{ __('Back') }}</a>
    <x-print-signatures :labels="[__('Issued by'), __('Checked by'), __('Received by (supplier)')]" />
@endsection
